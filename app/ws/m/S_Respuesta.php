<?php

include_once("../../config/Connection.php");
require_once("../../config/global.php");
require_once("../../config/WhatsAppClient.php");
require_once("../../config/tenant_subdominio.php");
resolverTenantPorSubdominio(); // apuntar la conexión a la DB del tenant (POST sin login)
require_once("../../config/Conexion.php"); // define WA_PHONE_NUMBER_ID/WA_ACCESS_TOKEN del tenant (lee la sesión)

$json=$_POST["json"];
$dataJson= json_decode($json,true);

$reclamoId = intval($dataJson["reclamoId"] ?? 0);
$resolucion = Connection::escape($dataJson["resolucion"] ?? '');
$estado = Connection::escape($dataJson["estado"] ?? '');

$cant=Connection::runQuery("UPDATE `reclamos` SET  `fecha_resolucion`=now(),`resolucion`='".$resolucion."',`estado`='".$estado."',`notificado`=1,`anulado`=0 WHERE `reclamoId` = '". $reclamoId."'");
Connection::runQuery("INSERT INTO `msj_reclamos`(`id_reclamo`, tipo,`fecha`, `mensaje`,respondido, `estado`,`canal`) VALUES (". $reclamoId.",0,NOW(),'".$resolucion."',1,'".$estado."',1)");
Connection::runQuery("UPDATE `msj_reclamos` SET `respondido`=1  WHERE `id_reclamo` = ".$reclamoId);

// Envío de la respuesta del supervisor al cliente por la API directa de WhatsApp Cloud.
// Antes el texto salía por WebSocket/WEB_MASTER (método legacy) desde el JS de movil.php,
// que NO entrega en los tenants migrados a la API directa → el cliente nunca recibía la
// respuesta. Ahora se arma y envía server-side con WhatsAppClient (mismo método que el bot).
$estadoTxt  = $dataJson["estado"] ?? '';
$resolucionTxt = $dataJson["resolucion"] ?? '';
$recRes = Connection::runQuery("SELECT telefono, nick, motivo FROM reclamos WHERE reclamoId = $reclamoId");
$recRow = mysqli_fetch_assoc($recRes);
if ($recRow && !empty($recRow['telefono'])) {
    $telefono = preg_replace('/\D/', '', $recRow['telefono']);
    $waClient = new WhatsAppClient(
        defined('WA_PHONE_NUMBER_ID') ? WA_PHONE_NUMBER_ID : '',
        defined('WA_ACCESS_TOKEN')    ? WA_ACCESS_TOKEN    : '',
        WA_API_VERSION
    );

    $hora   = (int) date('H');
    $saludo = $hora < 12 ? 'Buenos Días' : (($hora < 19) ? 'Buenas Tardes' : 'Buenas Noches');

    $mensaje  = $saludo . ' *' . $recRow['nick'] . "* , tenemos novedades de su reclamo:\n";
    $mensaje .= '*Reclamo N°:* ' . $reclamoId . "\n";
    $mensaje .= '*Motivo:* ' . $recRow['motivo'] . "\n";
    $mensaje .= '*Fecha:* ' . date('Y-m-d H:i:s') . "\n";
    $mensaje .= '*Estado:* ' . $estadoTxt . "\n";
    $mensaje .= '*Resolución:* ' . $resolucionTxt . "\n";
    // Mientras el reclamo NO esté finalizado, se manda UN SOLO mensaje: la respuesta + la pregunta
    // (separadas por un salto de línea) con los botones "Sí, responder / No, gracias" debajo. Al tocar
    // "Sí" (webhook: reclamo_si → reclamo_conversacion) el cliente entra en conversación CONTINUA y
    // puede mandar varios mensajes que se cargan al hilo del supervisor, hasta que se finalice. Al
    // finalizar, se manda solo el texto (sin botones), se cierra la conversación y vuelve al menú.
    if ($estadoTxt === "Finalizado") {
        $waClient->sendText($telefono, $mensaje);
        Connection::runQuery("UPDATE contactos SET anterior='', esperaRespuesta=0, menu='0' WHERE id='$telefono'");
    } else {
        $antJson = addslashes(json_encode(['type' => 'reclamo_pregunta', 'idReclamo' => $reclamoId], JSON_UNESCAPED_UNICODE));
        Connection::runQuery("UPDATE contactos SET anterior='$antJson', esperaRespuesta=0 WHERE id='$telefono'");
        $waClient->sendInteractiveButtons($telefono, $mensaje . "\n¿Deseas responder sobre tu reclamo?", [
            ['id' => 'reclamo_si', 'title' => 'Sí, responder'],
            ['id' => 'reclamo_no', 'title' => 'No, gracias'],
        ]);
    }
}

echo $cant;

?>