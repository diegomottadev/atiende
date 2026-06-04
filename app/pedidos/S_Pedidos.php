<?php 

include_once("../config/Connection.php");

//Connection::runQuery("INSERT INTO `pedidos`(`clienteId`, `fecha`, `producto`, `descripcion`, `cantidad`, `precio`, `descuento`,pedidoid, `subtotal`, `flag`) VALUE ()");

$json=$_POST["json"];
$dataJson= json_decode($json,true);

$pedidos= $dataJson["mensaje"];
$vendedor = $dataJson["ved"]!=="" ? $dataJson["ved"] : null ;
$cant=0;
for($i=0;$i<count($pedidos);$i++) {
    $insert= Connection::runQuery("INSERT INTO `pedidos`(`clienteId`, `fecha`, `producto`, `descripcion`, `cantidad`, `precio`, `descuento`,pedidoid,telefono, dato9, `subtotal`, `flag`,`vendedorId`)
     VALUE ('".$pedidos[$i][5]."',now(),'".$pedidos[$i][0]."','".$pedidos[$i][1]."','".$pedidos[$i][2]."','".$pedidos[$i][4]."','0','".$dataJson["ped"]."','".$dataJson["telefono"]."','".$pedidos[$i][6]."','".$pedidos[$i][3]."','-1','".$vendedor."')");
    if($insert)
    $cant++;
}
if($cant>0){
$request=Connection::runQuery("REPLACE INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`) VALUES ('".$dataJson["telefono"]."','".$dataJson["nombre"]."','".$dataJson["telefono"]."','11','1', now())  ");
$request=Connection::runQuery("UPDATE `link_pedidos` SET estado= 1 where id =".$dataJson["ped"]);
}
echo $cant;

?>