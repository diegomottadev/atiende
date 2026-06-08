<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\Encryption;
Auth::requireSuperadmin();

$pdo      = getPlatformPDO();
$tenantId = (int)($_GET['id'] ?? 0);
$stmt     = $pdo->prepare('SELECT * FROM tenants WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();
if (!$tenant) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

$sub = $pdo->prepare('SELECT * FROM subscriptions WHERE tenant_id = ? AND deleted_at IS NULL');
$sub->execute([$tenantId]);
$subscription = $sub->fetch();

$success = $_GET['saved'] ?? '';
$err     = $_GET['err'] ?? '';

$pageTitle    = 'Tenant: ' . $tenant['nombre'];
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2><?= htmlspecialchars($tenant['nombre']) ?>
  <span class="badge bg-secondary"><?= htmlspecialchars($tenant['estado']) ?></span>
</h2>
<p class="text-muted"><?= htmlspecialchars($tenant['email']) ?> · Slug: <code><?= htmlspecialchars($tenant['slug']) ?></code></p>

<?php $atiendeUrl = tenantLoginUrl($tenant['slug']); ?>
<div class="alert alert-light border d-flex align-items-center justify-content-between flex-wrap gap-2" style="max-width:640px">
  <div>
    <strong>Acceso al sistema</strong><br>
    <a href="<?= htmlspecialchars($atiendeUrl) ?>" target="_blank"><?= htmlspecialchars($atiendeUrl) ?></a>
    <div class="text-muted small">El subdominio preselecciona el tenant <code><?= htmlspecialchars($tenant['slug']) ?></code>.</div>
  </div>
  <a href="<?= htmlspecialchars($atiendeUrl) ?>" target="_blank" class="btn btn-success btn-sm">Abrir Atiende</a>
</div>

<?php if ($success === '1'): ?><div class="alert alert-success">Credenciales guardadas.</div><?php endif ?>
<?php if ($success === '2'): ?><div class="alert alert-success">Datos del tenant guardados.</div><?php endif ?>
<?php if ($success === '3'): ?><div class="alert alert-success">Contraseña actualizada.</div><?php endif ?>
<?php if ($err === 'datos'): ?><div class="alert alert-danger">Revisá los datos: el nombre es obligatorio y el email debe ser válido.</div><?php endif ?>
<?php if ($err === 'clave'): ?><div class="alert alert-danger">La contraseña debe tener al menos 4 caracteres.</div><?php endif ?>

<h5 class="mt-4">Datos del tenant</h5>
<form method="post" action="tenant_update.php" style="max-width:480px">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
  <div class="mb-2"><label class="form-label">Nombre / Empresa</label>
    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($tenant['nombre']) ?>" required></div>
  <div class="mb-2"><label class="form-label">Email de contacto</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($tenant['email']) ?>" required></div>
  <div class="mb-2"><label class="form-label">Razón social</label>
    <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($tenant['razon_social'] ?? '') ?>" placeholder="Mi Empresa S.R.L."></div>
  <div class="mb-2"><label class="form-label">CUIT</label>
    <input type="text" name="cuit" class="form-control" value="<?= htmlspecialchars($tenant['cuit'] ?? '') ?>" placeholder="30-12345678-9"></div>
  <div class="mb-2"><label class="form-label">Teléfono</label>
    <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($tenant['telefono'] ?? '') ?>" placeholder="3764000000"></div>
  <div class="mb-2"><label class="form-label">Slug (subdominio)</label>
    <input type="text" class="form-control" value="<?= htmlspecialchars($tenant['slug']) ?>" disabled>
    <div class="form-text">No editable: define el subdominio y la base de datos del tenant.</div></div>
  <button type="submit" class="btn btn-primary">Guardar datos</button>
</form>

<h5 class="mt-4">Credenciales de WhatsApp</h5>
<form method="post" action="tenant_save.php">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
  <div class="mb-3"><label>Phone Number ID</label>
    <input type="text" name="phone_id" class="form-control" value="<?= htmlspecialchars($tenant['whatsapp_phone_id'] ?? '') ?>"></div>
<div class="mb-3"><label>Access Token</label>
    <?php if ($tenant['whatsapp_token_enc']): ?>
      <div class="input-group">
        <input type="text" class="form-control" value="<?= Encryption::mask(Encryption::decrypt($tenant['whatsapp_token_enc'])) ?>" readonly id="tokenMasked">
        <input type="password" name="token" class="form-control d-none" id="tokenInput" placeholder="Nuevo token (dejar vacío para no cambiar)">
        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('tokenMasked').classList.toggle('d-none');document.getElementById('tokenInput').classList.toggle('d-none')">Editar</button>
      </div>
    <?php else: ?>
      <input type="password" name="token" class="form-control" placeholder="Access Token de Meta">
    <?php endif ?>
  </div>
  <div class="mb-3"><label>App Secret (clave secreta de la app Meta)</label>
    <?php if ($tenant['whatsapp_app_secret_enc']): ?>
      <div class="input-group">
        <input type="text" class="form-control" value="<?= Encryption::mask(Encryption::decrypt($tenant['whatsapp_app_secret_enc'])) ?>" readonly id="secretMasked">
        <input type="password" name="app_secret" class="form-control d-none" id="secretInput" placeholder="Nuevo secret (dejar vacío para no cambiar)">
        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('secretMasked').classList.toggle('d-none');document.getElementById('secretInput').classList.toggle('d-none')">Editar</button>
      </div>
    <?php else: ?>
      <input type="password" name="app_secret" class="form-control" placeholder="App Secret de Meta">
    <?php endif ?>
  </div>
  <button type="submit" class="btn btn-primary">Guardar credenciales</button>
</form>

<div class="mt-4 d-flex gap-2">
<?php if ($tenant['estado'] === 'pendiente_whatsapp' && $tenant['whatsapp_phone_id'] && $tenant['whatsapp_token_enc'] && $tenant['whatsapp_app_secret_enc']): ?>
  <form method="post" action="activate.php">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
    <button type="submit" class="btn btn-success">Activar tenant</button>
  </form>
<?php endif ?>
<?php if ($tenant['estado'] === 'activo'): ?>
  <form method="post" action="suspend.php">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
    <button type="submit" class="btn btn-warning" onclick="return confirm('¿Suspender?')">Suspender</button>
  </form>
<?php elseif ($tenant['estado'] === 'suspendido'): ?>
  <form method="post" action="reactivate.php">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
    <button type="submit" class="btn btn-success">Reactivar</button>
  </form>
  <?php if ($subscription && $subscription['estado'] === 'cancelada'): ?>
  <a href="teardown.php?id=<?= $tenantId ?>" class="btn btn-danger">Teardown</a>
  <?php endif ?>
<?php endif ?>
</div>
<?php
// Conectar a la DB del tenant para mostrar sus usuarios
$tenantUsers = [];
$permisosDisponibles = [];
try {
    $tenantMysql = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$tenant['db_name']};charset=utf8",
        $_ENV['DB_USER'], $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $tenantUsers = $tenantMysql->query(
        'SELECT idusuario, nombre, login, condicion FROM usuario ORDER BY idusuario'
    )->fetchAll();
    $permisosDisponibles = $tenantMysql->query(
        'SELECT idpermiso, nombre FROM permiso ORDER BY idpermiso'
    )->fetchAll();
    foreach ($tenantUsers as &$u) {
        $st = $tenantMysql->prepare('SELECT idpermiso FROM usuario_permiso WHERE idusuario = ?');
        $st->execute([$u['idusuario']]);
        $u['permisos'] = array_column($st->fetchAll(), 'idpermiso');
    }
    unset($u);
} catch (\Throwable $e) {
    // DB del tenant aún no accesible
}
?>
<?php if ($tenantUsers): ?>
<hr class="mt-4">
<h5>Usuarios de Atiende</h5>
<p class="text-muted small">Permiso <em>Panel de control</em> = escritorio, <em>Seguridad</em> = acceso a la sección Seguridad/Usuarios, etc.</p>

<?php foreach ($tenantUsers as $u): ?>
<form method="post" action="tenant_permissions_save.php" class="border rounded p-3 mb-3">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
  <input type="hidden" name="db_name" value="<?= htmlspecialchars($tenant['db_name']) ?>">
  <input type="hidden" name="idusuario" value="<?= $u['idusuario'] ?>">

  <div class="d-flex align-items-center gap-3 mb-2">
    <strong><?= htmlspecialchars($u['nombre']) ?></strong>
    <code class="text-muted"><?= htmlspecialchars($u['login']) ?></code>
    <?php if (!$u['condicion']): ?><span class="badge bg-secondary">Desactivado</span><?php endif ?>
  </div>

  <div class="row row-cols-2 row-cols-md-3 g-1 mb-2">
    <?php foreach ($permisosDisponibles as $p): ?>
    <div class="col">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="permisos[]"
               value="<?= $p['idpermiso'] ?>"
               id="p_<?= $u['idusuario'] ?>_<?= $p['idpermiso'] ?>"
               <?= in_array($p['idpermiso'], $u['permisos']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="p_<?= $u['idusuario'] ?>_<?= $p['idpermiso'] ?>">
          <?= htmlspecialchars($p['nombre']) ?>
        </label>
      </div>
    </div>
    <?php endforeach ?>
  </div>

  <button type="submit" class="btn btn-sm btn-primary">Guardar permisos</button>
</form>

<form method="post" action="tenant_password_save.php" class="d-flex align-items-end gap-2 mb-3 ms-1" style="max-width:520px">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
  <input type="hidden" name="idusuario" value="<?= $u['idusuario'] ?>">
  <div class="flex-grow-1">
    <label class="form-label small mb-1">Nueva contraseña para <code><?= htmlspecialchars($u['login']) ?></code></label>
    <input type="text" name="nueva_clave" class="form-control form-control-sm" placeholder="Mínimo 4 caracteres" minlength="4" autocomplete="off" required>
  </div>
  <button type="submit" class="btn btn-sm btn-warning">Cambiar contraseña</button>
</form>
<?php endforeach ?>
<?php endif ?>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
