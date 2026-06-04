<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\ProvisioningService;
Auth::requireSuperadmin();

$pdo      = getPlatformPDO();
$tenantId = (int)($_GET['id'] ?? $_POST['tenant_id'] ?? 0);
$stmt     = $pdo->prepare('SELECT slug, nombre FROM tenants WHERE id = ?');
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();
if (!$tenant) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

$svc = new ProvisioningService();

// Dry-run: previsualiza el plan y valida precondiciones (suscripción
// cancelada) ANTES de que el operador escriba el slug. Si no se puede,
// $plan queda null y se muestra el motivo en vez del formulario.
$plan       = null;
$blockReason = '';
try {
    $plan = $svc->teardown($tenantId, true);
} catch (\Throwable $e) {
    $blockReason = $e->getMessage();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    if (!$plan) {
        $error = 'No se puede ejecutar el teardown: ' . $blockReason;
    } elseif ($_POST['confirm_slug'] !== $tenant['slug']) {
        $error = 'El slug ingresado no coincide.';
    } else {
        $svc->teardown($tenantId);
        header('Location: ' . APP_URL . '/superadmin/dashboard.php?torn=1');
        exit;
    }
}

$pageTitle    = 'Teardown';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="card border-danger" style="max-width:480px">
  <div class="card-header bg-danger text-white">Teardown — <?= htmlspecialchars($tenant['nombre']) ?></div>
  <div class="card-body">
    <p>Esta acción es <strong>irreversible</strong>. Eliminará la base de datos del tenant y todos sus datos.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
    <?php if (!$plan): ?>
      <div class="alert alert-warning mb-0">
        No se puede dar de baja este tenant todavía: <strong><?= htmlspecialchars($blockReason) ?></strong>
      </div>
      <a href="tenant.php?id=<?= $tenantId ?>" class="btn btn-secondary mt-3">Volver</a>
    <?php else: ?>
      <p class="mb-1"><strong>Se ejecutará (dry-run):</strong></p>
      <ul class="small text-muted">
        <?php foreach ($plan['acciones'] as $accion): ?>
          <li><code><?= htmlspecialchars($accion) ?></code></li>
        <?php endforeach ?>
      </ul>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
        <div class="mb-3"><label>Escribí el slug <code><?= htmlspecialchars($tenant['slug']) ?></code> para confirmar</label>
          <input type="text" name="confirm_slug" class="form-control" required></div>
        <button type="submit" class="btn btn-danger">Confirmar teardown</button>
        <a href="tenant.php?id=<?= $tenantId ?>" class="btn btn-secondary ms-2">Cancelar</a>
      </form>
    <?php endif ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
