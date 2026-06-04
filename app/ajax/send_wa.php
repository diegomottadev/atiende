<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require __ROOT__ . '/config/Conexion.php';
require __ROOT__ . '/config/WhatsAppClient.php';

// Mutación (envío WhatsApp por POST, sin switch) → exige token CSRF.
requireCsrf();

header('Content-Type: application/json');

$to   = $_POST['to']   ?? '';
$text = $_POST['text'] ?? '';
$type = $_POST['type'] ?? 'text';

if (!$to) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing params']);
    exit;
}
if ($type !== 'location' && !$text) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing params']);
    exit;
}
$interactive = ($_POST['interactive'] ?? '') === '1';
$subtype     = $_POST['subtype']     ?? 'reclamo';

$client = new WhatsAppClient(
    defined('WA_PHONE_NUMBER_ID') ? WA_PHONE_NUMBER_ID : '',
    defined('WA_ACCESS_TOKEN')    ? WA_ACCESS_TOKEN    : '',
    WA_API_VERSION
);

if ($type === 'location') {
    $lat     = $_POST['lat']     ?? '';
    $lng     = $_POST['lng']     ?? '';
    $name    = $_POST['name']    ?? '';
    $address = $_POST['address'] ?? '';
    if (!is_numeric($lat) || !is_numeric($lng)) {
        echo json_encode(['ok' => false, 'error' => 'Coordenadas inválidas']);
        exit;
    }
    $result = $client->sendLocation($to, (float)$lat, (float)$lng, $name, $address);
} elseif ($interactive) {
    $prefix = $subtype === 'consulta' ? 'consulta' : 'reclamo';
    $result = $client->sendInteractiveButtons($to, $text, [
        ['id' => $prefix . '_si', 'title' => 'Sí, responder'],
        ['id' => $prefix . '_no', 'title' => 'No, gracias'],
    ]);
} else {
    $result = $client->sendText($to, $text);
}
echo json_encode($result);
