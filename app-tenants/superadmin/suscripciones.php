<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();

$pdo      = getPlatformPDO();
$tenantId = (int)($_GET['tenant'] ?? 0);
$where    = $tenantId ? 'WHERE s.tenant_id = ' . $tenantId : '';
$subs     = $pdo->query(
    "SELECT s.*, t.nombre AS empresa FROM subscriptions s
     JOIN tenants t ON t.id = s.tenant_id
     $where ORDER BY s.created_at DESC LIMIT 100"
)->fetchAll();

$pageTitle    = 'Suscripciones';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Historial de suscripciones</h2>
<table class="table mt-3">
  <thead><tr><th>Empresa</th><th>Proveedor</th><th>Estado</th><th>Vence</th><th>Reintentos</th><th>Alta</th></tr></thead>
  <tbody>
  <?php foreach ($subs as $s): ?>
  <tr>
    <td><?= htmlspecialchars($s['empresa']) ?></td>
    <td><?= htmlspecialchars($s['provider']) ?></td>
    <td><?= htmlspecialchars($s['estado']) ?></td>
    <td><?= $s['periodo_fin'] ? date('d/m/Y', strtotime($s['periodo_fin'])) : '-' ?></td>
    <td><?= $s['payment_failure_count'] ?></td>
    <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
