<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$pdo       = getPlatformPDO();
$tenantId  = (int)($_POST['tenant_id'] ?? 0);
$idusuario = (int)($_POST['idusuario'] ?? 0);
$nueva     = trim($_POST['nueva_clave'] ?? '');

// db_name se toma de la DB (no del POST) para evitar apuntar a otra base.
$stmt = $pdo->prepare('SELECT db_name FROM tenants WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$tenantId]);
$row = $stmt->fetch();
if (!$row) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

if (strlen($nueva) < 4 || $idusuario <= 0) {
    header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&err=clave');
    exit;
}

// La contraseña se guarda hasheada (el login de Atiende usa password_verify).
$tenantMysql = new PDO(
    "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$row['db_name']};charset=utf8",
    $_ENV['DB_USER'], $_ENV['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$tenantMysql->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?')
    ->execute([password_hash($nueva, PASSWORD_DEFAULT), $idusuario]);

header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&saved=3');
exit;
