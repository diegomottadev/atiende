<?php
require_once '../config/auth.php';
include_once("../config/Connection.php");
$estado= str_replace("#", " ", $_GET["estado"]);

$request=Connection::runQuery("SELECT reclamos.`reclamoId` AS Codigo,reclamos.fecha_ingreso as Fecha, reclamos.clienteId, clientes.razonSocial,clientes.direccion, clientes.vendedor, reclamos.telefono, reclamos.nick, reclamos.motivo,areas.area, reclamos.detalle, reclamos.fecha_resolucion,reclamos.resolucion, reclamos.estado FROM reclamos LEFT JOIN clientes ON reclamos.clienteId= clientes.codigo LEFT JOIN areas ON reclamos.area= areas.id where reclamos.estado like '".$estado."%' ");
while ($row = mysqli_fetch_assoc($request)){
    $export_data[] =array_map("utf8_encode", $row ); 

}


$fileName = "Reclamos.xls";
 
if ($export_data) {
    function filterData(&$str) {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }
 
    // headers for download
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Content-Type: application/vnd.ms-excel");
 
    $flag = false;
    foreach($export_data as $row) {
        if(!$flag) {
            // display column names as first row
            echo implode("\t", array_keys($row)) . "\n";
            $flag = true;
        }
        // filter data
        array_walk($row, 'filterData');
        echo implode("\t", array_values($row)) . "\n";
    }
    exit;           
}


?>