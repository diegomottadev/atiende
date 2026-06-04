<?php

include_once("../config/Connection.php");

// Tenant routing: read slug from POST ?t= param
$_t = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['t'] ?? ''));
if ($_t !== '') {
    Connection::setDatabase('atiende_' . $_t);
}
error_log('[S_Pedidos_bis] t=' . ($_POST['t'] ?? '(none)') . ' db=' . ($_t !== '' ? 'atiende_'.$_t : 'default'));
unset($_t);

function saveLog($json,$nombre ){
    //$nombre =date("dmY_His");
    $fp = fopen($nombre.".csv","w+b");
    if( $fp == false ){
        echo "Error al crear el archivo";
        //echo "0";
        //do debugging or logging here
    }else{
        fwrite($fp,$json);
        fclose($fp);
        //echo "1";
    }
}
$response = getWebMasterConfig();

$json=$_POST["json"];
$dataJson= json_decode($json,true);

$pedidos= $dataJson["mensaje"];
$vendedor = $dataJson["ved"]!=="" ? $dataJson["ved"] : null ;//echo $pedidos[0][0];
$cant=0;
$codigoCliente = null;
for($i=0;$i<count($pedidos);$i++) {
    $codigoCliente = $pedidos[$i][5];
    $insert= Connection::runQuery("INSERT INTO `pedidos`(`clienteId`, `fecha`, `producto`, `descripcion`, `cantidad`, `precio`, `descuento`,pedidoid,telefono, dato9, `subtotal`, `flag`,`vendedorId`)
     VALUE ('".$pedidos[$i][5]."',now(),'".$pedidos[$i][0]."','".$pedidos[$i][1]."','".$pedidos[$i][2]."','".$pedidos[$i][4]."','0','".$dataJson["ped"]."','".$dataJson["telefono"]."','".$pedidos[$i][6]."','".$pedidos[$i][3]."','-1','".$vendedor."')");
    if($insert)
        $cant++;
}
if($cant>0){
    $request=Connection::runQuery("REPLACE INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`) VALUES ('".$dataJson["telefono"]."','".$dataJson["nombre"]."','".$dataJson["telefono"]."','0','0', now())  ");
    $request=Connection::runQuery("UPDATE `link_pedidos` SET estado= 1 where id =".$dataJson["ped"]);
}
$row = mysqli_fetch_array(Connection::runQuery("SELECT pedidoid FROM pedidos where clienteId ='".$codigoCliente."'  ORDER BY fecha DESC limit 1 "));

Connection::runQuery("UPDATE pedidos SET flag = 0 WHERE pedidoid = '".$row["pedidoid"]."' ");

/*  Descomentar si es necesario para subirlo a un ftp */

if($response['data']['ftp']){
    $req = null;
    if($response['data']['mix'] || $response['data']['b2c']){
        $req=Connection::runQuery("SELECT pedidos.* ,  DATE_FORMAT( fecha,'%d-%m-%Y %H:%i:%s') as fechaEnviado, clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `pedidos`, clientes where pedidos.clienteId=clientes.id and pedidos.flag =0 and pedidoid = '".$row["pedidoid"]."' order by fecha desc ,clienteId ASC");
    }else if($response['data']['b2b']){
        $req=Connection::runQuery("SELECT pedidos.* ,  DATE_FORMAT( fecha,'%d-%m-%Y %H:%i:%s') as fechaEnviado, clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `pedidos`, clientes where pedidos.clienteId=clientes.codigo and pedidos.flag =0 and pedidoid = '".$row["pedidoid"]."' order by fecha desc ,clienteId ASC");
    }
    $csv = null;
    while ($row = mysqli_fetch_assoc($req)){
        if ($row["producto"]==".001"){
            $csv.="\"".$row["pedidoid"]."\","."\"".$row["clienteId"]."\","."\"".$row["fechaEnviado"]."\","."\"".$row["producto"]."\","."\"".$row["cantidad"]."\","."\"".$row["pagado"]."\","."\"".$row["razonSocial"]."\","."\"".$row["direccion"]."\","."\"".$row["vendedor"]."\",\"\",\"1\",\"".$row["descripcion"]."\"\n";
        }
        else{
            $csv.="\"".$row["pedidoid"]."\","."\"".$row["clienteId"]."\","."\"".$row["fechaEnviado"]."\","."\"".$row["producto"]."\","."\"".$row["cantidad"]."\","."\"".$row["pagado"]."\","."\"".$row["razonSocial"]."\","."\"".$row["direccion"]."\","."\"".$row["vendedor"]."\",\"\",\"1\",\"".$row["dato9"]."\"\n";
        }
        Connection::runQuery("UPDATE `pedidos` SET `flag`=1 where  id =".$row["id"]);
    }
    $filename="../csv/pedidos/"."pedidos".$codigoCliente.date_timestamp_get(date_create()) ;
    saveLog($csv,$filename);
}

/* fin descomentar */

echo $cant;

?>

