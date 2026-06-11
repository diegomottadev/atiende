<?php
// Test de Telefono::normalizar (script plano, sin framework, sin DB — la clase es pura).
// Correr: docker exec atiende-app php /var/www/atiende/tests/TelefonoNormalizarTest.php
require_once __DIR__ . '/../config/Telefono.php';

$casos = [
    // [numero, pais, esperado]
    ['3764278402',        'AR', '5493764278402'], // AR local → 549
    ['0376 4278402',      'AR', '5493764278402'], // AR con 0 troncal
    ['+54 9 376 4278402', 'AR', '5493764278402'], // AR ya internacional
    ['5493764278402',     'AR', '5493764278402'], // AR ya wa_id → tal cual
    ['0549376427840',     'AR', '549376427840'],  // AR zero-padded internacional → no duplica prefijo
    ['11987654321',       'BR', '5511987654321'], // BR local (ya con su 9) → solo prefijo
    ['5511987654321',     'BR', '5511987654321'], // BR ya internacional → tal cual
    ['5512345678',        'MX', '525512345678'],  // MX local 10 díg → solo prefijo
    ['099123456',         'UY', '59899123456'],   // UY con 0 troncal → solo prefijo
    ['',                  'AR', ''],              // vacío → vacío
    ['3764278402',        'ZZ', '5493764278402'], // país desconocido → AR
];

$fail = 0;
foreach ($casos as $i => $c) {
    $got = Telefono::normalizar($c[0], $c[1]);
    if ($got !== $c[2]) {
        fwrite(STDERR, "FAIL #$i: normalizar('{$c[0]}','{$c[1]}') = '$got' (esperado '{$c[2]}')\n");
        $fail++;
    }
}
if ($fail > 0) { fwrite(STDERR, "$fail caso(s) fallaron\n"); exit(1); }
echo "OK: " . count($casos) . " casos pasaron\n";
