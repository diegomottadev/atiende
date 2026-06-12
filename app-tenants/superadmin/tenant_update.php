<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$pdo      = getPlatformPDO();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email']  ?? '');
$razon    = trim($_POST['razon_social'] ?? '');
$cuit     = trim($_POST['cuit']     ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$pais     = strtoupper(trim($_POST['pais'] ?? 'AR'));

// Países soportados (mismo set que config/Telefono.php y la pestaña Empresa del tenant).
$PAISES = ['AR', 'BR', 'MX', 'UY', 'CL', 'PY', 'CO', 'PE'];
if (!in_array($pais, $PAISES, true)) { $pais = 'AR'; }

$stmt = $pdo->prepare('SELECT db_name FROM tenants WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$tenantId]);
$row = $stmt->fetch();
if (!$row) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

// Validación: nombre obligatorio, email válido. (slug y db_name NO se editan:
// definen el subdominio y la base de datos del tenant.)
if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&err=datos');
    exit;
}

$pdo->prepare('UPDATE tenants SET nombre = ?, email = ?, razon_social = ?, cuit = ?, telefono = ?, pais = ? WHERE id = ?')
    ->execute([$nombre, $email, $razon, $cuit, $telefono, $pais, $tenantId]);

// Sincronizar el país a la DB del tenant (bot_config.pais), igual que la pestaña
// Empresa del propio tenant. Best-effort: si la DB no está accesible o no tiene la
// columna, el guardado a nivel plataforma ya quedó hecho.
if (!empty($row['db_name'])) {
    try {
        $tdb = new PDO(
            "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$row['db_name']};charset=utf8",
            $_ENV['DB_USER'], $_ENV['DB_PASS'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $tdb->prepare('UPDATE bot_config SET pais = ? WHERE id = 1')->execute([$pais]);
    } catch (\Throwable $e) {
        // DB del tenant inaccesible o sin columna pais — se ignora.
    }
}

header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&saved=2');
exit;
