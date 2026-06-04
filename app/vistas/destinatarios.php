<?php

header('Content-Type: application/json; charset=utf-8');

define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
require (__ROOT__.'/config/Connection.php');


$mensaje = ejecutarConsultaSimpleFila('SELECT * FROM `mensajes` where id='.$_GET["id"]);
$request = Connection::runQuery("SELECT  `id`, `mensaje`, `fecha`, `cantidad`, `destino`, `estado` FROM `mensajes` WHERE `id` like '" . $_GET["id"] . "'");
$json = "";
if ($request)
    while ($row = mysqli_fetch_assoc($request)) {
        $ramos = $row;
        $json = $row["destino"];
    }
$obj = json_decode($json, true);

$and = " WHERE 0";

if (count($obj[4]) > 0) {
    $codigo = substr(json_encode($obj[4]), 1, -1);
    $and = ' where  codigo IN (' . $codigo . ')';
}

$rows['clientes'] = array();
//$request = Connection::runQuery("SELECT codigo,telefono,SUBSTRING_INDEX(razonSocial,' ',-1) as nombre  FROM  clientes " . $and);

$request = Connection::runQuery("SELECT DISTINCT (SELECT telefono  FROM  telefonos where clienteId = contacto.codigo ORDER BY telefono DESC limit 1) as telefono, contacto.codigo, contacto.razonSocial, contacto.direccion, contacto.zona, contacto.ramo, contacto.localidad FROM clientes contacto INNER JOIN telefonos telefono ON contacto.codigo = telefono.clienteId" . $and);
$enviados = 0;

if ($request) {

    while ($row = mysqli_fetch_assoc($request)) {
//        $telefonoSql = Connection::runQuery("SELECT telefono  FROM  telefonos where clienteId = " . $row["codigo"] . " ORDER BY telefono DESC limit 1");
//        while ($rowTel = mysqli_fetch_assoc($telefonoSql)) {
            $enviados = 0;
            //$row["telefono"] = $rowTel["telefono"];
            $rows['clientes'][] = $row;
            $enviados++;
        //}
    }

    $request=Connection::runQuery("UPDATE `mensajes` SET `cantidad`= ".$enviados." , fecha = now() WHERE `id` = ".$_GET["id"]);

    //antes sin relacionar con la tabla de telefonos
//    while ($row =  mysqli_fetch_assoc($request)){
//        if(strlen ($row["telefono"]) >8){
//            $row["telefono"]=$row["telefono"];
//            $rows['clientes'][] = $row ;
//            $enviados++;
//        }
//
//    }
//
//    $request=Connection::runQuery("UPDATE `mensajes` SET `cantidad`= ".$enviados." , fecha = now() WHERE `id` = ".$_GET["id"]);
}
$params =  [
    "contacts" =>  $rows['clientes'],
    "menssages" => $mensaje
];
echo json_encode($params, JSON_UNESCAPED_UNICODE);
?>