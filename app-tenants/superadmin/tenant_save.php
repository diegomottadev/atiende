<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\Encryption;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$pdo      = getPlatformPDO();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
$phoneId   = trim($_POST['phone_id']  ?? '');
$token     = trim($_POST['token']     ?? '');
$appSecret = trim($_POST['app_secret'] ?? '');

$stmt = $pdo->prepare('SELECT slug FROM tenants WHERE id = ?');
$stmt->execute([$tenantId]);
$row = $stmt->fetch();
if (!$row) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

$updates = 'whatsapp_phone_id = ?';
$params  = [$phoneId];
if ($token !== '') {
    $updates .= ', whatsapp_token_enc = ?';
    $params[] = Encryption::encrypt($token);
    $prov = getProvisionerPDO();
    $prov->exec("USE `atiende_{$row['slug']}`");
    $prov->prepare('UPDATE bot_config SET telefono = ? WHERE id = 1')->execute([$phoneId]);
}
if ($appSecret !== '') {
    $updates .= ', whatsapp_app_secret_enc = ?';
    $params[] = Encryption::encrypt($appSecret);
}
$params[] = $tenantId;
$pdo->prepare("UPDATE tenants SET $updates WHERE id = ?")->execute($params);
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&saved=1');
exit;
