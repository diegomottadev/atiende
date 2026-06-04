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

$stmt = $pdo->prepare('SELECT id FROM tenants WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$tenantId]);
if (!$stmt->fetch()) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

// Validación: nombre obligatorio, email válido. (slug y db_name NO se editan:
// definen el subdominio y la base de datos del tenant.)
if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&err=datos');
    exit;
}

$pdo->prepare('UPDATE tenants SET nombre = ?, email = ? WHERE id = ?')
    ->execute([$nombre, $email, $tenantId]);

header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&saved=2');
exit;
