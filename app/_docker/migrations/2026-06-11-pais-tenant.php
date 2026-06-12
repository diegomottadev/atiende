<?php
// Idempotente: agrega bot_config.pais (en la DB del tenant pasada) y tenants.pais (DB central, una vez).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-11-pais-tenant.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }
$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }

// 1) bot_config.pais en la DB del tenant (si la tabla existe y la columna no)
if ($m->query("SHOW TABLES LIKE 'bot_config'")->num_rows > 0) {
    if ($m->query("SHOW COLUMNS FROM `bot_config` LIKE 'pais'")->num_rows === 0) {
        $m->query("ALTER TABLE `bot_config` ADD COLUMN `pais` VARCHAR(2) NOT NULL DEFAULT 'AR'");
        echo "[$db] + bot_config.pais\n";
    } else { echo "[$db] = bot_config.pais (ya existe)\n"; }
} else { echo "[$db] sin tabla bot_config\n"; }

// 2) tenants.pais en la DB central pedidos_platform (idempotente, una sola vez)
$pp = new mysqli('mysql8', 'root', 'root', 'pedidos_platform');
if (!$pp->connect_errno) {
    if ($pp->query("SHOW COLUMNS FROM `tenants` LIKE 'pais'")->num_rows === 0) {
        $pp->query("ALTER TABLE `tenants` ADD COLUMN `pais` VARCHAR(2) NOT NULL DEFAULT 'AR'");
        echo "[pedidos_platform] + tenants.pais\n";
    } else { echo "[pedidos_platform] = tenants.pais (ya existe)\n"; }
    $pp->close();
}
$m->close();
