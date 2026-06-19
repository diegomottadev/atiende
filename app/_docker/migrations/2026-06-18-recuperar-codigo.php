<?php
// Migración idempotente por tenant: flujo "No recuerdo mi número de cliente".
//   - Siembra la fila reservada de motivo_reclamos (opcionId '99', area '0').
//   - Parchea bot_config.menu_json:
//       · menuId 100, ítem opción-3 → accion "recuperarCodigoCliente".
//       · menuId 103 → consigna del alta (4 campos) + captura accion
//         "registrarOlvidoReclamo" con destino al cierre menuId 3.
//   El menuId 104 NO se toca (queda sin uso para este flujo, no se borra).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-18-recuperar-codigo.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

require_once __DIR__ . '/_lib.php';
$m = mig_connect($db);

// Consigna nueva del menuId 103 (texto exacto §3 de la spec; \n literales del bot).
$consigna103 = "Para registrarte enviá en *un solo mensaje*:\n*Nombre/Razón Social:*\n*Localidad:*\n*Dirección/Dirección del negocio:*\n*DNI o CUIL:*";

// 1) Fila reservada de motivo_reclamos (opcionId '99', area '0').
//    MySQL 8 no tiene INSERT ... IF NOT EXISTS → chequeo previo con SELECT.
$hasMotivos = $m->query("SHOW TABLES LIKE 'motivo_reclamos'")->num_rows > 0;
if ($hasMotivos) {
    $res = $m->query("SELECT id FROM `motivo_reclamos` WHERE `opcionId` = '99' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        echo "[$db] = motivo_reclamos opcionId '99' (ya existe)\n";
    } else {
        // opcion fijo, menuId '5' (igual que los motivos visibles), guardar 0, area '0'.
        $stmt = $m->prepare("INSERT INTO `motivo_reclamos` (`opcionId`,`opcion`,`menuId`,`guardar`,`area`) VALUES ('99', 'No recuerdo mi numero de cliente', '5', 0, '0')");
        $stmt->execute();
        $stmt->close();
        echo "[$db] + motivo_reclamos opcionId '99' (area '0')\n";
    }
} else {
    echo "[$db] sin tabla motivo_reclamos, salto fila reservada\n";
}

// 2) Parche del menú (si bot_config tiene la fila 1).
$hasBotConfig = $m->query("SHOW TABLES LIKE 'bot_config'")->num_rows > 0;
if ($hasBotConfig) {
    $res = $m->query("SELECT menu_json FROM bot_config WHERE id=1");
    if ($res && $res->num_rows > 0) {
        $menu = json_decode($res->fetch_assoc()['menu_json'], true);
        if (is_array($menu)) {
            $cambios = [];

            foreach ($menu as &$entry) {
                // menuId 100, ítem opción-3 → accion "recuperarCodigoCliente" (idempotente).
                if (($entry['menuId'] ?? '') === '100') {
                    foreach ($entry['menuItem'] as &$it) {
                        if (($it['opcionId'] ?? '') === '3') {
                            if (($it['accion'] ?? '') !== 'recuperarCodigoCliente') {
                                $it['accion'] = 'recuperarCodigoCliente';
                                $cambios[] = "100/op3 accion=recuperarCodigoCliente";
                            }
                        }
                    }
                    unset($it);
                }

                // menuId 103 → consigna nueva + captura accion "registrarOlvidoReclamo" destino 3.
                if (($entry['menuId'] ?? '') === '103') {
                    if (($entry['consigna'] ?? '') !== $consigna103) {
                        $entry['consigna'] = $consigna103;
                        $cambios[] = "103 consigna";
                    }
                    // Captura: primer (único) menuItem. Lo dejamos como captura libre
                    // (opcionId '') con accion registrarOlvidoReclamo y destino menuId 3.
                    $cap = ["opcionId"=>"","opcion"=>"","menuId"=>"3","guardar"=>"false","area"=>"","accion"=>"registrarOlvidoReclamo"];
                    $itemActual = $entry['menuItem'][0] ?? null;
                    $necesitaParche = !is_array($itemActual)
                        || ($itemActual['accion'] ?? '') !== 'registrarOlvidoReclamo'
                        || ($itemActual['menuId'] ?? '') !== '3';
                    if (count($entry['menuItem'] ?? []) !== 1 || $necesitaParche) {
                        $entry['menuItem'] = [$cap];
                        $cambios[] = "103/captura accion=registrarOlvidoReclamo → 3";
                    }
                }
            }
            unset($entry);

            if (empty($cambios)) {
                echo "[$db] = menú ya parcheado (sin cambios)\n";
            } else {
                $json = json_encode($menu, JSON_UNESCAPED_UNICODE);
                $stmt = $m->prepare("UPDATE bot_config SET menu_json=? WHERE id=1");
                $stmt->bind_param('s', $json);
                $stmt->execute();
                $stmt->close();
                echo "[$db] ~ menú actualizado: " . implode(', ', $cambios) . "\n";
            }
        } else {
            echo "[$db] menu_json no decodifica, salto menú\n";
        }
    } else {
        echo "[$db] bot_config sin fila id=1, salto menú\n";
    }
} else {
    echo "[$db] sin tabla bot_config, salto menú\n";
}
$m->close();
