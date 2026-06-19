<?php
// Runner de migraciones con ledger. Corre DENTRO del container atiende-app:
//   docker exec -i atiende-app php /var/www/atiende/_docker/migrate.php [--dry-run] [db]
//
// - Descubre tenants desde pedidos_platform.tenants (estado='activo') = fuente de verdad.
// - Por tenant mantiene una tabla `schema_migrations(filename, applied_at)`.
// - Aplica solo las migraciones de _docker/migrations/*.php (orden por nombre = fecha)
//   que aún no estén registradas. Cada migración ya es idempotente; el ledger evita
//   re-correrlas y deja un registro auditable de qué se aplicó y cuándo.
// - --dry-run: lista las pendientes por tenant sin aplicar nada.
require_once __DIR__ . '/migrations/_lib.php';

$args   = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$only   = null;
foreach ($args as $a) { if ($a !== '--dry-run' && $a[0] !== '-') { $only = $a; } }

$migDir = __DIR__ . '/migrations';
$files  = array_map('basename', glob($migDir . '/*.php'));
sort($files);
// Excluir helpers/internos (prefijo '_')
$files = array_values(array_filter($files, function ($f) { return $f[0] !== '_'; }));

// Descubrir tenants (db_name) activos
$tenants = [];
if ($only) {
    $tenants[] = $only;
} else {
    $pp = mig_connect('pedidos_platform');
    $res = $pp->query("SELECT db_name FROM tenants WHERE estado = 'activo' AND db_name <> '' ORDER BY db_name");
    while ($row = $res->fetch_assoc()) { $tenants[] = $row['db_name']; }
    $pp->close();
}

if (empty($tenants)) { echo "No hay tenants activos para migrar.\n"; exit(0); }
echo ($dryRun ? "[DRY-RUN] " : "") . "Tenants: " . implode(', ', $tenants) . "\n";
echo "Migraciones en disco: " . count($files) . "\n\n";

$totalAplicadas = 0;
$errores = 0;

foreach ($tenants as $db) {
    $m = mig_connect($db);
    $m->query("CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `filename` VARCHAR(191) NOT NULL PRIMARY KEY,
        `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $aplicadas = [];
    $r = $m->query("SELECT filename FROM `schema_migrations`");
    while ($row = $r->fetch_row()) { $aplicadas[$row[0]] = true; }

    $pendientes = array_values(array_filter($files, function ($f) use ($aplicadas) { return !isset($aplicadas[$f]); }));
    echo "── $db: " . count($pendientes) . " pendiente(s)\n";

    foreach ($pendientes as $f) {
        if ($dryRun) { echo "   • $f (pendiente)\n"; continue; }
        $cmd = escapeshellarg(PHP_BINARY) . ' '
             . escapeshellarg($migDir . '/' . $f) . ' '
             . escapeshellarg($db) . ' 2>&1';
        $out = []; $rc = 0;
        exec($cmd, $out, $rc);
        foreach ($out as $line) { echo "   $line\n"; }
        if ($rc === 0) {
            $stmt = $m->prepare("INSERT IGNORE INTO `schema_migrations` (`filename`) VALUES (?)");
            $stmt->bind_param('s', $f);
            $stmt->execute();
            $stmt->close();
            echo "   ✓ registrada: $f\n";
            $totalAplicadas++;
        } else {
            echo "   ✗ FALLÓ (rc=$rc): $f — se detiene este tenant\n";
            $errores++;
            $m->close();
            continue 2; // no seguir aplicando en este tenant si una falla
        }
    }
    $m->close();
}

echo "\n" . ($dryRun ? "[DRY-RUN] nada aplicado.\n"
                     : "Listo. Aplicadas: $totalAplicadas. Errores: $errores.\n");
exit($errores > 0 ? 1 : 0);
