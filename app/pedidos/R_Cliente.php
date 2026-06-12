<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');

$json=$_POST["json"];
$dataJson= json_decode($json,true);

// Saneo anti-SQLi: 'ped' es un id numérico (endpoint público sin auth).
$ped = intval($dataJson["ped"] ?? 0);
$req=ejecutarConsulta("SELECT cliente.telefono as telefono FROM pedidos as pedido inner  join clientes as cliente on pedido.clienteId= cliente.codigo where pedido.flag =0 and pedido.pedidoid = '".$ped."' order by pedido.pedidoid DESC limit 1");
$telefono = null;
 while ($reg = $req->fetch_object())  {
    $telefono = $reg->telefono;
}

echo json_encode($telefono);
//SELECT cliente.telefono as telefono FROM pedidos as pedido inner  join clientes as cliente on pedido.clienteId= cliente.codigo where pedido.pedidoid = '1449' order by pedido.pedidoid DESC limit 1;

?>
