<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$tenantId  = (int)($_POST['tenant_id'] ?? 0);
$dbName    = basename(trim($_POST['db_name'] ?? ''));
$idusuario = (int)($_POST['idusuario'] ?? 0);
$permisos  = array_map('intval', $_POST['permisos'] ?? []);

if (!$tenantId || !$dbName || !$idusuario || !preg_match('/^atiende_[a-z0-9_]+$/', $dbName)) {
    http_response_code(400);
    exit('Datos inválidos');
}

// Verificar que el tenant pertenece a este tenantId
$pdo  = getPlatformPDO();
$stmt = $pdo->prepare('SELECT id FROM tenants WHERE id = ? AND db_name = ? AND deleted_at IS NULL');
$stmt->execute([$tenantId, $dbName]);
if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Tenant no encontrado');
}

$tenantMysql = new PDO(
    "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$dbName};charset=utf8",
    $_ENV['DB_USER'], $_ENV['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tenantMysql->prepare('DELETE FROM usuario_permiso WHERE idusuario = ?')->execute([$idusuario]);
if ($permisos) {
    $ins = $tenantMysql->prepare('INSERT INTO usuario_permiso (idusuario, idpermiso) VALUES (?,?)');
    foreach ($permisos as $pid) {
        $ins->execute([$idusuario, $pid]);
    }
}

header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&saved=1');
exit;
