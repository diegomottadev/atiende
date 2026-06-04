<?php
$title = $pageTitle ?? 'Pedidos Platform';
$module = $activeModule ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> — Pedidos Platform</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark px-3 mb-4">
  <span class="navbar-brand">Pedidos Platform</span>
  <?php if ($module === 'superadmin'): ?>
  <div class="d-flex gap-3">
    <a href="<?= APP_URL ?>/superadmin/dashboard.php" class="text-white text-decoration-none">Tenants</a>
    <a href="<?= APP_URL ?>/superadmin/planes.php" class="text-white text-decoration-none">Planes</a>
    <a href="<?= APP_URL ?>/superadmin/suscripciones.php" class="text-white text-decoration-none">Suscripciones</a>
    <a href="<?= APP_URL ?>/logout.php" class="text-white text-decoration-none">Salir</a>
  </div>
  <?php endif ?>
</nav>
<div class="container">
