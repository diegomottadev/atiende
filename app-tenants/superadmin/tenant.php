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

// --- Helpers de presentación ---------------------------------------------
$estadoMeta = [
  'pendiente_pago'     => ['bg-secondary',        'Pendiente de pago'],
  'pendiente_whatsapp' => ['bg-info text-dark',   'Pendiente de WhatsApp'],
  'activo'             => ['bg-success',          'Activo'],
  'suspendido'         => ['bg-warning text-dark','Suspendido'],
  'cancelado'          => ['bg-danger',           'Cancelado'],
];
[$estadoClass, $estadoLabel] = $estadoMeta[$tenant['estado']] ?? ['bg-secondary', ucfirst($tenant['estado'])];

$subMeta = [
  'activa'    => ['bg-success',           'Suscripción activa'],
  'vencida'   => ['bg-warning text-dark', 'Suscripción vencida'],
  'cancelada' => ['bg-danger',            'Suscripción cancelada'],
];

$modoLabel = ['mix' => 'Mixto (B2B + B2C)', 'b2b' => 'B2B', 'b2c' => 'B2C'][$tenant['tenant_mode'] ?? 'b2b'] ?? $tenant['tenant_mode'];

// Países soportados (mismo set que config/Telefono.php y la pestaña Empresa del tenant).
// [nombre, código telefónico] — el código es lo que se usa para normalizar WhatsApp.
$paises = [
  'AR' => ['Argentina', '54'], 'BR' => ['Brasil', '55'], 'MX' => ['México', '52'], 'UY' => ['Uruguay', '598'],
  'CL' => ['Chile', '56'], 'PY' => ['Paraguay', '595'], 'CO' => ['Colombia', '57'], 'PE' => ['Perú', '51'],
];
$paisTenant = strtoupper($tenant['pais'] ?? 'AR');
if (!isset($paises[$paisTenant])) { $paisTenant = 'AR'; }

// $atiendeUrl se deriva del dominio configurado vía tenantLoginUrl() más abajo.

include dirname(__DIR__) . '/views/layout/header.php';
?>

<?php $atiendeUrl = tenantLoginUrl($tenant['slug']); ?>
<!-- ===== Cabecera ===== -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
  <div>
    <div class="text-muted small mb-1"><a href="<?= APP_URL ?>/superadmin/dashboard.php" class="text-decoration-none">Tenants</a> / #<?= $tenantId ?></div>
    <h2 class="mb-1 d-flex align-items-center gap-2">
      <?= htmlspecialchars($tenant['nombre']) ?>
      <span class="badge <?= $estadoClass ?> align-middle"><?= htmlspecialchars($estadoLabel) ?></span>
    </h2>
    <div class="text-muted">
      <?= htmlspecialchars($tenant['email']) ?>
      · Slug <code><?= htmlspecialchars($tenant['slug']) ?></code>
      · Modo <span class="badge bg-light text-dark border"><?= htmlspecialchars($modoLabel) ?></span>
    </div>
  </div>
  <a href="<?= htmlspecialchars($atiendeUrl) ?>" target="_blank" class="btn btn-success">Abrir Atiende ↗</a>
</div>

<!-- ===== Avisos ===== -->
<?php if ($success === '1'): ?><div class="alert alert-success">Credenciales guardadas.</div><?php endif ?>
<?php if ($success === '2'): ?><div class="alert alert-success">Datos del tenant guardados.</div><?php endif ?>
<?php if ($success === '3'): ?><div class="alert alert-success">Contraseña actualizada.</div><?php endif ?>
<?php if ($err === 'datos'): ?><div class="alert alert-danger">Revisá los datos: el nombre es obligatorio y el email debe ser válido.</div><?php endif ?>
<?php if ($err === 'clave'): ?><div class="alert alert-danger">La contraseña debe tener al menos 4 caracteres.</div><?php endif ?>

<!-- ===== Resumen / Acceso ===== -->
<div class="row g-3 mb-4">
  <div class="col-md-7">
    <div class="card h-100">
      <div class="card-header bg-light fw-semibold">Acceso al sistema</div>
      <div class="card-body">
        <div class="mb-2"><a href="<?= htmlspecialchars($atiendeUrl) ?>" target="_blank" class="fw-semibold"><?= htmlspecialchars($atiendeUrl) ?></a></div>
        <div class="text-muted small">El subdominio preselecciona el tenant <code><?= htmlspecialchars($tenant['slug']) ?></code>.</div>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card h-100">
      <div class="card-header bg-light fw-semibold">Suscripción</div>
      <div class="card-body">
        <?php if ($subscription): ?>
          <?php [$sc, $sl] = $subMeta[$subscription['estado']] ?? ['bg-secondary', ucfirst($subscription['estado'])]; ?>
          <div class="mb-2"><span class="badge <?= $sc ?>"><?= htmlspecialchars($sl) ?></span></div>
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted fw-normal">Proveedor</dt>
            <dd class="col-7 text-capitalize"><?= htmlspecialchars($subscription['provider']) ?></dd>
            <dt class="col-5 text-muted fw-normal">Periodo hasta</dt>
            <dd class="col-7"><?= htmlspecialchars($subscription['periodo_fin']) ?></dd>
            <?php if (!empty($subscription['grace_period_fin'])): ?>
            <dt class="col-5 text-muted fw-normal">Gracia hasta</dt>
            <dd class="col-7"><?= htmlspecialchars($subscription['grace_period_fin']) ?></dd>
            <?php endif ?>
            <?php if ((int)($subscription['payment_failure_count'] ?? 0) > 0): ?>
            <dt class="col-5 text-muted fw-normal">Pagos fallidos</dt>
            <dd class="col-7 text-danger"><?= (int)$subscription['payment_failure_count'] ?></dd>
            <?php endif ?>
          </dl>
        <?php else: ?>
          <span class="text-muted">Sin suscripción registrada.</span>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>

<!-- ===== Datos del tenant + Credenciales WhatsApp ===== -->
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-light fw-semibold">Datos del tenant</div>
      <div class="card-body">
        <form method="post" action="tenant_update.php" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
          <div class="mb-2"><label class="form-label">Nombre / Empresa</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($tenant['nombre']) ?>" required autocomplete="off"></div>
          <div class="mb-2"><label class="form-label">Email de contacto</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($tenant['email']) ?>" required autocomplete="off"></div>
          <div class="mb-2"><label class="form-label">Razón social</label>
            <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($tenant['razon_social'] ?? '') ?>" autocomplete="off"></div>
          <div class="mb-2"><label class="form-label">País</label>
            <select name="pais" class="form-select">
              <?php foreach ($paises as $code => [$nombre, $cc]): ?>
                <option value="<?= $code ?>" <?= $code === $paisTenant ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?> (+<?= $cc ?>)</option>
              <?php endforeach ?>
            </select>
            <div class="form-text">Define cómo se normalizan los números de WhatsApp. Se sincroniza con la config del tenant (<code>bot_config.pais</code>).</div></div>
          <div class="row g-2 mb-2">
            <div class="col-sm-6"><label class="form-label">CUIT</label>
              <input type="text" name="cuit" class="form-control" value="<?= htmlspecialchars($tenant['cuit'] ?? '') ?>" autocomplete="off"></div>
            <div class="col-sm-6"><label class="form-label">Teléfono</label>
              <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($tenant['telefono'] ?? '') ?>" autocomplete="off"></div>
          </div>
          <div class="mb-3"><label class="form-label">Slug (subdominio)</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($tenant['slug']) ?>" disabled>
            <div class="form-text">No editable: define el subdominio y la base de datos del tenant.</div></div>
          <button type="submit" class="btn btn-primary">Guardar datos</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-light fw-semibold">Credenciales de WhatsApp</div>
      <div class="card-body">
        <form method="post" action="tenant_save.php" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
          <div class="mb-3"><label class="form-label">Phone Number ID</label>
            <input type="text" name="phone_id" class="form-control" value="<?= htmlspecialchars($tenant['whatsapp_phone_id'] ?? '') ?>" autocomplete="off"></div>

          <div class="mb-3"><label class="form-label">Access Token</label>
            <?php if ($tenant['whatsapp_token_enc']): ?>
              <div class="input-group">
                <input type="text" class="form-control" value="<?= htmlspecialchars(Encryption::mask(Encryption::decrypt($tenant['whatsapp_token_enc']))) ?>" readonly id="tokenMasked">
                <input type="text" name="token" class="form-control d-none" id="tokenInput"
                       data-real="<?= htmlspecialchars(Encryption::decrypt($tenant['whatsapp_token_enc'])) ?>"
                       autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary" onclick="revealSecret('tokenMasked','tokenInput',this)">Editar</button>
              </div>
              <div class="form-text">Mostrá el valor actual con «Editar» para verificarlo o reemplazarlo. Vacío = no cambia.</div>
            <?php else: ?>
              <input type="password" name="token" class="form-control" autocomplete="new-password">
            <?php endif ?>
          </div>

          <div class="mb-3"><label class="form-label">App Secret <span class="text-muted fw-normal">(clave secreta de la app Meta)</span></label>
            <?php if ($tenant['whatsapp_app_secret_enc']): ?>
              <div class="input-group">
                <input type="text" class="form-control" value="<?= htmlspecialchars(Encryption::mask(Encryption::decrypt($tenant['whatsapp_app_secret_enc']))) ?>" readonly id="secretMasked">
                <input type="text" name="app_secret" class="form-control d-none" id="secretInput"
                       data-real="<?= htmlspecialchars(Encryption::decrypt($tenant['whatsapp_app_secret_enc'])) ?>"
                       autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary" onclick="revealSecret('secretMasked','secretInput',this)">Editar</button>
              </div>
              <div class="form-text">Mostrá el valor actual con «Editar» para verificarlo o reemplazarlo. Vacío = no cambia.</div>
            <?php else: ?>
              <input type="password" name="app_secret" class="form-control" autocomplete="new-password">
            <?php endif ?>
          </div>

          <button type="submit" class="btn btn-primary">Guardar credenciales</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ===== Acciones de estado ===== -->
<?php
$accionDisponible = ($tenant['estado'] === 'pendiente_whatsapp' && $tenant['whatsapp_phone_id'] && $tenant['whatsapp_token_enc'] && $tenant['whatsapp_app_secret_enc'])
  || in_array($tenant['estado'], ['activo', 'suspendido'], true);
?>
<?php if ($accionDisponible): ?>
<div class="card mb-4">
  <div class="card-header bg-light fw-semibold">Acciones de estado</div>
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
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
</div>
<?php endif ?>
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
<div class="card mb-4">
  <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
    <span>Usuarios de Atiende</span>
    <span class="badge bg-secondary"><?= count($tenantUsers) ?></span>
  </div>
  <div class="card-body">
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

<form method="post" action="tenant_password_save.php" class="d-flex align-items-end gap-2 mb-3 ms-1" style="max-width:520px" autocomplete="off">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
  <input type="hidden" name="idusuario" value="<?= $u['idusuario'] ?>">
  <div class="flex-grow-1">
    <label class="form-label small mb-1">Nueva contraseña para <code><?= htmlspecialchars($u['login']) ?></code></label>
    <input type="text" name="nueva_clave" class="form-control form-control-sm" minlength="4" autocomplete="new-password" required>
  </div>
  <button type="submit" class="btn btn-sm btn-warning">Cambiar contraseña</button>
</form>
<?php endforeach ?>
  </div>
</div>
<?php endif ?>

<script>
// Revela y precarga el valor real del secreto en el input editable del tenant.
function revealSecret(maskedId, inputId, btn) {
  var masked = document.getElementById(maskedId);
  var input  = document.getElementById(inputId);
  if (!masked || !input) return;
  masked.classList.add('d-none');
  input.classList.remove('d-none');
  if (!input.value) { input.value = input.dataset.real || ''; }
  input.focus();
  if (btn) { btn.disabled = true; }
}
</script>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
