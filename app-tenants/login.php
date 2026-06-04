<?php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
use App\Auth;

Auth::start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    if (Auth::attempt($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: ' . APP_URL . '/superadmin/');
        exit;
    }
    $error = 'Email o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Login — Pedidos Platform</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container" style="max-width:400px;margin-top:100px">
  <h4 class="mb-4 text-center">Pedidos Platform</h4>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <div class="mb-3"><label>Email</label>
      <input type="email" name="email" class="form-control" required autofocus></div>
    <div class="mb-3"><label>Contraseña</label>
      <input type="password" name="password" class="form-control" required></div>
    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
  </form>
</div>
</body></html>
