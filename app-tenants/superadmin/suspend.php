<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\ProvisioningService;
Auth::requireSuperadmin();
Auth::verifyCsrf();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
(new ProvisioningService())->suspend($tenantId);
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId);
exit;
