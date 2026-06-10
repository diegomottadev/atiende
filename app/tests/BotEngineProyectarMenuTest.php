<?php
/**
 * Test aislado de BotEngine::proyectarMenu (sin framework, sin DB).
 *
 * BotEngine requiere Connection/Conexion/WhatsAppClient en su constructor, así que
 * instanciarlo acá es inviable. La función de abajo es una COPIA 1:1 del método
 * privado `proyectarMenu` de app/modelos/BotEngine.php. Si se modifica el método,
 * actualizar también esta copia.
 *
 * Correr: docker exec atiende-app php /var/www/atiende/tests/BotEngineProyectarMenuTest.php
 */

function proyectarMenu(array $menuItem): array
{
    $visibles = []; $salir = null;
    foreach ($menuItem as $opt) {
        // Captura de texto libre: opcionId vacío (cadena ''), NO el '0' del botón Salir.
        // Se detecta por strlen (igual que el bucle de match) para que una captura cuyo
        // destino es '2.2' (detalle de consulta / consultar-reclamo) NO se confunda con Salir.
        if (strlen((string) ($opt['opcionId'] ?? '')) === 0) {
            $visibles[] = $opt; continue;            // se conserva tal cual, no se renumera, sea cual sea su menuId destino
        }
        if (($opt['menuId'] ?? '') === '2.2') { $salir = $opt; continue; } // Salir → al final
        $activo = array_key_exists('activo', $opt) ? $opt['activo'] : 'true'; // legacy = visible
        if ($activo === 'false') continue;            // oculta
        $visibles[] = $opt;
    }
    $n = 1;
    foreach ($visibles as &$o) { if (!empty($o['opcionId'])) { $o['opcionId'] = (string)$n; $n++; } }
    unset($o);
    if ($salir !== null) { $salir['opcionId'] = (string)$n; $salir['activo'] = 'true'; $visibles[] = $salir; }
    return $visibles;
}

$fails = 0;
function check($cond, $msg) {
    global $fails;
    if (!$cond) { $fails++; fwrite(STDERR, "FAIL: $msg\n"); }
}

// ---- (a) ocultar la opción intermedia de 5 → quedan 1..4 correlativas y Salir último ----
$menu = [
    ['opcionId' => '1', 'opcion' => 'Hacer un pedido',     'menuId' => '350', 'activo' => 'true'],
    ['opcionId' => '2', 'opcion' => 'Hacer un reclamo',    'menuId' => '1',   'activo' => 'true'],
    ['opcionId' => '3', 'opcion' => 'Hacer una consulta',  'menuId' => '300', 'activo' => 'false'], // OCULTA
    ['opcionId' => '4', 'opcion' => 'Consultar reclamo',   'menuId' => '400', 'activo' => 'true'],
    ['opcionId' => '0', 'opcion' => 'Salir',               'menuId' => '2.2'],
];
$proj = proyectarMenu($menu);
check(count($proj) === 4, '(a) deben quedar 4 items (5 - 1 oculta)');
check($proj[0]['opcionId'] === '1' && $proj[0]['opcion'] === 'Hacer un pedido',  '(a) item 1 correcto');
check($proj[1]['opcionId'] === '2' && $proj[1]['opcion'] === 'Hacer un reclamo', '(a) item 2 correcto');
check($proj[2]['opcionId'] === '3' && $proj[2]['opcion'] === 'Consultar reclamo','(a) ex-4 renumerado a 3');
check($proj[3]['opcionId'] === '4' && $proj[3]['menuId'] === '2.2',              '(a) Salir último con número 4');

// ---- (b) item legacy sin `activo` = visible ----
$menuLegacy = [
    ['opcionId' => '1', 'opcion' => 'A', 'menuId' => '10'], // sin activo
    ['opcionId' => '2', 'opcion' => 'B', 'menuId' => '20'], // sin activo
    ['opcionId' => '0', 'opcion' => 'Salir', 'menuId' => '2.2'],
];
$projL = proyectarMenu($menuLegacy);
check(count($projL) === 3, '(b) legacy sin activo → todos visibles');
check($projL[0]['opcionId'] === '1' && $projL[1]['opcionId'] === '2', '(b) numeración legacy intacta');
check($projL[2]['menuId'] === '2.2' && $projL[2]['opcionId'] === '3', '(b) Salir último');

// ---- (c) opcionId vacío (captura de texto libre) se conserva sin número ----
$menuCaptura = [
    ['opcionId' => '1', 'opcion' => 'A', 'menuId' => '10', 'activo' => 'true'],
    ['opcionId' => '', 'opcion' => '', 'menuId' => '15', 'accion' => 'registrarConsulta'], // captura libre
];
$projC = proyectarMenu($menuCaptura);
check(count($projC) === 2, '(c) captura libre se conserva');
check($projC[1]['opcionId'] === '' && ($projC[1]['accion'] ?? '') === 'registrarConsulta', '(c) opcionId vacío sin número, accion intacta');
check($projC[0]['opcionId'] === '1', '(c) item numerado renumerado, no la captura');

// ---- (d) Salir nunca se oculta aunque venga activo='false' ----
$menuSalir = [
    ['opcionId' => '1', 'opcion' => 'A', 'menuId' => '10', 'activo' => 'true'],
    ['opcionId' => '0', 'opcion' => 'Salir', 'menuId' => '2.2', 'activo' => 'false'], // intento de ocultar
];
$projS = proyectarMenu($menuSalir);
check(count($projS) === 2, '(d) Salir no se oculta pese a activo=false');
$last = end($projS);
check($last['menuId'] === '2.2' && $last['activo'] === 'true', '(d) Salir forzado visible y último');
check($last['opcionId'] === '2', '(d) Salir renumerado al final');

// ---- (e) captura de texto libre cuyo destino es 2.2 (consulta / consultar-reclamo) NO es "Salir" ----
$menuCaptura22 = [
    ['opcionId' => '', 'opcion' => '', 'menuId' => '2.2', 'accion' => 'registrarConsulta'], // detalle de consulta
];
$projE = proyectarMenu($menuCaptura22);
check(count($projE) === 1, '(e) la captura libre a 2.2 se conserva');
check($projE[0]['opcionId'] === '' && ($projE[0]['accion'] ?? '') === 'registrarConsulta',
      '(e) captura a 2.2 mantiene opcionId vacío (no se convierte en Salir numerado)');

// ---- (f) captura libre a 2.2 + Salir real coexistiendo: la captura sigue sin número, el Salir va último ----
$menuMix = [
    ['opcionId' => '1', 'opcion' => 'A',     'menuId' => '10',  'activo' => 'true'],
    ['opcionId' => '',  'opcion' => '',      'menuId' => '2.2', 'accion' => 'consultarReclamo'], // captura libre
    ['opcionId' => '0', 'opcion' => 'Salir', 'menuId' => '2.2'],                                  // Salir real
];
$projF = proyectarMenu($menuMix);
check(count($projF) === 3, '(f) captura + opción + Salir → 3 items');
check($projF[1]['opcionId'] === '' && ($projF[1]['accion'] ?? '') === 'consultarReclamo',
      '(f) la captura conserva opcionId vacío');
$lastF = end($projF);
check($lastF['opcion'] === 'Salir' && $lastF['menuId'] === '2.2', '(f) Salir real va último');

if ($fails === 0) {
    echo "OK\n";
    exit(0);
}
fwrite(STDERR, "$fails assertion(s) failed\n");
exit(1);
