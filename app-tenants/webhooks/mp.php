<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\ProvisioningService;
use App\MailService;

http_response_code(200); // Always respond 200 to MP

$raw     = file_get_contents('php://input');
$headers = getallheaders();

// Validate HMAC-SHA256 signature
$xSignature = $headers['X-Signature'] ?? '';
$xRequestId = $headers['X-Request-Id'] ?? '';
$signedPayload = "id:{$_GET['id'] ?? ''};request-id:$xRequestId;ts:{$_GET['ts'] ?? ''}";
$expectedSig = hash_hmac('sha256', $signedPayload, $_ENV['MP_WEBHOOK_SECRET']);
$parts = [];
foreach (explode(';', $xSignature) as $part) {
    [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
    $parts[$k] = $v;
}
if (!hash_equals($expectedSig, $parts['v1'] ?? '')) {
    error_log('[MP Webhook] Invalid signature');
    exit;
}

$payload = json_decode($raw, true);
$type    = $payload['type'] ?? '';
$eventId = $payload['id'] ?? '';
if (!$eventId) exit;

// Idempotency — atomic INSERT IGNORE
$pdo  = getPlatformPDO();
$stmt = $pdo->prepare('INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)');
$stmt->execute(['mercadopago', (string)$eventId, $type]);
if ($stmt->rowCount() === 0) exit; // Already processed

$svc = new ProvisioningService();

if ($type === 'subscription_preapproval') {
    $status        = $payload['data']['status'] ?? '';
    $preapprovalId = $payload['data']['id'] ?? '';
    $mpData = json_decode(file_get_contents(
        "https://api.mercadopago.com/preapproval/$preapprovalId",
        false,
        stream_context_create(['http' => ['header' => 'Authorization: Bearer ' . $_ENV['MP_ACCESS_TOKEN']]])
    ), true);
    $sessionToken = $mpData['external_reference'] ?? '';
    if ($status === 'authorized' && $sessionToken) {
        $cs = $pdo->prepare('SELECT * FROM checkout_sessions WHERE session_token = ?');
        $cs->execute([$sessionToken]);
        $session = $cs->fetch();
        if ($session && $session['estado'] === 'pendiente') {
            $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE session_token = ?')
                ->execute([$preapprovalId, $sessionToken]);
            $svc->provision($session['id']);
        }
    } elseif ($status === 'cancelled') {
        $sub = $pdo->prepare("SELECT * FROM subscriptions WHERE provider_subscription_id = ?");
        $sub->execute([$preapprovalId]);
        $subscription = $sub->fetch();
        if ($subscription) {
            $graceFin = date('Y-m-d H:i:s', strtotime('+3 days'));
            $pdo->prepare("UPDATE subscriptions SET estado='cancelada', grace_period_fin=? WHERE id=?")
                ->execute([$graceFin, $subscription['id']]);
            $tenant = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
            $tenant->execute([$subscription['tenant_id']]);
            $t = $tenant->fetch();
            MailService::send($t['email'], 'Problema con tu suscripción', 'grace_period', [
                'empresa'     => $t['nombre'],
                'grace_until' => $graceFin,
            ]);
        }
    }
} elseif ($type === 'subscription_authorized_payment') {
    $status        = $payload['data']['status'] ?? '';
    $preapprovalId = $payload['data']['preapproval_id'] ?? '';
    if ($status === 'processed') {
        $sub = $pdo->prepare("SELECT tenant_id FROM subscriptions WHERE provider_subscription_id = ?");
        $sub->execute([$preapprovalId]);
        $s = $sub->fetch();
        if ($s) {
            $svc->updatePeriodFin($s['tenant_id'], new DateTime('+1 month'));
        }
    }
}
