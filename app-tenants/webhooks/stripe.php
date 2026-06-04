<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\ProvisioningService;
use App\MailService;
use Stripe\Webhook;
use Stripe\StripeClient;

$raw = file_get_contents('php://input');
try {
    $event = Webhook::constructEvent(
        $raw,
        $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '',
        $_ENV['STRIPE_WEBHOOK_SECRET']
    );
} catch (\Exception $e) {
    http_response_code(400);
    exit;
}

$pdo     = getPlatformPDO();
$eventId = $event->id;

// Idempotency — atomic INSERT IGNORE
$stmt = $pdo->prepare('INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)');
$stmt->execute(['stripe', $eventId, $event->type]);
if ($stmt->rowCount() === 0) {
    http_response_code(200);
    exit;
}

$svc    = new ProvisioningService();
$stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);

if ($event->type === 'invoice.payment_succeeded') {
    $invoice = $event->data->object;
    $subId   = $invoice->subscription;
    $reason  = $invoice->billing_reason;

    if ($reason === 'subscription_create') {
        $subscription = $stripe->subscriptions->retrieve($subId);
        $sessionToken = $subscription->metadata->session_token ?? '';
        if ($sessionToken) {
            $cs = $pdo->prepare('SELECT * FROM checkout_sessions WHERE session_token = ?');
            $cs->execute([$sessionToken]);
            $session = $cs->fetch();
            if ($session && $session['estado'] === 'pendiente') {
                $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE session_token = ?')
                    ->execute([$subId, $sessionToken]);
                $svc->provision($session['id']);
            }
        }
    } else {
        $sub = $pdo->prepare("SELECT tenant_id FROM subscriptions WHERE provider_subscription_id = ?");
        $sub->execute([$subId]);
        $s = $sub->fetch();
        if ($s) {
            $periodEnd = new DateTime('@' . $invoice->lines->data[0]->period->end);
            $svc->updatePeriodFin($s['tenant_id'], $periodEnd);
        }
    }
} elseif ($event->type === 'invoice.payment_failed') {
    $invoice = $event->data->object;
    $subId   = $invoice->subscription;
    $sub     = $pdo->prepare("SELECT * FROM subscriptions WHERE provider_subscription_id = ?");
    $sub->execute([$subId]);
    $subscription = $sub->fetch();
    if ($subscription) {
        $newCount = $subscription['payment_failure_count'] + 1;
        $pdo->prepare("UPDATE subscriptions SET payment_failure_count = ? WHERE id = ?")
            ->execute([$newCount, $subscription['id']]);
        if ($newCount >= 3) {
            $graceFin = date('Y-m-d H:i:s', strtotime('+3 days'));
            $pdo->prepare("UPDATE subscriptions SET grace_period_fin = ? WHERE id = ?")->execute([$graceFin, $subscription['id']]);
            $tenant = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
            $tenant->execute([$subscription['tenant_id']]);
            $t = $tenant->fetch();
            MailService::send($t['email'], 'Problema con tu suscripción', 'grace_period', [
                'empresa'     => $t['nombre'],
                'grace_until' => $graceFin,
            ]);
        }
    }
} elseif ($event->type === 'customer.subscription.deleted') {
    $subId = $event->data->object->id;
    $sub   = $pdo->prepare("SELECT * FROM subscriptions WHERE provider_subscription_id = ?");
    $sub->execute([$subId]);
    $subscription = $sub->fetch();
    if ($subscription) {
        $graceFin = date('Y-m-d H:i:s', strtotime('+3 days'));
        $pdo->prepare("UPDATE subscriptions SET estado='cancelada', grace_period_fin=? WHERE id=?")
            ->execute([$graceFin, $subscription['id']]);
        $tenant = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
        $tenant->execute([$subscription['tenant_id']]);
        $t = $tenant->fetch();
        MailService::send($t['email'], 'Suscripción cancelada', 'cancelacion', [
            'empresa'     => $t['nombre'],
            'periodo_fin' => $subscription['periodo_fin'],
        ]);
    }
}

$pdo->prepare('UPDATE webhook_events SET procesado = 1 WHERE provider = ? AND provider_event_id = ?')
    ->execute(['stripe', $eventId]);

http_response_code(200);
