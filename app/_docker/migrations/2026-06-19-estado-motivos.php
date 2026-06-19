<?php
// Migración idempotente por tenant: flag `estado` en los motivos.
//   - Agrega columna `estado` TINYINT(1) NOT NULL DEFAULT 1 a `motivo_reclamos`
//     y `motivo_consultas` (MySQL 8 no tiene ADD COLUMN IF NOT EXISTS → chequeo
//     previo en information_schema). Default 1 = visible en el panel admin, así
//     las filas existentes no cambian su visibilidad.
//   - Deja el motivo reservado opcionId '99' (flujo "recuperar código") en
//     estado 0 → no se lista en el panel admin, pero el bot lo sigue usando.
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-19-estado-motivos.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }
$m->set_charset('utf8mb4');

foreach (['motivo_reclamos', 'motivo_consultas'] as $tabla) {
    if ($m->query("SHOW TABLES LIKE '$tabla'")->num_rows === 0) {
        echo "[$db] sin tabla $tabla, salto\n";
        continue;
    }
    $res = $m->query("SELECT 1 FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$tabla' AND COLUMN_NAME = 'estado'");
    if ($res && $res->num_rows > 0) {
        echo "[$db] = $tabla.estado (ya existe)\n";
    } else {
        $m->query("ALTER TABLE `$tabla` ADD COLUMN `estado` tinyint(1) NOT NULL DEFAULT 1");
        echo "[$db] + $tabla.estado (default 1)\n";
    }
}

// El motivo reservado op99 va a estado 0 (oculto del panel admin; el bot lo usa igual).
if ($m->query("SHOW TABLES LIKE 'motivo_reclamos'")->num_rows > 0) {
    $res = $m->query("SELECT estado FROM `motivo_reclamos` WHERE `opcionId` = '99' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $estado = $res->fetch_assoc()['estado'];
        if ((string) $estado === '0') {
            echo "[$db] = motivo_reclamos op99 ya en estado 0\n";
        } else {
            $m->query("UPDATE `motivo_reclamos` SET `estado` = 0 WHERE `opcionId` = '99'");
            echo "[$db] ~ motivo_reclamos op99 → estado 0\n";
        }
    } else {
        echo "[$db] op99 no existe (corré antes 2026-06-18-recuperar-codigo.php)\n";
    }
}

$m->close();
