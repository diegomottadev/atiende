<?php
// Migración idempotente por tenant: aislar las imágenes de artículos por tenant.
//   Antes: files/articulos/{codigo}.jpg (plano, compartido entre tenants).
//   Ahora: files/articulos/{seg}/{codigo}.jpg  donde {seg} = nombre de DB saneado.
//
//   Copia (NO mueve) cada {codigo}.jpg de la carpeta plana a la subcarpeta del tenant,
//   por cada codigo presente en `articulos`. Idempotente: si el destino ya existe, lo
//   saltea (re-correrlo no duplica ni pisa). NO borra la carpeta plana — la limpieza
//   final (borrar los *.jpg planos salvo camara.jpg) la hace el operador tras migrar
//   TODOS los tenants.
//
//   El placeholder camara.jpg NO se aísla (queda plano) — es el default de la app.
//
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-19-aislar-imagenes-tenant.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

// Segmento de tenant: misma allowlist anti-traversal que Connection::rutaArticulos() y el endpoint.
$seg = preg_replace('/[^A-Za-z0-9_]/', '', (string) $db);
if ($seg === '') { fwrite(STDERR, "El nombre de DB '$db' no produce un segmento válido\n"); exit(1); }

require_once __DIR__ . '/_lib.php';
$m = mig_connect($db);

// Path absoluto dentro del contenedor (mismo root que sirve nginx).
$src = '/var/www/atiende/files/articulos/';
$dst = $src . $seg . '/';

if (!is_dir($src)) { fwrite(STDERR, "[$db] no existe $src\n"); exit(1); }
if (!is_dir($dst)) {
    if (!@mkdir($dst, 0775, true)) { fwrite(STDERR, "[$db] no se pudo crear $dst\n"); exit(1); }
}

if ($m->query("SHOW TABLES LIKE 'articulos'")->num_rows === 0) {
    echo "[$db] sin tabla articulos, salto\n";
    $m->close();
    exit(0);
}

$res = $m->query("SELECT codigo FROM articulos");
if ($res === false) { fwrite(STDERR, "[$db] error al leer articulos: {$m->error}\n"); exit(1); }

$copiadas = 0;   // se copió el archivo a la subcarpeta del tenant
$existian = 0;   // el destino ya existía (idempotencia)
$sinArchivo = 0; // el codigo no tiene {codigo}.jpg plano de origen
$invalidas = 0;  // codigo que no pasa la allowlist (no se toca)

while ($row = $res->fetch_assoc()) {
    $cod = (string) $row['codigo'];
    // Mismo saneo que el endpoint: allowlist + rechazo de "..".
    if ($cod === '' || strpos($cod, '..') !== false || !preg_match('/\A[A-Za-z0-9 ._-]{1,64}\z/', $cod)) {
        $invalidas++;
        continue;
    }
    $origen  = $src . $cod . '.jpg';
    $destino = $dst . $cod . '.jpg';
    if (file_exists($destino)) { $existian++; continue; }
    if (!file_exists($origen)) { $sinArchivo++; continue; }
    if (@copy($origen, $destino)) { $copiadas++; }
    else { fwrite(STDERR, "[$db] no se pudo copiar $origen → $destino\n"); }
}

echo "[$db] seg=$seg | copiadas=$copiadas | ya-existían=$existian | sin-archivo=$sinArchivo | codigos-inválidos=$invalidas\n";

$m->close();
