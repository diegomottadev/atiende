<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\MailService;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$pdo      = getPlatformPDO();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
$stmt     = $pdo->prepare('SELECT * FROM tenants WHERE id = ? AND estado = ?');
$stmt->execute([$tenantId, 'pendiente_whatsapp']);
$tenant = $stmt->fetch();
if ($tenant && $tenant['whatsapp_phone_id'] && $tenant['whatsapp_waba_id'] && $tenant['whatsapp_token_enc']) {
    $pdo->prepare("UPDATE tenants SET estado = 'activo' WHERE id = ?")->execute([$tenantId]);
    MailService::send($tenant['email'], '¡Tu cuenta está activa!', 'activacion', ['empresa' => $tenant['nombre']]);
}
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId);
exit;
