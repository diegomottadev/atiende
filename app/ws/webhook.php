<?php
define('__ROOT__', dirname(__DIR__));
require __ROOT__ . '/config/global.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ---------- GET: verificación del webhook ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']         ?? $_GET['hub.mode']         ?? '';
    $challenge = $_GET['hub_challenge']    ?? $_GET['hub.challenge']    ?? '';
    $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';

    if ($mode === 'subscribe' && hash_equals(WA_VERIFY_TOKEN, $token)) {
        http_response_code(200);
        echo $challenge;
    } else {
        http_response_code(403);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$rawBody = file_get_contents('php://input');

// ---------- Identificar tenant por phone_number_id ----------
// Parsear el payload antes de incluir Conexion.php para poder elegir la DB correcta.
$tempPayload  = json_decode($rawBody, true);
$tempPhoneId  = $tempPayload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
// Número de WhatsApp del negocio (con el que chatea el cliente). Se persiste más abajo en
// bot_config.telefono para que el botón "Volver a WhatsApp" de finaliza.php use el número real.
$tempDisplayPhone = $tempPayload['entry'][0]['changes'][0]['value']['metadata']['display_phone_number'] ?? null;

// Valores por defecto (tenant por defecto / hardcodeado en global.php)
$tenantAppSecret = defined('WA_APP_SECRET')      ? WA_APP_SECRET      : '';
$tenantToken     = defined('WA_ACCESS_TOKEN')    ? WA_ACCESS_TOKEN    : '';
$tenantPhoneId   = defined('WA_PHONE_NUMBER_ID') ? WA_PHONE_NUMBER_ID : '';
$tenantDbName    = DB_NAME;
$tenantNombre    = DB_NAME;

if ($tempPhoneId) {
    try {
        $ppPdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4',
            DB_USERNAME, DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $ppStmt = $ppPdo->prepare(
            'SELECT db_name, nombre, whatsapp_phone_id, whatsapp_token_enc, whatsapp_app_secret_enc
             FROM tenants
             WHERE whatsapp_phone_id = ? AND estado = "activo" AND deleted_at IS NULL
             LIMIT 1'
        );
        $ppStmt->execute([$tempPhoneId]);
        $ppRow = $ppStmt->fetch();
        if ($ppRow) {
            $key     = hex2bin(PLATFORM_ENCRYPTION_KEY);
            $decrypt = function ($enc) use ($key) {
                if (!$enc) return false;
                $decoded = base64_decode($enc);
                $iv      = substr($decoded, 0, 12);
                $tag     = substr($decoded, 12, 16);
                $cipher  = substr($decoded, 28);
                return openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            };

            $tenantDbName   = $ppRow['db_name'];
            $tenantNombre   = $ppRow['nombre'];
            $tenantPhoneId  = $ppRow['whatsapp_phone_id'];

            if ($ppRow['whatsapp_token_enc']) {
                $v = $decrypt($ppRow['whatsapp_token_enc']);
                if ($v !== false) $tenantToken = $v;
            }
            if ($ppRow['whatsapp_app_secret_enc']) {
                $v = $decrypt($ppRow['whatsapp_app_secret_enc']);
                if ($v !== false) $tenantAppSecret = $v;
            }

            error_log('[webhook] tenant identificado: db=' . $tenantDbName . ' phoneId=' . $tenantPhoneId);
        } else {
            error_log('[webhook] phone_number_id=' . $tempPhoneId . ' no encontrado en pedidos_platform, usando default');
        }
    } catch (PDOException $e) {
        error_log('[webhook] tenant lookup error: ' . $e->getMessage());
    }
}

// Apuntar Conexion.php y Connection::runQuery al DB del tenant
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['tenant_db'] = $tenantDbName;

require __ROOT__ . '/config/Conexion.php';
require __ROOT__ . '/config/WhatsAppClient.php';
require __ROOT__ . '/modelos/BotEngine.php';

// Apuntar Connection::runQuery (usado por BotEngine) al mismo DB
Connection::setDatabase($tenantDbName);

// ---------- Verificación HMAC ----------
// Sin app_secret no se puede validar la firma: rechazar (nunca firmar con clave vacía,
// porque el HMAC con clave '' es predecible y permitiría falsificar webhooks).
if (!is_string($tenantAppSecret) || $tenantAppSecret === '') {
    error_log('[webhook] sin app_secret configurado — rechazado phone_id=' . ($tempPhoneId ?? 'null') . ' db=' . $tenantDbName);
    http_response_code(403);
    exit;
}
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $rawBody, $tenantAppSecret);
if ($signature === '' || !hash_equals($expected, $signature)) {
    error_log('[webhook] HMAC inválida — phone_id=' . ($tempPhoneId ?? 'null') . ' db=' . $tenantDbName);
    http_response_code(403);
    exit;
}

// Responder 200 inmediatamente
http_response_code(200);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$payload = $tempPayload;
if (!$payload || ($payload['object'] ?? '') !== 'whatsapp_business_account') {
    exit;
}

$value = $payload['entry'][0]['changes'][0]['value'] ?? null;
if (!$value || !isset($value['messages'])) {
    exit;
}

$message = $value['messages'][0];
$contact = $value['contacts'][0] ?? null;
$type    = $message['type']              ?? 'unknown';
$rawUser = $message['from']              ?? '';
$rawName = $contact['profile']['name']   ?? ($message['from'] ?? 'usuario');

global $conexion;
$user     = preg_replace('/\D/', '', $rawUser);
$pushname = $conexion->real_escape_string(substr($rawName, 0, 100));

// ---------- Descartar mensajes viejos (cola de reintentos de Meta) ----------
// Si la app falló antes, Meta encola y reintenta mensajes viejos. Procesarlos
// hace que el menú "avance solo". Solo atendemos mensajes recientes (< 5 min).
$msgTs = (int)($message['timestamp'] ?? 0);
if ($msgTs > 0 && (time() - $msgTs) > 300) {
    error_log('[webhook] mensaje viejo descartado edad=' . (time() - $msgTs) . 's id=' . ($message['id'] ?? '?'));
    exit;
}

// ---------- Deduplicación: descartar webhooks reenviados por Meta ----------
// Meta reintenta el webhook si tarda; sin esto, el segundo procesamiento ve
// el estado ya avanzado (menu='0') y reenvía el menú principal de la nada.
$wamId = $message['id'] ?? '';
if ($wamId !== '') {
    $conexion->query("CREATE TABLE IF NOT EXISTS `wa_mensajes_procesados` (
        `wam_id` VARCHAR(128) NOT NULL,
        `procesado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`wam_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $wamEsc = $conexion->real_escape_string($wamId);
    $conexion->query("INSERT IGNORE INTO `wa_mensajes_procesados` (`wam_id`) VALUES ('$wamEsc')");
    if ($conexion->affected_rows === 0) {
        error_log('[webhook] mensaje duplicado descartado wam_id=' . $wamId);
        exit;
    }
}

$body     = '';
$location = null;

$client = new WhatsAppClient($tenantPhoneId, $tenantToken, WA_API_VERSION);

if ($type === 'text') {
    $rawBodyText = $message['text']['body'] ?? '';
    $body        = $conexion->real_escape_string(substr($rawBodyText, 0, 1000));

    $req = Connection::runQuery("SELECT anterior FROM contactos WHERE id = '$user'");
    $row = mysqli_fetch_assoc($req);
    if ($row) {
        $ant = json_decode($row['anterior'] ?? '{}', true);
        if (($ant['type'] ?? '') === 'pedido_respuesta' && !empty($ant['aceptado'])) {
            $idv = intval($ant['idventa']);
            $cid = intval($ant['clienteid']);
            Connection::runQuery("UPDATE fidelizar SET estado=1 WHERE pedidoid=$idv");
            Connection::runQuery(
                "INSERT INTO fidelizar (fecha, clienteid, pedidoid, tipo, mensaje, estado)
                 VALUES (NOW(), '$cid', '$idv', '0', '$body', 0)"
            );
            Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0 WHERE id='$user'");
            $client->sendText($user, "Gracias! Tu respuesta fue registrada.");
            exit;
        }

        if (($ant['type'] ?? '') === 'reclamo_respuesta') {
            $idReclamo = intval($ant['idReclamo']);
            Connection::runQuery("INSERT INTO msj_reclamos(id_reclamo, tipo, fecha, mensaje, respondido, estado, canal) VALUES ($idReclamo, 1, NOW(), '$body', 0, 'En analisis', -1)");
            Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0, menu='0' WHERE id='$user'");
            $client->sendText($user, "Gracias, *$pushname*, tu respuesta fue registrada. ¡Hasta pronto!");
            exit;
        }

        if (($ant['type'] ?? '') === 'consulta_respuesta') {
            $idConsulta = intval($ant['idConsulta']);
            Connection::runQuery("INSERT INTO msj_consultas(id_consulta, tipo, fecha, mensaje, respondido, estado, canal) VALUES ($idConsulta, 1, NOW(), '$body', 0, 'En analisis', -1)");
            Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0, menu='0' WHERE id='$user'");
            $client->sendText($user, "Gracias, *$pushname*, tu respuesta fue registrada. ¡Hasta pronto!");
            exit;
        }
    }

} elseif ($type === 'interactive') {
    $interactiveType = $message['interactive']['type'] ?? '';
    if ($interactiveType !== 'button_reply') exit;

    $buttonId = $message['interactive']['button_reply']['id'] ?? '';

    $req = Connection::runQuery("SELECT anterior FROM contactos WHERE id = '$user'");
    $row = mysqli_fetch_assoc($req);
    if (!$row) exit;

    $ant     = json_decode($row['anterior'] ?? '{}', true);
    $antType = $ant['type'] ?? '';

    if ($antType === 'reclamo_pregunta') {
        if ($buttonId === 'reclamo_si') {
            $ant['type'] = 'reclamo_respuesta';
            $antJson     = addslashes(json_encode($ant, JSON_UNESCAPED_UNICODE));
            Connection::runQuery("UPDATE contactos SET anterior='$antJson', esperaRespuesta=1 WHERE id='$user'");
            $client->sendText($user, "Por favor, escribí tu respuesta:");
        } elseif ($buttonId === 'reclamo_no') {
            Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0 WHERE id='$user'");
            $client->sendText($user, "Entendido. Podés responder más adelante desde el link en el mensaje anterior.");
        }
        exit;
    }

    if ($antType === 'consulta_pregunta') {
        if ($buttonId === 'consulta_si') {
            $ant['type'] = 'consulta_respuesta';
            $antJson     = addslashes(json_encode($ant, JSON_UNESCAPED_UNICODE));
            Connection::runQuery("UPDATE contactos SET anterior='$antJson', esperaRespuesta=1 WHERE id='$user'");
            $client->sendText($user, "Por favor, escribí tu respuesta:");
        } elseif ($buttonId === 'consulta_no') {
            Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0 WHERE id='$user'");
            $client->sendText($user, "Entendido. Podés responder más adelante desde el link en el mensaje anterior.");
        }
        exit;
    }

    if ($antType !== 'pedido_respuesta') exit;

    if ($buttonId === '1') {
        $ant['aceptado'] = true;
        $antJson         = addslashes(json_encode($ant, JSON_UNESCAPED_UNICODE));
        Connection::runQuery("UPDATE contactos SET anterior='$antJson' WHERE id='$user'");
        $client->sendText($user, "Por favor, escribi tu respuesta:");
    } elseif ($buttonId === '2') {
        Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0 WHERE id='$user'");
        $client->sendText($user, "Entendido. No se enviara respuesta adicional.");
    }
    exit;

} elseif ($type === 'location') {
    $location = [
        'latitude'  => $message['location']['latitude']  ?? 0,
        'longitude' => $message['location']['longitude'] ?? 0,
    ];
    $body = '';
} else {
    exit;
}

// Log del payload para debugging — SOLO si WA_DEBUG está activo.
// (Volcaba PII del cliente a ws/json_.txt, un archivo accesible por web.)
if (defined('WA_DEBUG') && WA_DEBUG) {
    $logData = json_encode(['user' => $user, 'pushname' => $pushname, 'type' => $type, 'body' => $body, 'db' => $tenantDbName], JSON_UNESCAPED_UNICODE);
    $logFile = fopen(__ROOT__ . '/ws/json_.txt', 'w');
    if ($logFile) { fwrite($logFile, $logData); fclose($logFile); }
}

$responseWebMaster = null; // cargado desde bot_config (DB del tenant) en el bloque de abajo

if (!$responseWebMaster || !isset($responseWebMaster['data']['empresa']['json'])) {
    // Para tenants atiende_*: cargar menú desde bot_config en la DB del tenant
    if (strncmp($tenantDbName, 'atiende_', 8) === 0) {
        $slug      = substr($tenantDbName, 8);

        // Obtener nombre legible desde pedidos_platform
        $ppNombre = $slug;
        try {
            $ppPdo2   = new PDO('mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $ppStmt2  = $ppPdo2->prepare('SELECT nombre FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
            $ppStmt2->execute([$slug]);
            $ppRow2   = $ppStmt2->fetch();
            if ($ppRow2) { $ppNombre = $ppRow2['nombre']; $tenantNombre = $ppNombre; }
        } catch (PDOException $e2) { /* silent */ }

        // Auto-capturar el número de WhatsApp del negocio en bot_config.telefono (solo si cambió),
        // así finaliza.php arma el wa.me con el número real sin configurarlo a mano.
        if ($tempDisplayPhone) {
            $dp = preg_replace('/\D/', '', (string) $tempDisplayPhone);
            if ($dp !== '') {
                @mysqli_query($conexion, "UPDATE bot_config SET telefono='" . $dp . "' WHERE telefono IS NULL OR telefono <> '" . $dp . "'");
            }
        }

        $menuRow = mysqli_query($conexion, "SELECT menu_json FROM bot_config LIMIT 1");
        if ($menuRow && ($cfgRow = mysqli_fetch_assoc($menuRow))) {
            $rawMenu = json_decode($cfgRow['menu_json'], true);
            if (is_array($rawMenu) && count($rawMenu) > 0) {
                $responseWebMaster = [
                    'data' => [
                        'identificador'  => $slug,
                        'empresa_nombre' => $ppNombre,
                        'b2b' => true, 'b2c' => false, 'mix' => false,
                        'empresa' => ['json' => json_encode(['menu' => $rawMenu])]
                    ]
                ];
                error_log('[webhook] menú cargado desde bot_config para slug=' . $slug . ' nombre=' . $ppNombre);
            } else {
                error_log('[webhook] bot_config vacío/inválido para slug=' . $slug);
            }
        }
    }

    // Fallback: archivo de configuración de desarrollo
    if (!$responseWebMaster || !isset($responseWebMaster['data']['empresa']['json'])) {
        $devCfgTenant = __ROOT__ . '/ws/dev_tenant_config_' . basename($tenantDbName) . '.json';
        $devCfg       = __ROOT__ . '/ws/dev_tenant_config.json';

        if (file_exists($devCfgTenant)) {
            $responseWebMaster = json_decode(file_get_contents($devCfgTenant), true);
        } elseif (file_exists($devCfg)) {
            $responseWebMaster = json_decode(file_get_contents($devCfg), true);
        }
    }

    if (!$responseWebMaster || !isset($responseWebMaster['data']['empresa']['json'])) {
        error_log('[webhook] No se pudo obtener configuración para db=' . $tenantDbName);
        exit;
    }
}

// Inyectar nombre de empresa para que BotEngine pueda reemplazar <empresa>
$responseWebMaster['data']['empresa_nombre'] = $tenantNombre;

$menuJson = json_decode($responseWebMaster['data']['empresa']['json'], true)['menu'] ?? [];
error_log('[webhook] db=' . $tenantDbName . ' empresa=' . $tenantNombre . ' menuItems=' . count($menuJson) . ' user=' . $user . ' body=' . $body);

$engine = new BotEngine($menuJson, $responseWebMaster, $client);
try {
    $engine->handle($user, $pushname, $body, $type, $location);
} catch (Throwable $e) {
    error_log('[webhook] EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
