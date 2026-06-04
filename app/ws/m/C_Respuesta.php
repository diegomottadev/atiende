<?php

include_once("../../config/Connection.php");


$json=$_POST["json"];
$dataJson= json_decode($json,true);


$cant=Connection::runQuery("UPDATE `consultas` SET  `fecha_resolucion`=now(),`resolucion`='".$dataJson["resolucion"]."',`estado`='".$dataJson["estado"]."',`notificado`=1,`anulado`=0 WHERE `consultaId` = '". $dataJson["consultaId"]."'");
Connection::runQuery("INSERT INTO `msj_consultas`(`id_consulta`, tipo,`fecha`, `mensaje`,respondido, `estado`,`canal`) VALUES (". $dataJson["consultaId"].",0,NOW(),'".$dataJson["resolucion"]."',1,'".$dataJson["estado"]."',1)");
Connection::runQuery("UPDATE `msj_consultas` SET `respondido`=1  WHERE `id_consulta` = ".$dataJson["consultaId"]);echo $cant;

?>