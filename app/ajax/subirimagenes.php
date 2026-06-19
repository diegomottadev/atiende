<?php
/**
 * Importación masiva de imágenes de artículos (por carpeta + lotes).
 *
 * El front (vistas/scripts/articulo.js) sube los archivos JPG de una carpeta en
 * lotes vía fetch; cada archivo debe llamarse igual que el `codigo` del artículo
 * (ej. 623.jpg → articulos.codigo='623'). Se guarda como ../files/articulos/{seg}/{codigo}.jpg
 * (imágenes aisladas por tenant, ver Connection::rutaArticulos(); mismo path/convención
 * que ajax/articulo.php op=guardaryeditar).
 *
 * Cada imagen reporta un status: ok | nomatch | badformat | error.
 *  - ok        → JPEG real, ≤2MB, matchea un artículo → guardada.
 *  - nomatch   → el código no existe en `articulos` → se saltea (no se guarda).
 *  - badformat → no es JPEG real o supera 2MB.
 *  - error     → fallo de upload / path inválido / no se pudo mover.
 */
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require (__ROOT__.'/config/Connection.php');
Connection::setDatabase(!empty($_SESSION['tenant_db']) ? $_SESSION['tenant_db'] : DB_NAME);

// Mutación (subida de imágenes por POST) → exige token CSRF (mismo mecanismo que subirarchivo.php).
requireCsrf();

header('Content-Type: application/json');

$MAX_IMG_BYTES = 2 * 1024 * 1024; // 2 MB (mismo tope de negocio que el form individual)
// Imágenes aisladas por tenant: ../files/articulos/{seg}/ (la subcarpeta del tenant puede no existir).
$dir = Connection::rutaArticulos();
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}

// Normaliza $_FILES['imagenes'] (array) a una lista de archivos individuales.
$archivos = array();
if (isset($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
    $total = count($_FILES['imagenes']['name']);
    for ($i = 0; $i < $total; $i++) {
        $archivos[] = array(
            'name'     => $_FILES['imagenes']['name'][$i],
            'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
            'size'     => $_FILES['imagenes']['size'][$i],
            'error'    => $_FILES['imagenes']['error'][$i],
        );
    }
}

if (empty($archivos)) {
    echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Saca el basename sin extensión y lo valida con allowlist estricta (anti path traversal).
// Devuelve null si es inseguro (→ status error, no se guarda).
function codigoDesdeNombre($nombre) {
    $base = pathinfo((string) $nombre, PATHINFO_FILENAME); // sin extensión
    // Allowlist: el código solo admite alfanumérico, espacio, punto, guion y guion bajo (cubre códigos tipo AAL-0001).
    if ($base === '' || strpos($base, '..') !== false || !preg_match('/\A[A-Za-z0-9 ._-]{1,64}\z/', $base)) {
        return null;
    }
    return $base;
}

// 1) Set de códigos válidos: UN solo SELECT ... WHERE codigo IN (...) con los basenames del request.
$codigosValidos = array();
$codigosPedidos = array();
foreach ($archivos as $a) {
    $cod = codigoDesdeNombre($a['name']);
    if ($cod !== null) $codigosPedidos[$cod] = true;
}
if (!empty($codigosPedidos)) {
    try {
        $escapados = array();
        foreach (array_keys($codigosPedidos) as $cod) {
            $escapados[] = "'" . Connection::escape($cod) . "'";
        }
        $sql = "SELECT codigo FROM articulos WHERE codigo IN (" . implode(",", $escapados) . ")";
        $res = Connection::runQuery($sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $codigosValidos[(string) $row['codigo']] = true;
        }
    } catch (Exception $e) {
        // No se pudo consultar `articulos` → reportar error por cada archivo, sin guardar nada.
        $results = array();
        foreach ($archivos as $a) {
            $results[] = array('name' => $a['name'], 'codigo' => '', 'status' => 'error');
        }
        echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2) Procesar cada archivo.
$results = array();
$finfo = new finfo(FILEINFO_MIME_TYPE);
foreach ($archivos as $a) {
    $name = $a['name'];
    $cod  = codigoDesdeNombre($name);
    $status = 'error';

    if ($cod === null) {
        // Nombre inseguro / sin basename usable.
        $results[] = array('name' => $name, 'codigo' => '', 'status' => 'error');
        continue;
    }

    // Validar upload real.
    if ($a['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($a['tmp_name'])) {
        $results[] = array('name' => $name, 'codigo' => $cod, 'status' => 'error');
        continue;
    }

    // Tamaño ≤ 2MB.
    if ($a['size'] > $MAX_IMG_BYTES) {
        $results[] = array('name' => $name, 'codigo' => $cod, 'status' => 'badformat');
        continue;
    }

    // JPEG real: validar contenido (no el Content-Type del cliente, falsificable).
    $realMime = $finfo->file($a['tmp_name']);
    $isImage  = @getimagesize($a['tmp_name']) !== false;
    if ($realMime !== 'image/jpeg' || !$isImage) {
        $results[] = array('name' => $name, 'codigo' => $cod, 'status' => 'badformat');
        continue;
    }

    // ¿El código matchea un artículo? Si no → saltear (no guardar).
    if (!isset($codigosValidos[$cod])) {
        $results[] = array('name' => $name, 'codigo' => $cod, 'status' => 'nomatch');
        continue;
    }

    // Guardar como {codigo}.jpg.
    if (move_uploaded_file($a['tmp_name'], $dir . $cod . '.jpg')) {
        $status = 'ok';
    } else {
        $status = 'error';
    }
    $results[] = array('name' => $name, 'codigo' => $cod, 'status' => $status);
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
