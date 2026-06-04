<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
$pageTitle = 'Pago recibido';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="text-center py-5">
  <h2>¡Gracias por tu suscripción!</h2>
  <p class="lead">Estamos procesando tu pago. Recibirás un email con tus credenciales de acceso en los próximos minutos.</p>
  <a href="<?= APP_URL ?>/landing/" class="btn btn-outline-primary mt-3">Volver al inicio</a>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
