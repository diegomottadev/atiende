<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();

if (APP_ENV !== 'development') {
    http_response_code(403);
    exit('Solo disponible en APP_ENV=development');
}

$pdo   = getPlatformPDO();
$plans = $pdo->query('SELECT id, nombre FROM plans WHERE activo = 1 ORDER BY id')->fetchAll();

$result = null;
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();

    $nombre   = trim($_POST['nombre']   ?? '');
    $empresa  = trim($_POST['empresa']  ?? '');
    $email    = trim($_POST['email']    ?? '');
    $planId   = (int)($_POST['plan_id'] ?? 0);
    $password = trim($_POST['password'] ?? 'dev1234');

    if ($nombre && $empresa && $email && $planId && $password) {
        try {
            // Generar slug único
            $svc  = new \App\ProvisioningService();
            $slug = $svc->devSlug($empresa);
            $dbName = 'atiende_' . $slug;

            $prov  = getProvisionerPDO();

            // 1. Crear base de datos del tenant
            $prov->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");

            // 2. Aplicar schema
            $sql = file_get_contents(ROOT . '/templates/sql/tenant_schema.sql');
            $prov->exec("USE `$dbName`");
            foreach (explode(';', $sql) as $stmt) {
                $s = trim($stmt);
                if ($s !== '') {
                    $prov->exec($s);
                }
            }

            // 3. Sembrar el menú default en la bot_config del tenant
            $menuJson = file_get_contents(ROOT . '/templates/bot/default_menu.json');
            $prov->prepare('INSERT INTO bot_config (id, menu_json) VALUES (1, ?)')
                 ->execute([$menuJson]);

            // 4. Usuario de plataforma
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                'INSERT INTO platform_users (tenant_id, email, password_hash, rol) VALUES (?,?,?,?)'
            )->execute([null, $email, $hash, 'tenant_admin']);
            $userId = (int)$pdo->lastInsertId();

            // 5. Tenant → activo directamente (saltamos pendiente_whatsapp)
            $pdo->prepare(
                'INSERT INTO tenants (slug, nombre, email, estado, db_name, plan_id) VALUES (?,?,?,?,?,?)'
            )->execute([$slug, $empresa, $email, 'activo', $dbName, $planId]);
            $tenantId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE platform_users SET tenant_id = ? WHERE id = ?')->execute([$tenantId, $userId]);

            // 6. Suscripción activa por 1 año
            $pdo->prepare(
                'INSERT INTO subscriptions (tenant_id, provider, provider_subscription_id, estado, periodo_fin)
                 VALUES (?,?,?,?,DATE_ADD(NOW(), INTERVAL 1 YEAR))'
            )->execute([$tenantId, 'stripe', 'dev_sub_' . $slug, 'activa']);

            // Crear usuario operador en la DB del tenant para acceder a Atiende
            $tenantMysql = new PDO(
                "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$dbName};charset=utf8",
                $_ENV['DB_USER'], $_ENV['DB_PASS'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $tenantMysql->prepare(
                "INSERT INTO usuario (nombre, tipo_documento, num_documento, login, clave, imagen, condicion, cargo)
                 VALUES (?, 'DNI', '0', ?, ?, '', 1, 'Administrador')"
            )->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $nuevoId = (int)$tenantMysql->lastInsertId();
            $insert  = $tenantMysql->prepare('INSERT INTO usuario_permiso (idusuario, idpermiso) VALUES (?,?)');
            // Asignar TODOS los permisos que existan en la tabla del tenant (incluye Configuración=11)
            $idsPermisos = $tenantMysql->query('SELECT idpermiso FROM permiso')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($idsPermisos as $permiso) {
                $insert->execute([$nuevoId, (int)$permiso]);
            }

            $result = [
                'tenant_id' => $tenantId,
                'slug'      => $slug,
                'db_name'   => $dbName,
                'email'     => $email,
                'password'  => $password,
            ];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Completá todos los campos.';
    }
}

$pageTitle    = '[DEV] Crear tenant';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="alert alert-warning d-flex align-items-center gap-2">
  <strong>Solo desarrollo</strong> — crea un tenant activo saltando el proceso de pago.
</div>

<h2>Crear tenant (dev)</h2>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif ?>

<?php if ($result): ?>
  <div class="alert alert-success">
    <h5 class="mb-3">✓ Tenant creado</h5>
    <table class="table table-sm mb-3" style="max-width:480px">
      <tr><th>Tenant ID</th><td><?= $result['tenant_id'] ?></td></tr>
      <tr><th>Slug</th><td><code><?= htmlspecialchars($result['slug']) ?></code></td></tr>
      <tr><th>Base de datos</th><td><code><?= htmlspecialchars($result['db_name']) ?></code></td></tr>
      <tr><th>Login (usuario)</th><td><code><?= htmlspecialchars($result['email']) ?></code></td></tr>
      <tr><th>Password</th><td><code><?= htmlspecialchars($result['password']) ?></code></td></tr>
    </table>
    <?php $atiendeUrl = tenantLoginUrl($result['slug']); ?>
    <p class="mb-1"><strong>Para entrar a Atiende con este tenant:</strong></p>
    <ol>
      <li>Ir a <a href="<?= htmlspecialchars($atiendeUrl) ?>" target="_blank"><?= htmlspecialchars($atiendeUrl) ?></a></li>
      <li>Empresa: <code><?= htmlspecialchars($result['slug']) ?></code> <span class="text-muted">(ya viene preseleccionada por el subdominio)</span></li>
      <li>Usuario: <code><?= htmlspecialchars($result['email']) ?></code></li>
      <li>Password: <code><?= htmlspecialchars($result['password']) ?></code></li>
    </ol>
    <a href="<?= htmlspecialchars($atiendeUrl) ?>" class="btn btn-sm btn-success" target="_blank">Abrir Atiende</a>
    <a href="<?= APP_URL ?>/superadmin/dashboard.php" class="btn btn-sm btn-outline-secondary ms-2">Ver en dashboard</a>
  </div>
<?php endif ?>

<form method="post" style="max-width:420px" class="mt-3">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <div class="mb-2">
    <label class="form-label">Nombre contacto</label>
    <input type="text" name="nombre" class="form-control" placeholder="Diego Motta" required>
  </div>
  <div class="mb-2">
    <label class="form-label">Empresa</label>
    <input type="text" name="empresa" class="form-control" placeholder="Mi Empresa SRL" required>
  </div>
  <div class="mb-2">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" placeholder="tenant@example.com" required>
  </div>
  <div class="mb-2">
    <label class="form-label">Plan</label>
    <select name="plan_id" class="form-select" required>
      <option value="">— elegir plan —</option>
      <?php foreach ($plans as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Password para el tenant</label>
    <input type="text" name="password" class="form-control" value="dev1234">
  </div>
  <button type="submit" class="btn btn-warning">Crear tenant sin pago</button>
</form>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
