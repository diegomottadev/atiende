<?php
// Reconcilia provisioning fallidos cuya compensación quedó incompleta
// (provisioning_failures.compensated = 0). Re-ejecuta solo la limpieza de
// infraestructura idempotente: DROP DATABASE IF EXISTS de la base huérfana y
// DELETE de la fila en axbot.empresa. NO toca filas de tenants/platform_users
// (un humano las revisa). Marca el fallo como resuelto al terminar OK.
//
// Uso:
//   php cli/reconcile_provisioning.php            → reconcilia los pendientes
//   php cli/reconcile_provisioning.php --list     → solo lista, no toca nada
// Este script vive en .claude/cli/ pero usa el config de pedidos-platform.
// .claude y pedidos-platform son carpetas hermanas bajo whatsbus2021-main.
$ppRoot = dirname(dirname(__DIR__)) . '/pedidos-platform';
require_once $ppRoot . '/config/bootstrap.php';
require_once $ppRoot . '/config/database.php';

$listOnly = in_array('--list', $argv, true);

$pdo  = getPlatformPDO();
$rows = $pdo->query(
    "SELECT * FROM provisioning_failures
     WHERE compensated = 0 AND resolved_at IS NULL
     ORDER BY id"
)->fetchAll();

if (!$rows) {
    echo "No hay provisioning_failures pendientes de reconciliar.\n";
    exit(0);
}

echo count($rows) . " fallo(s) pendiente(s):\n";
foreach ($rows as $r) {
    echo sprintf(
        "  #%d  slug=%s  db=%s  paso=%d  error=%s\n",
        $r['id'], $r['slug'] ?? '-', $r['db_name'] ?? '-', $r['paso'],
        substr((string)$r['error'], 0, 120)
    );
}

if ($listOnly) {
    echo "\n(--list) Sin cambios.\n";
    exit(0);
}

$ok = 0;
foreach ($rows as $r) {
    try {
        if (!empty($r['db_name'])) {
            getProvisionerPDO()->exec("DROP DATABASE IF EXISTS `{$r['db_name']}`");
        }
        if (!empty($r['slug'])) {
            getAxbotPDO()->prepare('DELETE FROM empresa WHERE empresa = ?')->execute([$r['slug']]);
        }
        $pdo->prepare(
            'UPDATE provisioning_failures SET compensated = 1, resolved_at = NOW() WHERE id = ?'
        )->execute([$r['id']]);
        echo "Reconciliado #{$r['id']} ({$r['slug']})\n";
        $ok++;
    } catch (\Throwable $e) {
        error_log("[reconcile_provisioning] #{$r['id']} FAIL: " . $e->getMessage());
        echo "FALLO #{$r['id']}: " . $e->getMessage() . "\n";
    }
}
echo "Listo. Reconciliados $ok de " . count($rows) . ".\n";
