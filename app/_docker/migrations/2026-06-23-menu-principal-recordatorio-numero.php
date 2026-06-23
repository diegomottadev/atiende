<?php
// Migración idempotente por tenant (TODOS los tenants, NO demo-only):
// reformatea la consigna del menú principal (menuId 200, "¿En qué podemos
// ayudarte?") para:
//   - dejar una línea en blanco después del saludo, y
//   - recordar que se ingrese solo el número de la opción,
//   - con línea en blanco antes del listado de opciones (BotEngine pega su
//     propio "\n" tras la consigna → el "\n" final de la consigna produce el
//     renglón vacío).
//
// SEGURA ante customizaciones: solo reescribe si la consigna actual es uno de
// los defaults conocidos. Si el tenant tiene una consigna distinta (editada a
// mano), la respeta y saltea — NO pisa texto custom.
//
// Idempotente: si ya está el texto destino, no escribe nada.
// UTF-8 vía PHP + JSON_UNESCAPED_UNICODE (menu_json NO se puede editar por
// mysql CLI: el pipeline bash→docker→mysql corrompe los multibyte — ver gotchas
// de CLAUDE.md).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-23-menu-principal-recordatorio-numero.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

require_once __DIR__ . '/_lib.php';

$saludo = '<saludo> *<nombre>*! ¿En qué podemos ayudarte?';

// Defaults conocidos que SÍ se pueden reescribir (con o sin "\n" final).
$conocidos = [
    $saludo,
    $saludo . "\n",
];

// Texto destino.
$target = $saludo . "\n\n" . "Elegí una opción ingresando solo el número:\n";

$m = mig_connect($db);

if ($m->query("SHOW TABLES LIKE 'bot_config'")->num_rows === 0) {
    echo "[$db] sin tabla bot_config, salto\n";
    exit(0);
}

$res = $m->query("SELECT menu_json FROM bot_config WHERE id = 1");
if (!$res || $res->num_rows === 0) {
    echo "[$db] bot_config id=1 sin fila, salto\n";
    exit(0);
}

$menu = json_decode($res->fetch_assoc()['menu_json'], true);
if (!is_array($menu)) {
    fwrite(STDERR, "[$db] menu_json inválido\n");
    exit(1);
}

$cambios = 0;
$encontrado = false;
foreach ($menu as &$nodo) {
    if ((string) ($nodo['menuId'] ?? '') !== '200') continue;
    $encontrado = true;
    $actual = $nodo['consigna'] ?? '';

    if ($actual === $target) {
        echo "[$db] = consigna 200 ya está al día\n";
    } elseif (in_array($actual, $conocidos, true)) {
        $nodo['consigna'] = $target;
        $cambios++;
        echo "[$db] ~ consigna 200 actualizada\n";
    } else {
        echo "[$db] ! consigna 200 es custom (no es un default conocido), la respeto y salto\n";
    }
}
unset($nodo);

if (!$encontrado) {
    echo "[$db] sin nodo menuId 200, salto\n";
    exit(0);
}

if ($cambios > 0) {
    $json = json_encode($menu, JSON_UNESCAPED_UNICODE);
    $stmt = $m->prepare("UPDATE bot_config SET menu_json = ? WHERE id = 1");
    $stmt->bind_param('s', $json);
    $stmt->execute();
    $stmt->close();
    echo "[$db] menu_json guardado\n";
} else {
    echo "[$db] sin cambios\n";
}

$m->close();
