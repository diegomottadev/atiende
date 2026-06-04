<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
$plans = getPlatformPDO()->query('SELECT * FROM plans WHERE activo = 1 ORDER BY precio_ars ASC')->fetchAll();
$pageTitle = 'Planes';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2 class="mb-4">Elegí tu plan</h2>
<div class="row g-4">
<?php foreach ($plans as $plan): ?>
<div class="col-md-4">
  <div class="card h-100 shadow-sm">
    <div class="card-body">
      <h5 class="card-title"><?= htmlspecialchars($plan['nombre']) ?></h5>
      <p class="display-6">$<?= number_format($plan['precio_ars'], 0, ',', '.') ?> ARS</p>
      <p class="text-muted">USD <?= number_format($plan['precio_usd'], 2) ?></p>
    </div>
    <div class="card-footer bg-transparent">
      <a href="checkout.php?plan=<?= $plan['id'] ?>" class="btn btn-primary w-100">Contratar</a>
    </div>
  </div>
</div>
<?php endforeach ?>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
