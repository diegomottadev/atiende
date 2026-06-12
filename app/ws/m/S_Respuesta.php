<?php

include_once("../../config/Connection.php");
require_once("../../config/global.php");
require_once("../../config/WhatsAppClient.php");

$json=$_POST["json"];
$dataJson= json_decode($json,true);

$reclamoId = intval($dataJson["reclamoId"] ?? 0);
$resolucion = Connection::escape($dataJson["resolucion"] ?? '');
$estado = Connection::escape($dataJson["estado"] ?? '');

$cant=Connection::runQuery("UPDATE `reclamos` SET  `fecha_resolucion`=now(),`resolucion`='".$resolucion."',`estado`='".$estado."',`notificado`=1,`anulado`=0 WHERE `reclamoId` = '". $reclamoId."'");
Connection::runQuery("INSERT INTO `msj_reclamos`(`id_reclamo`, tipo,`fecha`, `mensaje`,respondido, `estado`,`canal`) VALUES (". $reclamoId.",0,NOW(),'".$resolucion."',1,'".$estado."',1)");
Connection::runQuery("UPDATE `msj_reclamos` SET `respondido`=1  WHERE `id_reclamo` = ".$reclamoId);

if ($dataJson["estado"] === "En analisis") {
    $idReclamo = intval($dataJson["reclamoId"]);
    $recRes = Connection::runQuery("SELECT telefono FROM reclamos WHERE reclamoId = $idReclamo");
    $recRow = mysqli_fetch_assoc($recRes);
    if ($recRow && !empty($recRow['telefono'])) {
        $telefono = preg_replace('/\D/', '', $recRow['telefono']);
        $antJson = addslashes(json_encode(['type' => 'reclamo_pregunta', 'idReclamo' => $idReclamo], JSON_UNESCAPED_UNICODE));
        Connection::runQuery("UPDATE contactos SET anterior='$antJson', esperaRespuesta=0 WHERE id='$telefono'");
        $waClient = new WhatsAppClient(WA_PHONE_NUMBER_ID, WA_ACCESS_TOKEN, WA_API_VERSION);
        $waClient->sendInteractiveButtons($telefono, '¿Deseas responder a la empresa sobre tu reclamo?', [
            ['id' => 'reclamo_si', 'title' => 'Sí, responder'],
            ['id' => 'reclamo_no', 'title' => 'No, gracias'],
        ]);
    }
}

echo $cant;

?>