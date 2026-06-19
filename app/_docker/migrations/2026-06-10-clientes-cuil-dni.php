<?php
// Idempotente por tenant: agrega clientes.cuil y clientes.dni.
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-clientes-cuil-dni.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }
require_once __DIR__ . '/_lib.php';
$m = mig_connect($db);
if ($m->query("SHOW TABLES LIKE 'clientes'")->num_rows === 0) { echo "[$db] sin tabla clientes\n"; exit(0); }
foreach (['cuil','dni'] as $colName) {
    $col = $m->query("SHOW COLUMNS FROM `clientes` LIKE '$colName'");
    if ($col->num_rows === 0) {
        $m->query("ALTER TABLE `clientes` ADD COLUMN `$colName` VARCHAR(20) NULL DEFAULT NULL");
        echo "[$db] + clientes.$colName\n";
    } else {
        echo "[$db] = clientes.$colName (ya existe)\n";
    }
}
$m->close();
