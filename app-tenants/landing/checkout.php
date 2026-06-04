<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

$pdo    = getPlatformPDO();
$planId = (int)($_GET['plan'] ?? 0);
$plan   = $pdo->prepare('SELECT * FROM plans WHERE id = ? AND activo = 1');
$plan->execute([$planId]);
$planRow = $plan->fetch();
if (!$planRow) { header('Location: ' . APP_URL . '/landing/'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $empresa  = trim($_POST['empresa'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $provider = $_POST['provider'] ?? '';

    if (!$nombre || !$empresa || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($provider, ['mercadopago', 'stripe'])) {
        $error = 'Por favor completá todos los campos correctamente.';
    } else {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare(
            'INSERT INTO checkout_sessions (session_token, nombre, empresa, email, plan_id, provider) VALUES (?,?,?,?,?,?)'
        )->execute([$token, $nombre, $empresa, $email, $planId, $provider]);
        $sessionId = (int)$pdo->lastInsertId();

        if ($provider === 'stripe') {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $priceId = $planRow['stripe_price_id'] ?? null;
            $checkout = \Stripe\Checkout\Session::create([
                'mode'                 => 'subscription',
                'payment_method_types' => ['card'],
                'line_items'           => [['price' => $priceId, 'quantity' => 1]],
                'success_url'          => APP_URL . '/landing/success.php?token=' . $token,
                'cancel_url'           => APP_URL . '/landing/?canceled=1',
                'subscription_data'    => ['metadata' => ['session_token' => $token]],
                'customer_email'       => $email,
            ]);
            $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE id = ?')
                ->execute([$checkout->id, $sessionId]);
            header('Location: ' . $checkout->url);
            exit;
        } else {
            $payload = [
                'reason'         => $planRow['nombre'],
                'auto_recurring' => [
                    'frequency'          => 1,
                    'frequency_type'     => 'months',
                    'transaction_amount' => (float)$planRow['precio_ars'],
                    'currency_id'        => 'ARS',
                ],
                'payer_email'        => $email,
                'external_reference' => $token,
                'back_url'           => APP_URL . '/landing/success.php?token=' . $token,
            ];
            $ch = curl_init('https://api.mercadopago.com/preapproval');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $_ENV['MP_ACCESS_TOKEN'],
                ],
            ]);
            $resp = json_decode(curl_exec($ch), true);
            curl_close($ch);
            $initPoint = $resp['init_point'] ?? null;
            if ($initPoint) {
                $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE id = ?')
                    ->execute([$resp['id'] ?? '', $sessionId]);
                header('Location: ' . $initPoint);
                exit;
            }
            $error = 'Error al crear la suscripción. Intentá nuevamente.';
        }
    }
}

$pageTitle = 'Checkout';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2 class="mb-4">Suscribirse — <?= htmlspecialchars($planRow['nombre']) ?></h2>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
<form method="post" style="max-width:480px">
  <div class="mb-3"><label>Nombre completo</label>
    <input type="text" name="nombre" class="form-control" required></div>
  <div class="mb-3"><label>Nombre de tu empresa</label>
    <input type="text" name="empresa" class="form-control" required></div>
  <div class="mb-3"><label>Email</label>
    <input type="email" name="email" class="form-control" required></div>
  <div class="mb-3"><label>Método de pago</label>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="provider" value="mercadopago" id="mp" checked>
      <label class="form-check-label" for="mp">MercadoPago (ARS)</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="provider" value="stripe" id="st">
      <label class="form-check-label" for="st">Stripe (USD)</label>
    </div>
  </div>
  <button type="submit" class="btn btn-success">Ir al pago &rarr;</button>
</form>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
