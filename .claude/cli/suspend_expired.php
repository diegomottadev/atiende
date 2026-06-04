<?php
// Este script vive en .claude/cli/ pero usa el config de pedidos-platform.
// .claude y pedidos-platform son carpetas hermanas bajo whatsbus2021-main.
$ppRoot = dirname(dirname(__DIR__)) . '/pedidos-platform';
require_once $ppRoot . '/config/bootstrap.php';
require_once $ppRoot . '/config/database.php';
use App\ProvisioningService;

$pdo  = getPlatformPDO();
$svc  = new ProvisioningService();

$stmt = $pdo->query(
    "SELECT s.tenant_id FROM subscriptions s
     JOIN tenants t ON t.id = s.tenant_id
     WHERE s.grace_period_fin IS NOT NULL
       AND s.grace_period_fin <= NOW()
       AND t.estado = 'activo'
       AND t.deleted_at IS NULL"
);

$tenantIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
foreach ($tenantIds as $tenantId) {
    try {
        $svc->suspend((int)$tenantId);
        $pdo->prepare("UPDATE subscriptions SET grace_period_fin = NULL WHERE tenant_id = ?")
            ->execute([$tenantId]);
        echo "Suspended tenant $tenantId\n";
    } catch (\Throwable $e) {
        error_log("[suspend_expired] tenant $tenantId FAIL: " . $e->getMessage());
    }
}
echo "Done. Processed " . count($tenantIds) . " tenant(s).\n";
