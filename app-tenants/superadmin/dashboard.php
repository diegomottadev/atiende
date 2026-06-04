<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();

$pdo     = getPlatformPDO();
$tenants = $pdo->query(
    "SELECT t.*, p.nombre AS plan_nombre, s.periodo_fin, s.estado AS sub_estado
     FROM tenants t
     JOIN plans p ON p.id = t.plan_id
     LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.deleted_at IS NULL
     WHERE t.deleted_at IS NULL
     ORDER BY t.created_at DESC"
)->fetchAll();

$pending = array_filter($tenants, fn($t) => $t['estado'] === 'pendiente_whatsapp');

$pageTitle    = 'Dashboard';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<?php if (APP_ENV === 'development'): ?>
<a href="dev_provision.php" class="btn btn-warning btn-sm mb-3">+ Crear tenant (dev, sin pago)</a>
<?php endif ?>
<h2>Tenants <?php if (count($pending)): ?>
  <span class="badge bg-warning text-dark"><?= count($pending) ?> pendiente(s) WhatsApp</span>
<?php endif ?></h2>
<table class="table table-striped mt-3">
  <thead><tr>
    <th>Empresa</th><th>Email</th><th>Plan</th><th>Estado</th><th>Sub</th><th>Vence</th><th></th>
  </tr></thead>
  <tbody>
  <?php foreach ($tenants as $t): ?>
  <tr>
    <td><?= htmlspecialchars($t['nombre']) ?></td>
    <td><?= htmlspecialchars($t['email']) ?></td>
    <td><?= htmlspecialchars($t['plan_nombre']) ?></td>
    <td><span class="badge bg-<?= match($t['estado']) {
      'activo' => 'success', 'suspendido' => 'danger',
      'pendiente_whatsapp' => 'warning', default => 'secondary'} ?>">
      <?= htmlspecialchars($t['estado']) ?></span></td>
    <td><?= htmlspecialchars($t['sub_estado'] ?? '-') ?></td>
    <td><?= $t['periodo_fin'] ? date('d/m/Y', strtotime($t['periodo_fin'])) : '-' ?></td>
    <td><a href="tenant.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
