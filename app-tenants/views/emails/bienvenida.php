<h2>¡Bienvenido a Pedidos Platform!</h2>
<p>Tu cuenta para <strong><?= htmlspecialchars($empresa) ?></strong> ha sido creada.</p>
<p>Accedé al portal con estas credenciales:</p>
<ul>
  <li><strong>URL:</strong> <a href="<?= APP_URL ?>/portal/"><?= APP_URL ?>/portal/</a></li>
  <li><strong>Email:</strong> <?= htmlspecialchars($email) ?></li>
  <li><strong>Contraseña temporal:</strong> <?= htmlspecialchars($password) ?></li>
</ul>
<p>En breve activaremos tu número de WhatsApp y recibirás otro email de confirmación.</p>
