<?php
// Migración DEMO-ONLY e idempotente: personaliza el mensaje de bienvenida del bot
// del tenant `atiende_demo` para guiar un pedido de prueba:
//   - Nodo menuId 100 (bienvenida, lo que dispara "Hola"): explica que elija la
//     opción "1 - Ya soy Cliente" e ingrese el código de cliente 0001.
//   - Nodo menuId 101 (pide el código de cliente): agrega el hint "(para la demo,
//     ingresá 0001)".
// Solo actúa sobre `atiende_demo`; en cualquier otro tenant es no-op (el runner la
// corre sobre todos, pero el guard la saltea). Edita bot_config.menu_json sin tocar
// las opciones del menú (BotEngine las lista solo debajo de la consigna).
// Idempotente: si la consigna ya está en el texto destino, no escribe nada.
// UTF-8 vía PHP + JSON_UNESCAPED_UNICODE (menu_json NO se puede editar por mysql CLI:
// el pipeline bash→docker→mysql corrompe los multibyte — ver gotchas de CLAUDE.md).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-23-demo-bienvenida-pedido-prueba.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

if ($db !== 'atiende_demo') {
    echo "[$db] no es atiende_demo, salto (migración demo-only)\n";
    exit(0);
}

require_once __DIR__ . '/_lib.php';
$m = mig_connect($db);

// Consignas destino (clave = menuId).
$targets = [
    '100' =>
        "Bienvenido a *<empresa>*, *<nombre>*!! 👋\n\n"
        . "🧪 Esto es una *demo*. Para hacer un *pedido de prueba*:\n"
        . "1) Elegí la opción *1 - Ya soy Cliente*\n"
        . "2) Cuando te pida el código de cliente, ingresá *0001*\n\n"
        . "Vas a poder cargar productos al carrito y simular un pedido completo.\n\n"
        . "Para comenzar, elegí una opción escribiendo solo el número:",
    '101' =>
        "Por favor ingresa tu *código de cliente*:\n\n"
        . "_(Para la demo, ingresá *0001*)_",
];

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
foreach ($menu as &$nodo) {
    if (!isset($nodo['menuId'])) continue;
    $id = (string) $nodo['menuId'];
    if (!isset($targets[$id])) continue;

    if (($nodo['consigna'] ?? '') === $targets[$id]) {
        echo "[$db] = nodo $id consigna ya está al día\n";
    } else {
        $nodo['consigna'] = $targets[$id];
        $cambios++;
        echo "[$db] ~ nodo $id consigna actualizada\n";
    }
}
unset($nodo);

if ($cambios > 0) {
    $json = json_encode($menu, JSON_UNESCAPED_UNICODE);
    $stmt = $m->prepare("UPDATE bot_config SET menu_json = ? WHERE id = 1");
    $stmt->bind_param('s', $json);
    $stmt->execute();
    $stmt->close();
    echo "[$db] menu_json guardado ($cambios nodo/s)\n";
} else {
    echo "[$db] sin cambios\n";
}

$m->close();
