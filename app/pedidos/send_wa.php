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

        // Copia opcional al administrador (mismo PDF + mensaje). Solo si el llamador lo pide
        // (copiaAdmin=1, exclusivo del flujo de confirmación de pedido) Y está activado en bot_config.
        if (($_POST['copiaAdmin'] ?? '') === '1') {
            try {
                $rcfg = Connection::runQuery("SELECT admin_telefono, admin_envio_activo FROM bot_config LIMIT 1");
                if ($rcfg && ($rc = mysqli_fetch_assoc($rcfg))) {
                    $adminTel = preg_replace('/\D/', '', (string) ($rc['admin_telefono'] ?? ''));
                    if ((int) $rc['admin_envio_activo'] === 1 && $adminTel !== '' && $adminTel !== preg_replace('/\D/', '', $to)) {
                        $client->sendDocument($adminTel, $pdf['path'], $pdf['filename'], $text);
                    }
                }
            } catch (Throwable $eAdm) { error_log('[send_wa] copia admin falló: ' . $eAdm->getMessage()); }
        }

        unlink($pdf['path']);
    } else {
        $client->sendText($to, $text);
    }
} else {
    $client->sendText($to, $text);
}

echo json_encode(['ok' => true]);
