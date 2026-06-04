<?php 

include_once("../Connection.php");


$request=Connection::runQuery("SELECT id,clientes.vendedor, clientes.razonSocial,clientes.direccion,`clienteId`,`fecha`,`producto`,`descripcion`,`cantidad`,`precio`,`descuento` FROM `pedidos`,clientes WHERE clientes.codigo=pedidos.clienteId and DATE(`fecha`) = DATE(NOW()) ORDER BY `clienteId` ");
while ($row = mysqli_fetch_assoc($request)){
    $export_data[] =array_map("utf8_encode", $row ); 

}


$fileName = "Pedidos.xls";
 
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