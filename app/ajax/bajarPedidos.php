<?php
require_once '../config/auth.php';
include_once '../config/Connection.php';

Connection::setDatabase(!empty($_SESSION['tenant_db']) ? $_SESSION['tenant_db'] : DB_NAME);

$op = $_GET['op'] ?? '';

/*
 * CSRF-6: el endpoint hacía dos cosas en un mismo GET — generar el CSV (lectura)
 * y marcar los pedidos como bajados con UPDATE flag=1 (mutación). Una mutación
 * por GET no la cubre el header X-CSRF-Token (los GET no lo envían), así que se
 * separan los dos flujos:
 *   - GET (descarga CSV): lectura pura, NO marca nada.
 *   - POST op=marcar: marca flag=1 los pedidos pendientes, protegido con CSRF.
 * El front dispara la descarga y, al terminar, hace el POST a op=marcar.
 */

if ($op === 'marcar') {
    // Mutación → exige token CSRF.
    requireCsrf();
    header('Content-Type: application/json');

    // Marca como bajados todos los pedidos pendientes (flag=0).
    $update = Connection::runQuery("UPDATE `pedidos` SET `flag`=1 WHERE `flag`=0");
    echo json_encode(['ok' => (bool)$update], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Descarga CSV (lectura, sin mutar) ---
header('Content-Type: application/csv');
$filename = "pedidos" . date_timestamp_get(date_create()) . ".csv";
header('Content-Disposition: attachment; filename="' . $filename . '";');

$row  = array();
$data = "";

$request = Connection::runQuery("SELECT pedidos.* ,  DATE_FORMAT( fecha,'%d-%m-%Y %H:%i:%s') as fechaEnviado, clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `pedidos`, clientes where pedidos.clienteId= clientes.codigo and pedidos.flag =0 order by fecha desc ,clienteId ASC");

while ($row = mysqli_fetch_assoc($request)) {
    if ($row["producto"] == ".001")
        $data .= "\"" . $row["pedidoid"] . "\"," . "\"" . $row["clienteId"] . "\"," . "\"" . $row["fechaEnviado"] . "\"," . "\"" . $row["producto"] . "\"," . "\"" . $row["cantidad"] . "\"," . "\"" . $row["pagado"] . "\"," . "\"" . $row["razonSocial"] . "\"," . "\"" . $row["direccion"] . "\"," . "\"" . $row["vendedo"] . "\"," . "\"1\"," . "\"" . $row["descripcion"] . "\"\n";
    else
        $data .= "\"" . $row["pedidoid"] . "\"," . "\"" . $row["clienteId"] . "\"," . "\"" . $row["fechaEnviado"] . "\"," . "\"" . $row["producto"] . "\"," . "\"" . $row["cantidad"] . "\"," . "\"" . $row["pagado"] . "\"," . "\"" . $row["razonSocial"] . "\"," . "\"" . $row["direccion"] . "\"," . "\"" . $row["vendedo"] . "\"," . "\"1\"," . "\"" . $row["dato9"] . "\"\n";
}

header('Content-Length: ' . strlen($data));

echo $data;
?>
