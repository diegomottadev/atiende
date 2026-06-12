<?php
// Idempotente: lleva bot_config del tenant al schema canónico (columnas de empresa +
// costo/admin que algunos tenants con seed viejo no tienen). Sin estas columnas,
// ticket_pdf.php / getEmpresa / getCostoEnvio / getAdminCopia lanzaban fatal por
// "Unknown column" (drift de schema). Las columnas de empresa alimentan el encabezado
// del ticket (nombre/razón/CUIT/logo).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-12-bot-config-empresa.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }
$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }

if ($m->query("SHOW TABLES LIKE 'bot_config'")->num_rows === 0) {
    echo "[$db] sin tabla bot_config (nada que hacer)\n";
    $m->close();
    exit(0);
}

// Columna => definición. MySQL 8 no soporta ADD COLUMN IF NOT EXISTS → chequear primero.
$cols = [
    'nombre_empresa'     => "VARCHAR(255) NULL DEFAULT NULL",
    'razon_social'       => "VARCHAR(255) NULL DEFAULT NULL",
    'cuit'               => "VARCHAR(20)  NULL DEFAULT NULL",
    'logo'               => "VARCHAR(255) NULL DEFAULT NULL",
    'costo_envio'        => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'costo_envio_activo' => "TINYINT(1) NOT NULL DEFAULT 0",
    'admin_telefono'     => "VARCHAR(20) NULL DEFAULT NULL",
    'admin_envio_activo' => "TINYINT(1) NOT NULL DEFAULT 0",
];

foreach ($cols as $col => $def) {
    if ($m->query("SHOW COLUMNS FROM `bot_config` LIKE '" . $m->real_escape_string($col) . "'")->num_rows === 0) {
        if ($m->query("ALTER TABLE `bot_config` ADD COLUMN `$col` $def")) {
            echo "[$db] + bot_config.$col\n";
        } else {
            echo "[$db] ! error ADD $col: {$m->error}\n";
        }
    } else {
        echo "[$db] = bot_config.$col (ya existe)\n";
    }
}
$m->close();
