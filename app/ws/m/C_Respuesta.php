<?php

include_once("../../config/Connection.php");


$json=$_POST["json"];
$dataJson= json_decode($json,true);

$consultaId = intval($dataJson["consultaId"] ?? 0);
$resolucion = Connection::escape($dataJson["resolucion"] ?? '');
$estado = Connection::escape($dataJson["estado"] ?? '');

$cant=Connection::runQuery("UPDATE `consultas` SET  `fecha_resolucion`=now(),`resolucion`='".$resolucion."',`estado`='".$estado."',`notificado`=1,`anulado`=0 WHERE `consultaId` = '". $consultaId."'");
Connection::runQuery("INSERT INTO `msj_consultas`(`id_consulta`, tipo,`fecha`, `mensaje`,respondido, `estado`,`canal`) VALUES (". $consultaId.",0,NOW(),'".$resolucion."',1,'".$estado."',1)");
Connection::runQuery("UPDATE `msj_consultas` SET `respondido`=1  WHERE `id_consulta` = ".$consultaId);echo $cant;

?>