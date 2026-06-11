<?php
define('__ROOT__', dirname(__DIR__));
require __ROOT__ . '/config/global.php';
require __ROOT__ . '/config/Connection.php';
require __ROOT__ . '/config/WhatsAppClient.php';
require __ROOT__ . '/pedidos/ticket_pdf.php';

header('Content-Type: application/json');

$to        = $_POST['to']        ?? '';
$text      = $_POST['text']      ?? '';
$ped       = $_POST['ped']       ?? '';
$idventa   = $_POST['idventa']   ?? '';
$clienteid = $_POST['clienteid'] ?? '';
$_t        = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['t'] ?? ''));

if (!$to || !$text) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing params']);
    exit;
}

// Tenant routing: set DB + load WA credentials from pedidos_platform
$_waPhoneId = defined('WA_PHONE_NUMBER_ID') ? WA_PHONE_NUMBER_ID : '';
$_waToken   = defined('WA_ACCESS_TOKEN')    ? WA_ACCESS_TOKEN    : '';
if ($_t !== '') {
    Connection::setDatabase('atiende_' . $_t);
    try {
        $_ppPdo  = new PDO('mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $_ppStmt = $_ppPdo->prepare('SELECT whatsapp_phone_id, whatsapp_token_enc FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
        $_ppStmt->execute([$_t]);
        $_ppRow  = $_ppStmt->fetch();
        if ($_ppRow) {
            if ($_ppRow['whatsapp_phone_id']) $_waPhoneId = $_ppRow['whatsapp_phone_id'];
            if ($_ppRow['whatsapp_token_enc']) {
                $_key = hex2bin(PLATFORM_ENCRYPTION_KEY);
                $_d   = base64_decode($_ppRow['whatsapp_token_enc']);
                $_dec = openssl_decrypt(substr($_d, 28), 'aes-256-gcm', $_key, OPENSSL_RAW_DATA, substr($_d, 0, 12), substr($_d, 12, 16));
                if ($_dec !== false) $_waToken = $_dec;
            }
        }
        unset($_ppPdo, $_ppStmt, $_ppRow, $_key, $_d, $_dec);
    } catch (Exception $_e) { /* silent */ }
}
unset($_t);

error_log('[send_wa] t=' . ($_POST['t'] ?? '') . ' phone_id=' . (strlen($_waPhoneId) > 4 ? substr($_waPhoneId,0,6).'...' : '(empty)') . ' token_ok=' . (strlen($_waToken) > 10 ? 'YES' : 'NO') . ' ped=' . $ped);
$client = new WhatsAppClient($_waPhoneId, $_waToken, WA_API_VERSION);
unset($_waPhoneId, $_waToken);

if ($idventa && $clienteid) {
    // Enviar mensaje interactivo con botones de respuesta
    $client->sendInteractiveButtons($to, $text, [
        ['id' => '1', 'title' => '1 - Si'],
        ['id' => '2', 'title' => '2 - No'],
    ]);

    // Guardar estado en contactos para que webhook sepa esperar respuesta de texto
    $phone  = preg_replace('/\D/', '', $to);
    $estado = addslashes(json_encode([
        'type'      => 'pedido_respuesta',
        'idventa'   => intval($idventa),
        'clienteid' => intval($clienteid),
    ]));
    Connection::runQuery(
        "INSERT INTO contactos (id, telefono, nombre, menu, esperaRespuesta, anterior)
         VALUES ('$phone', '$phone', '', '0', 1, '$estado')
         ON DUPLICATE KEY UPDATE anterior='$estado', esperaRespuesta=1, menu='0'"
    );
} elseif ($ped) {
    $pdf = generarTicketPdf($ped);
    if ($pdf && file_exists($pdf['path'])) {
        // Un solo mensaje: PDF con el resumen como caption
        $client->sendDocument($to, $pdf['path'], $pdf['filename'], $text);

        // Datos del cliente del pedido (para los encabezados de las copias admin / vendedor).
        // $ped es el id de link_pedidos; se resuelve el cliente con el mismo JOIN que index.php.
        $cliLabel = '';
        try {
            $cfgWM   = getWebMasterConfig();
            $joinKey = !empty($cfgWM['data']['b2b']) ? 'codigo' : 'id';   // b2b→codigo, b2c→id (igual que index.php)
            $rcli = Connection::runQuery("SELECT clientes.razonSocial AS rs, clientes.codigo AS cc FROM link_pedidos JOIN clientes ON link_pedidos.clienteId = clientes.`$joinKey` WHERE link_pedidos.id = '" . intval($ped) . "' LIMIT 1");
            if ($rcli && ($rcr = mysqli_fetch_assoc($rcli))) {
                $cliLabel = trim((string) $rcr['rs'] . (trim((string) $rcr['cc']) !== '' ? ' - ' . $rcr['cc'] : ''));
            }
        } catch (Throwable $eCli) { error_log('[send_wa] resolver cliente falló: ' . $eCli->getMessage()); }

        // Reemplaza la primera línea (el saludo) del mensaje del cliente, conservando Pedido N°/Monto/Ticket.
        $reemplazarSaludo = function ($txt, $saludo) {
            $nl = strpos($txt, "\n");
            return $saludo . ($nl === false ? '' : substr($txt, $nl));
        };

        // Copia opcional al administrador (mismo PDF). Solo si copiaAdmin=1 Y está activado en bot_config.
        if (($_POST['copiaAdmin'] ?? '') === '1') {
            try {
                $rcfg = Connection::runQuery("SELECT admin_telefono, admin_envio_activo FROM bot_config LIMIT 1");
                if ($rcfg && ($rc = mysqli_fetch_assoc($rcfg))) {
                    $adminTel = preg_replace('/\D/', '', (string) ($rc['admin_telefono'] ?? ''));
                    if ((int) $rc['admin_envio_activo'] === 1 && $adminTel !== '' && $adminTel !== preg_replace('/\D/', '', $to)) {
                        $adminGreeting = $cliLabel !== '' ? 'Haz recibido un pedido del cliente ' . $cliLabel : '*Haz recibido un Pedido*';
                        $client->sendDocument($adminTel, $pdf['path'], $pdf['filename'], $reemplazarSaludo($text, $adminGreeting));
                    }
                }
            } catch (Throwable $eAdm) { error_log('[send_wa] copia admin falló: ' . $eAdm->getMessage()); }
        }

        // Copia al vendedor (mismo PDF). Solo si el pedido se cargó por el flujo de vendedor (llega 'ved').
        $ved = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($_POST['ved'] ?? ''));
        if ($ved !== '') {
            try {
                $rv = Connection::runQuery("SELECT nombre, telefono FROM vendedores WHERE codigo = '" . Connection::escape($ved) . "' LIMIT 1");
                if ($rv && ($rvr = mysqli_fetch_assoc($rv))) {
                    $vendTel = preg_replace('/\D/', '', (string) ($rvr['telefono'] ?? ''));
                    if ($vendTel !== '' && $vendTel !== preg_replace('/\D/', '', $to)) {
                        $vendNombre   = trim((string) ($rvr['nombre'] ?? ''));
                        $vendGreeting = ($vendNombre !== '' ? $vendNombre . ', ' : '') . 'el pedido de cliente ' . $cliLabel . ' ha sido confirmado.';
                        $client->sendDocument($vendTel, $pdf['path'], $pdf['filename'], $reemplazarSaludo($text, $vendGreeting));
                    }
                }
            } catch (Throwable $eV) { error_log('[send_wa] copia vendedor falló: ' . $eV->getMessage()); }
        }

        unlink($pdf['path']);
    } else {
        $client->sendText($to, $text);
    }
} else {
    $client->sendText($to, $text);
}

echo json_encode(['ok' => true]);
