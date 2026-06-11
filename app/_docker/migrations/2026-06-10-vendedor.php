<?php
// Migración idempotente por tenant: contactos.vendedor_codigo + menú vendedor (105/106 + opción en 100).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-vendedor.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }
$m->set_charset('utf8mb4');

// 1) Columna contactos.vendedor_codigo (si la tabla existe y la columna no)
$hasContactos = $m->query("SHOW TABLES LIKE 'contactos'")->num_rows > 0;
if ($hasContactos) {
    $col = $m->query("SHOW COLUMNS FROM `contactos` LIKE 'vendedor_codigo'");
    if ($col->num_rows === 0) {
        $m->query("ALTER TABLE `contactos` ADD COLUMN `vendedor_codigo` VARCHAR(50) NULL DEFAULT NULL");
        echo "[$db] + contactos.vendedor_codigo\n";
    } else {
        echo "[$db] = contactos.vendedor_codigo (ya existe)\n";
    }
} else {
    echo "[$db] sin tabla contactos, salto columna\n";
}

// 2) Parche del menú (si bot_config tiene la fila 1)
$hasBotConfig = $m->query("SHOW TABLES LIKE 'bot_config'")->num_rows > 0;
if ($hasBotConfig) {
    $res = $m->query("SELECT menu_json FROM bot_config WHERE id=1");
    if ($res && $res->num_rows > 0) {
        $menu = json_decode($res->fetch_assoc()['menu_json'], true);
        if (is_array($menu)) {
            $ids = array_column($menu, 'menuId');

            // Opción "Soy Vendedor" en menú 100 (idempotente). Se inserta SIEMPRE *antes* de "Salir"
            // (menuId 2.2): el panel de admin (configuracion.php) renderiza las filas en el orden del
            // array pero numera espejando proyectarMenu (Salir al final), así que si "Soy Vendedor"
            // quedara después de "Salir" en el array, el panel mostraría un orden desprolijo (1,2,3,5,4).
            foreach ($menu as &$entry) {
                if (($entry['menuId'] ?? '') === '100') {
                    $items = $entry['menuItem'] ?? [];
                    $svIdx = null; $salirIdx = null;
                    foreach ($items as $k => $it) {
                        if (($it['menuId'] ?? '') === '105') { $svIdx = $k; }
                        if (($it['menuId'] ?? '') === '2.2') { $salirIdx = $k; }
                    }
                    if ($svIdx === null) {
                        // No existe → opcionId único (max+1) e insertar antes de Salir
                        $maxOid = 0; foreach ($items as $it) { $maxOid = max($maxOid, (int)($it['opcionId'] ?? 0)); }
                        $nuevo = ["opcionId"=>(string)($maxOid+1),"opcion"=>"Soy Vendedor","menuId"=>"105","guardar"=>"false","area"=>""];
                        if ($salirIdx !== null) { array_splice($items, $salirIdx, 0, [$nuevo]); }
                        else { $items[] = $nuevo; }
                        echo "[$db] + opción 'Soy Vendedor' en menú 100 (antes de Salir)\n";
                    } elseif ($salirIdx !== null && $svIdx > $salirIdx) {
                        // Existe pero quedó DESPUÉS de Salir → reordenar (idempotente)
                        $sv = $items[$svIdx];
                        array_splice($items, $svIdx, 1);
                        array_splice($items, $salirIdx, 0, [$sv]);
                        echo "[$db] ~ reordenado 'Soy Vendedor' antes de Salir\n";
                    }
                    $entry['menuItem'] = $items;
                }
            }
            unset($entry);

            // Detectar el menú destino del link de pedido — varía por tenant:
            // 350 en demo/corp, 300 en el seed default. Se busca por el placeholder <linkPedidos>.
            $linkMenuId = '';
            foreach ($menu as $e) {
                if (strpos($e['consigna'] ?? '', '<linkPedidos>') !== false) { $linkMenuId = (string)$e['menuId']; break; }
            }
            if ($linkMenuId === '') {
                fwrite(STDERR, "[$db] ADVERTENCIA: no se encontró el menú con <linkPedidos>; no se crea el menú 106\n");
            }

            // Menú 105 (idempotente)
            if (!in_array('105', $ids, true)) {
                $menu[] = ["menuId"=>"105","consigna"=>"Ingresá tu *código de vendedor*:","finaliza"=>"false",
                    "menuItem"=>[["opcionId"=>"","opcion"=>"","menuId"=>"106","guardar"=>"false","area"=>"","accion"=>"registraVendedor"]]];
                echo "[$db] + menú 105\n";
            }
            // Menú 106 (idempotente) — su captura apunta al menú del link detectado
            if (!in_array('106', $ids, true) && $linkMenuId !== '') {
                $menu[] = ["menuId"=>"106","consigna"=>"Ingresá el *código del cliente* al que vas a cargar el pedido.\n\n(Escribí *SALIR* para cerrar tu sesión de vendedor.)","finaliza"=>"false",
                    "menuItem"=>[["opcionId"=>"","opcion"=>"","menuId"=>$linkMenuId,"guardar"=>"false","area"=>"","accion"=>"chequearVendedorCliente"]]];
                echo "[$db] + menú 106 (captura → $linkMenuId)\n";
            }

            $json = json_encode($menu, JSON_UNESCAPED_UNICODE);
            $stmt = $m->prepare("UPDATE bot_config SET menu_json=? WHERE id=1");
            $stmt->bind_param('s', $json);
            $stmt->execute();
            $stmt->close();
            echo "[$db] menú actualizado: " . implode(',', array_column($menu, 'menuId')) . "\n";
        }
    } else {
        echo "[$db] bot_config sin fila id=1, salto menú\n";
    }
} else {
    echo "[$db] sin tabla bot_config, salto menú\n";
}
$m->close();
