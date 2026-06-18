<?php
// DEPRECATED: reemplazado por ws/webhook.php + modelos/BotEngine.php (WhatsApp Cloud API).
// Mantener como referencia histórica. No usar en producción.
// SEGURIDAD: endpoint legacy SIN verificación de firma HMAC y con SQLi no autenticada →
// neutralizado. El webhook real (firmado) es ws/webhook.php. Para reactivarlo habría que
// portarlo a prepared statements + validación X-Hub-Signature-256.
http_response_code(410);
exit('Gone');
include_once("../config/Connection.php");
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/global.php');
require (__ROOT__."/config/Conexion.php");

date_default_timezone_set('America/Argentina/Buenos_Aires');

$responseWebMaster = getWebMasterConfig();
////die(json_encode($responseWebMaster));
//$ch = [
//    "data"=> [
//        "identificador" => 1,
//        "empresa"=> [
//            "identificador"=> 1,
//            "nombre"=> "atiende",
//            "telefono"=> "5493743474282",
//            "json"=>'{
//  "empresaId": "1",
//  "empresa": "Atiende",
//  "menu": [
//    {
//      "menuId": 0,
//      "consigna": "<saludo> *<nombre>* ☺ \n ¡Un gusto saludarte! | *Empresa Demo*\n\n ✅  Elige una opción colocando una letra:\n",
//      "menuIdB": "6",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "A",
//          "opcion": "Pedidos",
//          "menuId": "10",
//          "guardar": false
//        },
//        {
//          "opcionId": "B",
//          "opcion": "Reclamo",
//          "menuId": "1",
//          "guardar": false
//        },
//        {
//          "opcionId": "C",
//          "opcion": "Consultas",
//          "menuId": "14",
//          "guardar": false
//        },
//        {
//          "opcionId": "D",
//          "opcion": "Novedades",
//          "menuId": "13",
//          "guardar": false
//        },
//        {
//          "opcionId": "F",
//          "opcion": "Finalizar",
//          "menuId": "2.2",
//          "guardar": false,
//          "area": ""
//        }
//
//      ]
//    },
//    {
//      "menuId": 1,
//      "consigna": "*✅ Elíge una opción por favor:*\n",
//      "finaliza": false,
//      "menuItem": []
//    },
//    {
//      "menuId": 2,
//      "consigna": "☑ Gracias <nombre> *por confiar en nosotros*‼",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 2.1,
//      "consigna": "☑ *Gracias* por consutar. Te esperamos pronto‼",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 2.2,
//      "consigna": "*☑ Te esperamos pronto‼*",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 3,
//      "consigna": "☹ Desde ya te pedimos disculpas por los inconvenientes, en breve nos ocuparemos del tema.",
//      "finaliza": true,
//      "menuItem": []
//    },
//    {
//      "menuId": 4,
//      "consigna": "*Upps‼*  Quedó un menu anterior abierto o ingresaste una opción no válida.\n\n Podés escribirme ahora nuevamente.",
//      "finaliza": false,
//      "menuItem": []
//    },
//    {
//      "menuId": 5,
//      "consigna": "Escríbeme una breve descripción.\n\n*No* | Audios | Fotos | Videos.\n",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "3",
//          "guardar": true,
//          "area": "NO ASIGNADO",
//          "motivo": "Otros"
//        }
//      ]
//    },
//    {
//      "menuId": 6,
//      "consigna": "<saludo> *<nombre>*\n¡Un gusto saludarte‼ | *Empresa Demo*\n\nPara que tengas una mejor experiencia *agendá nuestro número.*\n\n ✅ Elíge una opción escribiendo sólo la letra:\n",
//      "finaliza": false,
//      "menuItem": [
//        {"opcionId": "A","opcion": "Ya soy cliente", "menuId": "7","guardar":false,"area":"NO ASIGNADO"},
//        {"opcionId": "B","opcion": "Quiero ser cliente", "menuId": "8","guardar":false,"area":"NO ASIGNADO"},
//        {"opcionId": "C","opcion": "Ya soy vendedor", "menuId": "16","guardar":false,"area":"NO ASIGNADO"},
//        {"opcionId": "D","opcion": "¿Que te ofrecemos?", "menuId": "13","guardar":false,"area":"NO ASIGNADO"},
//        {"opcionId": "E","opcion": "Salir", "menuId": "2","guardar":false,"area":""}
//
//      ]},
//    {
//      "menuId": 7,
//      "consigna": "Escribe tu número de cliente. Es el mismo que figura en la factura\n\n*No se admiten*|Audio|Fotos|Videos",
//      "finaliza": false,
//      "menuItem":  [{"opcionId": "","opcion": "", "menuId": "0","guardar":false,"area":"NO ASIGNADO","accion":"registraNumero"}]
//    },
//    {
//      "menuId": 8,
//      "consigna": " *¿Cual es su Nombre?* ",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "801",
//          "guardar": false
//        }
//      ]
//    },
//    {
//      "menuId": 801,
//      "consigna": "*¿Cual es su direccion?*\nEj: Calle numero dpto piso ",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "802",
//          "guardar": false
//        }
//      ]
//    },
//    {
//      "menuId": 802,
//      "consigna": "*¿Cuál es su Ubicación?*\n\uD83D\uDCCD Ingresa al clip\uD83D\uDCCE.  \n\uD83D\uDCCD Selecciona *Ubicación*. \n\uD83D\uDCCD Luego *Enviar mi ubicación actual*\n",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "804",
//          "guardar": false,
//          "accion": "registraClientes"
//        }
//      ]
//    },
//    {
//      "menuId": 9,
//      "consigna": "Ingresá el número de reclamo a consultar ",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "2",
//          "guardar": false,
//          "area": "NO ASIGNADO",
//          "accion": "consultarReclamo"
//        }
//      ]
//    },
//    {
//      "menuId": 10,
//      "consigna": "☑ Hace clic en el link y hace tu pedido:\n\nPedido mínimo *$1.000,00* <linkPedidos>",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 11,
//      "consigna": "Quedó un pedido anterior pendiente‼\n\n*✅ Elegí la forma de pago para aceptarlo*.\nDe lo contrario ingresá la opción de cancelar pedido.\n",
//      "finaliza": false,
//      "menuItem":  [
//        {"opcionId": "A","opcion": "Pagar contra entrega", "menuId": "202","guardar":false,"accion":"confirmaPedido"},
//        {"opcionId": "B","opcion": "Transferencia bancaria", "menuId": "200","guardar":false,"accion":"confirmaTransferencia"},
//        {"opcionId": "C","opcion": "Cuenta corriente", "menuId": "203","guardar":false,"accion":"confirmaCuentaCorriente"},
//        {"opcionId": "D","opcion": "Cancelar Pedido", "menuId": "2.1","guardar":false,"accion":"cancelaPedido"}
//      ]
//
//    },
//    {
//      "menuId": 200,
//      "consigna": "Transferir a:\n\nBANCO PATAGONIA\nRazon Social: WhatsEmpresa\nCuit: 20-00000000-1\nCTA CTE $ 000-000000-000\nCBU: 00000000000000000000\nAlias: WHATS.BUS.SUPERBOT\n\n ☺ *Gracias por tu compra‼*",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 202,
//      "consigna": "Abonás en el momento de la entrega en efectivo.\n\n ☺ *Gracias por tu compra‼*",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 203,
//      "consigna": "Su pedido va ser evaluado, en base a la condición de pago que eligió *Cuenta Corriente*.\n\n ☺ *Gracias por tu compra‼*",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 20,
//      "consigna": "Gracias *<nombre>* Por confiar en *Empresa Demo*\n\n Su pedido llegará en los días asigandos de entrega",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 12,
//      "consigna": "<saludo> *<nombre>*, \n ✅ *Elíge* una opción:",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "A",
//          "opcion": "Hacer un pedido",
//          "menuId": "10",
//          "guardar": false
//        },
//        {
//          "opcionId": "B",
//          "opcion": "Consultar pedido",
//          "menuId": "2",
//          "guardar": false
//        },
//        {
//          "opcionId": "C",
//          "opcion": "Finalizar",
//          "menuId": "2",
//          "guardar": false
//        }
//      ]
//    },
//    {
//      "menuId": 13,
//      "consigna": "✅ *Elíge* una opción *<nombre>* :\n",
//      "finaliza": false,
//      "palabraClave": [],
//      "menuItem": [{
//        "opcionId": "L",
//        "opcion": "Lista de precios",
//        "menuId": "133",
//        "guardar": false
//      },
//      {
//        "opcionId": "O",
//        "opcion": "Ofertas vigentes",
//        "menuId": "134",
//        "guardar": false
//      },
//      {
//        "opcionId": "S",
//        "opcion": "Salir",
//        "menuId": "2.2",
//        "guardar": false,
//        "area": ""
//      }
//      ]
//    },
//    {
//      "menuId": 133,
//      "consigna": "✅ Hace clic en el link para ver nuestra lista de precios *<nombre>*\n\nis.gd/LJQXWi",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 134,
//      "consigna": "Hace clic en el link para ver nuestras ofertas *<nombre>*\n\nhttps://n9.cl/r9e6n",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 19,
//      "consigna": "*Para desvicular numero debe confirmar la operacion:*",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "1",
//          "opcion": "Desvincular numero",
//          "menuId": "2.1",
//          "guardar": false,
//          "accion": "bajaBot"
//        },
//        {
//          "opcionId": "2",
//          "opcion": "Cancelar",
//          "menuId": "2",
//          "guardar": false
//        }
//      ]
//    },
//    {
//      "menuId": 2.1,
//      "consigna": "La baja se realizó correctamente. Gracias!",
//      "finaliza": true,
//      "palabraClave": [],
//      "menuItem": []
//    },
//    {
//      "menuId": 900,
//      "consigna": "✅ Para consultar nuestros catalogos registrate e ingresa a la opcion de hacer un pedido.",
//      "finaliza": true,
//      "palabraClave": ["catalogo"],
//      "menuItem": []
//    },
//    {
//      "menuId": 2100,
//      "consigna": "☺ Si puedo contarte un chiste...\n*Mujer preocupada*\ny le preguntan...  por que estas preocupada?\n-Lo mande a mi marido a comprar *ravioles* y lo atropello un auto...\n-uhh... y ahora que vas a hacer?\n-No se... *Churrascos?*\n\n*cuack‼*\n\nVuelve a escribirme para ingresar al menu",
//      "finaliza": true,
//      "palabraClave":["chiste"],
//      "menuItem":  []
//    },
//    {
//      "menuId": 2200,
//      "consigna": "☺ Todo Bien‼ *Muchas gracias*\n\nVuelve a escribirme para ingresar al menu ",
//      "finaliza": true,
//      "palabraClave":["cómo estás","como estás","cómo estas","como estas","que onda","que haces","que tal"],
//      "menuItem":  []
//    },
//    {
//      "menuId": 2300,
//      "consigna": "☺ Soy un asistente virtual y cada dia aprendo mas.\n\nVuelve a escribirme para ingresar al menu\n*Gracias* ‼",
//      "finaliza": true,
//      "palabraClave":["humano","persona","robot","sos un bot","maquina"],
//      "menuItem":  []
//    },
//    {
//      "menuId": 7.1,
//      "consigna": "✅ Ingrese su numero de pedido que desea cancelar\nRecorda que no puedo escuchar *audios*, ni ver *fotos* y *videos*.",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "3",
//          "guardar": true,
//          "area": "CANCELACIONES",
//          "motivo": "cancelaPedido"
//        }
//      ]
//    },
//    {
//      "menuId": 14,
//      "consigna": "*✅ Elíge una opción por favor:*\n",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "S",
//          "opcion": "Salir",
//          "menuId": "2.2",
//          "guardar": false,
//          "area": ""
//        }
//      ]
//    },
//    {
//      "menuId": 15,
//      "consigna": "Escríbeme una breve descripción.\n\n*No* | Audios | Fotos | Videos.\n",
//      "finaliza": false,
//      "menuItem": [
//        {
//          "opcionId": "",
//          "opcion": "",
//          "menuId": "0",
//          "guardar": false,
//          "area": "NO ASIGNADO",
//          "motivo": "Otros",
//          "accion": "registrarConsulta"
//        }
//      ]
//    },
//    {
//      "menuId": 16,
//      "consigna": "• Indícame cual es el  N° de cliente para gestionar:\n",
//      "finaliza": false,
//      "menuItem":  [{"opcionId": "","opcion": "", "menuId": "10","guardar":false,"area":"NO ASIGNADO","accion":"chequearVendedorCliente"}]
//    }
//  ]
//}'
//        ],
//        "mp"=> false,
//        "b2b"=> true,
//        "b2c"=> false,
//        "mix"=> false,
//        "ftp"=> false,
//        "fechaCreacion"=> "2022-09-22 16:16:57",
//        "fechaActualizacion"=> "2022-09-22 16:16:57",
//        "fechaEliminacion"=> null
//    ]
//];
//$responseWebMaster = $ch ;

$data =file_get_contents('php://input') ;
$obj = json_decode($data, TRUE);
//-----------------VARIABLES HLOBALES-------------------------

saveLog($data,"json_");
$empresa = $obj["token"];
//echo "Empresa: ". $empresa;
//echo "token: ".$obj["type"]."<br>";
$type=$obj["msg"]['msg']['type'];
$body=$obj["msg"]['msg']['body'];
if (array_key_exists("user",$obj["msg"]['msg']['senderObj']['id']))
    $user =$obj["msg"]['msg']['senderObj']['id']['user'];
else
    $user =$obj["msg"]['msg']['chat']['id']['user'];

$pushname= preg_replace('([^A-Za-z0-9])', '', $obj["msg"]['msg']['senderObj']['pushname']);
//$telefonoEmpres=$obj["me"]['user'];
//die(json_encode($telefonoEmpres));

//if($type!="chat")

//--------------------------------------------------------------
//-----------------INICIO DE PROCESO----------------------------

$miTelefono="";
//$request=Connection::runQueryLogin("SELECT `json`, telefono FROM `empresa` WHERE `empresa` like  '".$empresa."'  ");
//if( mysqli_num_rows ($request )>0){
//    $row = mysqli_fetch_assoc($request);
//    $menu=$row["json"];
//    $miTelefono=$row["telefono"];
//}

//die(json_encode($responseWebMaster['data']['empresa']));

$menu = $responseWebMaster['data']['empresa']['json'];
$miTelefono =  $responseWebMaster['data']['empresa']['telefono'];
//$menu = utf8_encode (file_get_contents("menu.json"));

$_menu = json_decode($menu, TRUE);

$menuJson= $_menu["menu"];

$menuID="0";
$esperaRespusta="0";
$contactoMensaje="";
$ctrlLocationSendByChat = null;
$request=Connection::runQuery("SELECT menu,esperaRespuesta,anterior FROM contactos where telefono LIKE '".$user."'  ");
if( mysqli_num_rows ($request )>0){
    $row = mysqli_fetch_assoc($request);
    $menuID=$row["menu"];
    $esperaRespusta=$row["esperaRespuesta"];
    if($menuID == 802){
        $ctrlLocationSendByChat = $row["anterior"];
    }
    //$anterior=json_decode($row["anterior"], TRUE)["opcion"];

}

$codigoCliente="";
//para activar menu cliente
$request=Connection::runQuery("SELECT clienteId FROM `telefonos` WHERE `telefono` = '".$user."' ");
if( mysqli_num_rows ($request )>0){
    $row = mysqli_fetch_assoc($request);
    $codigoCliente=$row["clienteId"];
}
//--------------PALABRAS CLAVES------------------------------------------
if(strlen($codigoCliente)>0){
    $menuclave=buscarMenuClave($menuJson,$body);
    if(strlen($menuclave)>0)
        $menuID=$menuclave;

}

//Para activar menu vendedor
//$request=Connection::runQuery("SELECT count(*) as existe,codigo  FROM vendedores where telefono = '".$user."'  ");
//if( mysqli_num_rows ($request )>0) {
//    $rowVendedor = mysqli_fetch_assoc($request);
//    if($rowVendedor['existe'] > 0) {
//        $request=Connection::runQuery("SELECT menu,esperaRespuesta,anterior FROM contactos where telefono = '".$user."'  ");
//        if( mysqli_num_rows ($request )>0){
//            $row = mysqli_fetch_assoc($request);
//            $menuID=$row["menu"];
//    }
//}



//-------------------------MOTIVOS DE RECLAMOS----------------------------------------
//----------------------------------------------------------------------

$rows =array();
$request=Connection::runQuery("SELECT `opcionId`, `opcion`, `menuId`, IF(guardar, 'true', 'false') guardar, `area` FROM `menuitem`  ");
if($request){
    while ($row =  mysqli_fetch_assoc($request)){
        $rows[] =  $row;
    }
    $rows[] = [
        "opcionId"=> "S",
        "opcion"=> "Salir",
        "menuId"=> "2.2",
        "guardar"=> false,
        "area"=> ""
    ];

    $menuJson[1]["menuItem"]=$rows;
}
$rows =array();
$request=Connection::runQuery("SELECT `opcionId`, `opcion`, `menuId`, IF(guardar, 'true', 'false') guardar, `area` FROM `motivo_consultas`");
if($request) {
    while ($row = mysqli_fetch_assoc($request)) {
        $rows[] =  $row;
    }
    $rows[] = [
        "opcionId" => "S",
        "opcion" => "Salir",
        "menuId" => "2.2",
        "guardar" => false,
        "area" => ""
    ];
}

$menuJson[31]["menuItem"]=$rows;




//----------------PROCESAR ACCICON----------------------------------------

//echo buscarMenuClave($menuJson,$body);
if(strcasecmp($body,"bajacp") !== 0 ){
    if($type==="chat"){
        //echo sendChat($user,json_encode($body));
        procesarAccion($menuJson,$menuID,$esperaRespusta, $body,$pushname,$user,$codigoCliente,$responseWebMaster) ;
    }
    else if($type==="location"&& $menuID == 802 && $ctrlLocationSendByChat !=null){
        //https://maps.google.com/maps?q=-34.8713724,-58.5437346
//        echo sendChat($user,json_encode([$obj["msg"]['msg']['lat'],$obj["msg"]['msg']['lng']]));
        $body = json_encode([$obj["msg"]['msg']['lat'],$obj["msg"]['msg']['lng']]);
        procesarAccion($menuJson,$menuID,$esperaRespusta, $body,$pushname,$user,$codigoCliente,$responseWebMaster) ;
        //echo sendChat($user,json_encode([$obj["msg"]['msg']['lat'],$obj["msg"]['msg']['lng']]));
    }
    else{
        $user =$obj["msg"]['msg']['chat']['id']['user'];
        $pushname= $obj["msg"]['msg']['senderObj']['pushname'];
        echo sendChat($user,"No esta admitido mensaje de tipo ".$obj["msg"]['msg']['type']);
    }
}else{
    $request=Connection::runQuery("DELETE FROM `telefonos` WHERE `telefono` like '".$user."' ");
    Connection::runQuery("UPDATE `contactos` SET `anterior`= '' , menu = '0',  esperaRespuesta= 0  where id like '".$user."' ");
    echo sendChat($user,"La baja se realizo correctamente. Gracias!");
    //int estado = estatuto.executeUpdate("DELETE FROM `telefonos` WHERE `telefono` like '"+telefono+"' ");
}




//------------------------------------------------------------------------

function file_get_contents_utf8($content) {

    return mb_convert_encoding($content, 'UTF-8',
        mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
}


function  procesarAccion($menuJson,$menu,$esperaRespuesta,$mensaje,$pushname,$user,$codigoCliente,$responseWebMaster){
    //echo sendChat($user,json_encode(['Menu ===>',$menu]));
    $mensajePorcesado="";
    $espera_respuesta="0";
    $numeroReclamo="";
    $numeroConsulta="";
    global $empresa;
    global $contactoMensaje;

    $request=Connection::runQuery("SELECT menu,esperaRespuesta,anterior FROM contactos where telefono LIKE '".$user."'  ");
//    echo sendChat($user,json_encode( mysqli_fetch_assoc($request)));
//    die();
    if( mysqli_num_rows ($request )>0){
        $row = mysqli_fetch_assoc($request);
        $anterior=json_decode($row["anterior"], TRUE)["opcion"];

    }

    if($menu=="0"){
        if(strlen($codigoCliente)==0)
            $menu=$menuJson[0]["menuIdB"];

    }

    for($i=0; $i<count($menuJson); $i++) {

        if($menuJson[$i]["menuId"]==$menu){

            if($esperaRespuesta=="0"){


                $mensajePorcesado= $menuJson[$i]["consigna"]."\n";

                $hayMenuItem=false;

                $menuItem=$menuJson[$i]["menuItem"];

                for( $j=0; $j < count($menuItem); $j++){
                    $opciones =  $menuItem[$j]["opcion"];
                    if(strlen($opciones)>0)
                        $mensajePorcesado.="*".$menuItem[$j]["opcionId"]."*. ".$menuItem[$j]["opcion"]."\n";
                    $hayMenuItem=true;
                }


                if($hayMenuItem)$espera_respuesta="1";
                if($menuJson[$i]["finaliza"]=="true")
                    $menu="0";

                registrarContacto($pushname,$user,$menu,$espera_respuesta);


                $mensajePorcesado=str_replace("<saludo>",getSaludo(), $mensajePorcesado);
                $mensajePorcesado=str_replace("<nombre>",$pushname, $mensajePorcesado);

                $notiPedido="";
                $notiEncuesta="";
                if(strpos($mensajePorcesado,"<linkPedidos>")!== false){
                    $mensajePorcesado= str_replace("<linkPedidos>","", $mensajePorcesado); //$mensajePorcesado.replace("<linkPedidos>","");
                    $notiPedido=Connection::runQueryID("INSERT INTO `link_pedidos`(`clienteId`, `telefono`,token, `fecha`, `estado`) VALUES ('".$codigoCliente."','".$user."','".$empresa."',now(),0)");
                }
                if(strpos($mensajePorcesado,"<linkPromo>")!== false){// if(mensajePorcesado.indexOf("<linkPromo>")>=0){
                    $mensajePorcesado= str_replace("<linkPromo>","", $mensajePorcesado);//mensajePorcesado= mensajePorcesado.replace("<linkPromo>","");
                    $notiPedido="promo";
                }
                if(strpos($mensajePorcesado,"<linkEncuesta>")!== false){// if(mensajePorcesado.indexOf("<linkEncuesta>")>=0){
                    $mensajePorcesado= str_replace("<linkEncuesta>","", $mensajePorcesado);// mensajePorcesado= mensajePorcesado.replace("<linkEncuesta>","");
                    $notiEncuesta=$codigoCliente;
                }

                echo  sendChat($user,$mensajePorcesado);
                if(strlen($notiPedido)>0){
                    $vendedorR = "";
                    $requestVend=Connection::runQuery("SELECT atencion  FROM vendedores where telefono= '".$user."'");
                    if( mysqli_num_rows ($requestVend )>0) {
                        $rowVendedor = mysqli_fetch_assoc($requestVend);
                        if($rowVendedor['atencion'] !== null){
                            Connection::runQuery("UPDATE `link_pedidos` SET `clienteId`= '".$rowVendedor['atencion']."'  where id = '".$notiPedido."'  ");
                            $requestVendedor=Connection::runQuery("SELECT codigo  FROM vendedores where atencion= '".$rowVendedor['atencion']."'  ");
                            if( mysqli_num_rows ($requestVendedor )>0) {
                                $rowVendedor = mysqli_fetch_assoc($requestVendedor);
                                $vendedorR = $rowVendedor['codigo'];
                                Connection::runQuery("UPDATE `vendedores` SET `atencion`= ''  where telefono= '".$user."'");
                            }
                        }else{
                            $vendedorR = "";
                        }
                    }


                    if(strpos($notiPedido,"promo")!== false){

                        $request=Connection::runQuery("SELECT * FROM `promo` where estado =0");
                        $p=1;
                        if($request)
                            while ($row =  mysqli_fetch_assoc($request)){
                                echo sendLinkPromos(tenantUrl($empresa,'/chatbot/promos/view.php?id='.$row["id"].'&cli='.$codigoCliente),$user,$row["descripcion"],$row["base64"],$row["precio"]);
                                usleep(1000);
                                $p++;
                            }

                    }
                    else if($vendedorR!== ""){
                        //echo sendLink(__PROD__."/".$empresa."/pedidos/index.php?ped=".$notiPedido."&ved=".$vendedorR,$user,"Pedidos");
                        echo sendLink(tenantUrl($empresa,'/pedidos/index.php?ped='.$notiPedido.'&ved='.$vendedorR),$user,"Pedidos");
                       // echo sendLink(__SHOPPING_CART__."/pedidos/index.php?ped=".$notiPedido."&ved=".$vendedorR,$user,"Pedidos");
                    }
                    else
                       //echo sendLink(__PROD__."/".$empresa."/pedidos/index.php?ped=".$notiPedido,$user,"Pedidos");
                       echo sendLink(tenantUrl($empresa,'/pedidos/index.php?ped='.$notiPedido),$user,"Pedidos");
                       //echo sendLink(__SHOPPING_CART__."/pedidos#/".$notiPedido,$user,"Pedidos");
                }
                if($notiEncuesta!=""){
                    echo sendLink(tenantUrl($empresa,'/chatbot/encuesta/index.php?encu='.$notiEncuesta),$user,"Ax-Encuesta");
                }

            }else{


                $esOpcionValida=false;
                $numeroReclamo ="";
                $numeroConsulta ="";
                $menuItem=$menuJson[$i]["menuItem"];
                for($j=0; $j < count($menuItem); $j++){

                    if(strcasecmp ($menuItem[$j]["opcionId"], $mensaje) == 0 || strlen($menuItem[$j]["opcion"])==0){

                        if( strlen($menuItem[$j]["opcion"] )>0){
                            $mensajePorcesado = "Selecciono: *" . $menuItem[$j]["opcion"] . "*\n";
                        }else{
                            $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= CONCAT(`mensaje`,'_','".$mensaje."')  where id = '".$user."'  ");
                        }

                        if($menuItem[$j]["guardar"] =="true"){
                            if (isset($menuItem[$j]["accion"])) {

                            } else{

                                //* AQUI EMPIEZA REGISTRO DE RECLAMOS*//

                                $motivo="";
                                $detalle="";
                                if(strlen($menuItem[$j]["opcion"] )>0){
                                    $motivo=$menuItem[$j]["opcion"] ;
                                }
                                else{
                                    if (isset($menuItem[$j]["motivo"]))
                                        $motivo= $menuItem[$j]["motivo"];

                                    $detalle= $mensaje;
                                }
                                //-----------VERIFICAR ANTERIOR---------------------------------/
                                $_area=$menuItem[$j]["area"];
                                $_motivo=$motivo;
                                $request=Connection::runQuery("SELECT anterior FROM contactos where telefono LIKE '".$user."'  ");
                                if( mysqli_num_rows ($request )>0){
                                    $row = mysqli_fetch_assoc($request);
                                    if( strlen ($row["anterior"]) >0){
                                        $_anterior = json_decode($row["anterior"], TRUE);
                                        $_area=$_anterior["area"];
                                        $_motivo=$_anterior["opcion"];
                                    }

                                }

                                $numeroReclamo =Connection::runQueryID("INSERT INTO `reclamos`(empresa,`fecha_ingreso`,`clienteId`, `telefono`,nick, `motivo`, `area`, `detalle`, resolucion) VALUES ('".$empresa."',now(),'".$codigoCliente."','".$user."','".$pushname."','".$_motivo."','".$_area."','".$detalle."','')");
                                $request=Connection::runQuery("UPDATE `contactos` SET `anterior`= '' where id like '".$user."' ");
                                $request=Connection::runQuery("SELECT telefono,area FROM `areas` WHERE `id` = '".$_area."' ");
                                if( mysqli_num_rows ($request )>0){
                                    $row = mysqli_fetch_assoc($request);
                                    $telResponsable=$row["telefono"];
                                    $areaResponsable = $row["area"];
                                    if (strlen($row["telefono"])>0){
                                        $request = null;
                                        if($responseWebMaster['data']['b2b'])
                                        {
                                            $request=Connection::runQuery("SELECT *  FROM clientes WHERE  `codigo` =  '".$codigoCliente."' ");
                                        }else{
                                            $request=Connection::runQuery("SELECT *  FROM clientes WHERE  `id` = $codigoCliente");
                                        }

                                        if( mysqli_num_rows ($request )>0){
                                            $row = mysqli_fetch_assoc($request);
                                            $razonSocial=$row["razonSocial"];
                                            $vendedor=$row["vendedor"];
                                            $direccion=$row["direccion"];
                                        }

                                        $resultado="*‼️Este reclamo te ha sido informado porque estás asignado como supervisor del área ".$areaResponsable."*\n\n";
                                        $resultado.="Hay un nuevo reclamo de *".$pushname."*:\n".
                                            "*Reclamo N°:* ".$numeroReclamo."\n".
                                            "*Cliente:* ".$razonSocial."\n".
                                            "*Dirección* ".$direccion."\n".
                                            "*Vendedor:* ".$vendedor."\n".
                                            "*Motivo:* ".$_motivo."\n".
                                            "*Fecha:* ".strftime( "%Y-%m-%d %H:%M:%S", time() )."\n".
                                            "*Tel:* ".substr($user, 3)."\n\n".
                                            //"*Responder:* ".__PROD__."/".$empresa."/ws/m/movil.php?id=".$numeroReclamo."\n";
                                            "*Responder:* ".tenantUrl($empresa,'/responder/reclamo/'.$numeroReclamo)."\n";
                                        echo sendChat(trim($telResponsable), $resultado );
                                    }
                                }

                                //*  TERMINA REGISTRO DE RECLAMOS EN LA DB Y EL ENVIO DE POR WHATSAPP DEL RECLAMO GENERADO*//
                            }

                        }else{

                            if (isset($menuItem[$j]["accion"])) {

                                if($menuItem[$j]["accion"]==="chequearVendedorCliente"){
                                    $request=Connection::runQuery("SELECT count(*) as existe, codigo  FROM vendedores where telefono = '".$user."'  ");
                                    if( mysqli_num_rows ($request )>0){
                                        $rowVendedor = mysqli_fetch_assoc($request);
                                        if($rowVendedor['existe'] > 0){
                                            $requesteCiente=Connection::runQuery("SELECT razonSocial, codigo  FROM clientes where codigo =  '".$mensaje."' and vendedor= '".$rowVendedor['codigo']."'");
                                            //echo sendChat($user,json_encode("SELECT razonSocial, codigo  FROM clientes where codigo =  '".$mensaje."' and vendedor= '".$rowVendedor['codigo']."'"));
                                            if( mysqli_num_rows ($requesteCiente )>0) {
                                                $rowCliente = mysqli_fetch_assoc($requesteCiente);
                                                Connection::runQuery("UPDATE `vendedores` SET `atencion`= '".$rowCliente['codigo']."'  where codigo like '".$rowVendedor['codigo'] ."'");
                                                echo  sendChat($user,"Cliente: ".$rowCliente["razonSocial"]);
                                            }else{
                                                echo  sendChat($user,"No se encuentra registrado como vendedor. ");
                                                $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                                die();
                                            }
                                        }
                                        else{
                                            echo  sendChat($user,"No se encuentra registrado como vendedor. ");
                                            $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                            die();
                                        }

                                    }
                                }

                                if($menuItem[$j]["accion"]=="registraNumero"){
                                    $cli="";
                                    //-----------------------getRazonZocial-----------
                                    $request = null;

                                    if($responseWebMaster['data']['b2b'])
                                    {
                                        $request=Connection::runQuery("SELECT razonSocial,codigo  FROM clientes WHERE  `codigo` =  '".$mensaje."' ");
                                    }else{
                                        $request=Connection::runQuery("SELECT razonSocial,id  FROM clientes WHERE  `id` =  '".$mensaje."' ");
                                    }


                                    if( mysqli_num_rows ($request )>0){
                                        $row = mysqli_fetch_assoc($request);
                                        $cli=$row["razonSocial"];
                                        $codigoCliente = null;
                                        if($responseWebMaster['data']['b2b'])
                                        {
                                            $codigoCliente=$row["codigo"];
                                        }else{
                                            $codigoCliente=$row["id"];
                                        }
                                    }

                                    if(strlen ($cli) >0){
                                        $request=Connection::runQuery("REPLACE INTO `telefonos`( `clienteId`, `telefono`, `activo`)  VALUES ('".$codigoCliente."','".$user."',1)");

                                    }else{
                                        echo sendChat($user, "El codigo de cliente no es valido.");
                                    }
                                }


                                if($menuItem[$j]["accion"]=="consultarReclamo") {

                                    $re=consultarReclamo(trim($mensaje),$user);

                                    if(strlen ($re)>0){
                                        //echo "Resultado:".$re;
                                        echo  sendChat($user,str_replace("<saludo>",getSaludo(), $re));

                                    }else{
                                        // echo "El numero de reclamo no es valido.";
                                        echo   sendChat($user,"El numero de reclamo no es valido.");
                                        // sendMensaje(session,user,"El numero de reclamo no es valido.");
                                    }
                                }
                                if($menuItem[$j]["accion"]=='registraClientes'){
                                    if($responseWebMaster['data']['mix'] || $responseWebMaster['data']['b2c'] ){
                                        $request=Connection::runQuery("SELECT menu,esperaRespuesta,anterior,mensaje FROM contactos where telefono = '".$user."'  ");
                                        if( mysqli_num_rows ($request )>0){
                                            $row = mysqli_fetch_assoc($request);
                                            $anterior=json_decode($row["anterior"], TRUE)["opcion"];
                                            $contactoMensaje= $row["mensaje"];
                                        }
                                        $porciones = explode("_",$contactoMensaje );
                                        $location = substr($porciones[3], 1, -1);
                                        $partLocation = explode(",",$location );
                                        if ($porciones[1]!=null || $porciones[1]!="" ) {

                                            $codigoCliente = Connection::runQueryId("INSERT INTO `clientes`(`codigo`,`vendedor`, `razonSocial`, `direccion`, `ramo`, `zona`, `lista`,`latitud`,`longitud`,`deposito`,`telefono`) VALUES ('','1','" . $porciones[1] . "','" . $porciones[2] . "','RAMO','SIN_ZONA','1','" . $partLocation[0] . "','" . $partLocation[1] . "',1,'" . $user . "')");
                                            if (strlen($codigoCliente) > 0) {
                                                //---------- registrarNumero-------------
                                                $request = Connection::runQuery("REPLACE INTO `telefonos`( `clienteId`, `telefono`, `activo`)  VALUES ('" . $codigoCliente . "','" . $user . "',1)");
                                                $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                                echo sendChat($user, "Perfecto, ya te registramos!!");
                                            }
                                        }
                                        else{
                                            echo sendChat($user, "Hubo un error en la carga de la solicitud, disculpe 😔 \nPasos a seguir: \n1) Escribirnos nuevamente al chat. \n2) Elija la opción A.");
                                            $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                            die();
                                        }
                                    }else if($responseWebMaster['data']['b2b']){
                                        $request=Connection::runQuery("SELECT menu,esperaRespuesta,anterior,mensaje FROM contactos where telefono = '".$user."'  ");
                                        if( mysqli_num_rows ($request )>0){
                                            $row = mysqli_fetch_assoc($request);
                                            $anterior=json_decode($row["anterior"], TRUE)["opcion"];
                                            $contactoMensaje= $row["mensaje"];
                                        }
                                        $porciones = explode("_",$contactoMensaje );

                                        $location = substr($porciones[3], 1, -1);
                                        $partLocation = explode(",",$location );

                                        if ($porciones[1]!=null || $porciones[1]!="" ){
                                            $response=Connection::runQueryID("INSERT INTO `solicitudes`(`nombre`, `direccion`, `telefono`,`fecha`,`latitud`,`longitud`) VALUES ('".$porciones[1]."','".$porciones[2]."','".$user."',NOW(),'".$partLocation[0]."','".$partLocation[1]."') ");
                                            if(strlen ($response) >0){
                                                $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                                echo sendChat($user, "A la brevedad será informado el estado de su solicitud de cliente.");
                                                die();
                                            }
                                        }else{
                                            echo sendChat($user, "Hubo un error en la carga de la solicitud, disculpe, reintente nuevamente"."😔");
                                            $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                            die();
                                        }
                                    }
                            }
                            if($menuItem[$j]["accion"]=="confirmaPedidoFTP") {

                                $row = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '".$codigoCliente."' "));
                                $request=Connection::runQuery("UPDATE pedidos SET flag = 0 WHERE pedidoid like '".$row["max"]."' ");
                                //*--------- GUARDAR PEDIDO EN LA CARPETA CSV------------------------------------------ */
                                $req=Connection::runQuery("SELECT pedidos.* ,  DATE_FORMAT( fecha,'%d-%m-%Y %H:%i:%s') as fechaEnviado, clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `pedidos`, clientes where pedidos.clienteId= clientes.codigo and pedidos.flag =0 and pedidoid like '".$row["max"]."' order by fecha desc ,clienteId ASC");
                                while ($row = mysqli_fetch_assoc($req)){
                                    if ($row["producto"]==".001")
                                        $csv.="\"".$row["pedidoid"]."\","."\"".$row["clienteId"]."\","."\"".$row["fechaEnviado"]."\","."\"".$row["producto"]."\","."\"".$row["cantidad"]."\","."\"".$row["pagado"]."\","."\"".$row["razonSocial"]."\","."\"".$row["direccion"]."\","."\"".$row["vendedo"]."\",\"\",\"1\",\"".$row["descripcion"]."\"\n";
                                    else
                                        $csv.="\"".$row["pedidoid"]."\","."\"".$row["clienteId"]."\","."\"".$row["fechaEnviado"]."\","."\"".$row["producto"]."\","."\"".$row["cantidad"]."\","."\"".$row["pagado"]."\","."\"".$row["razonSocial"]."\","."\"".$row["direccion"]."\","."\"".$row["vendedo"]."\",\"\",\"1\",\"".$row["dato9"]."\"\n";
                                    $update=Connection::runQuery("UPDATE `pedidos` SET `flag`=1 where  id =".$row["id"]);
                                }
                                $filename="../csv/pedidos/"."pedidos".$codigoCliente.date_timestamp_get(date_create()) ;
                                saveLog($csv,$filename);
                            }

                            if($menuItem[$j]["accion"]=="confirmaPedido") {

                                $row = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '".$codigoCliente."' "));
                                $request=Connection::runQuery("UPDATE pedidos SET flag = 0, pagado = 2 WHERE pedidoid like '".$row["max"]."' ");
                                sendWap($codigoCliente,$row["max"],$user);

                            }
                            if($menuItem[$j]["accion"]=="confirmaTransferencia") {

                                $row = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '".$codigoCliente."' "));
                                $request=Connection::runQuery("UPDATE pedidos SET flag = 0, pagado = 3 WHERE pedidoid like '".$row["max"]."' ");

                                sendWap($codigoCliente,$row["max"],$user);

                            }
                            if($menuItem[$j]["accion"]=="confirmaCuentaCorriente") {

                                $row = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '".$codigoCliente."' "));
                                $request=Connection::runQuery("UPDATE pedidos SET flag = 0, pagado = 4 WHERE pedidoid like '".$row["max"]."' ");

                                sendWap($codigoCliente,$row["max"],$user);

                            }
                            if($menuItem[$j]["accion"]=="contactoVendedor") {

                                sendContacto($codigoCliente,$user);

                            }

                            if($menuItem[$j]["accion"]=="contactoVendedor") {

                                sendContacto($codigoCliente,$user);

                            }

                            if($menuItem[$j]["accion"]=="registrarConsulta"){
                                $request=Connection::runQuery("SELECT anterior FROM contactos where telefono LIKE '".$user."'  ");
                                if( mysqli_num_rows ($request )>0){
                                    $row = mysqli_fetch_assoc($request);
                                    if( strlen ($row["anterior"]) >0){
                                        $_anterior = json_decode($row["anterior"], TRUE);
                                        $_area=$_anterior["area"];
                                        $_motivo=$_anterior["opcion"];
                                    }
                                }

                                $consultaSql =              "INSERT INTO `consultas`(`empresa`,
                                                                                `fecha_ingreso`,
                                                                                `clienteId`, 
                                                                                `telefono`,
                                                                                `nick`, 
                                                                                `motivo`, 
                                                                                `area`, 
                                                                                `detalle`, 
                                                                                `resolucion`) 
                                                                                VALUES (        '".$empresa."',
                                                                                                now(),
                                                                                                '".$codigoCliente."',
                                                                                                '".$user."',
                                                                                                '".$pushname."',
                                                                                                '".$_motivo."',
                                                                                                '".$_area."',
                                                                                                '".$mensaje."',
                                                                                                '')
                                                                                ";
                                $numeroReclamo= "";
                                $numeroConsulta =Connection::runQueryID($consultaSql);
                                Connection::runQuery("UPDATE `contactos` SET `anterior`= '' where id like '".$user."' ");
                                $request=Connection::runQuery("SELECT telefono FROM `areas_consultas` WHERE `id` = '".$_area."' ");
                                if( mysqli_num_rows ($request )>0){
                                    $row = mysqli_fetch_assoc($request);
                                    $telResponsable=$row["telefono"];
                                    if (strlen($row["telefono"])>0){
                                        $request = null;
                                        if($responseWebMaster['data']['b2b'])
                                        {
                                            $request=Connection::runQuery("SELECT *  FROM clientes WHERE  `codigo` =  '".$codigoCliente."' ");
                                        }else{
                                            $request=Connection::runQuery("SELECT *  FROM clientes WHERE  `id` = $codigoCliente");
                                        }

                                        if( mysqli_num_rows ($request )>0){
                                            $row = mysqli_fetch_assoc($request);
                                            $razonSocial=$row["razonSocial"];
                                            $vendedor=$row["vendedor"];
                                            $direccion=$row["direccion"];
                                        }

                                        $resultado="Hay un nueva consulta de *".$pushname."* :\n";
                                        $resultado.="*Consulta N°:* ".$numeroConsulta."\n".
                                            "*Cliente:* ".$razonSocial."\n".
                                            "*Dirección* ".$direccion."\n".
                                            "*Vendedor* ".$vendedor."\n".
                                            "*Motivo:* ".$_motivo."\n".
                                            "*Fecha:* ".strftime( "%Y-%m-%d %H:%M:%S", time() )."\n".
                                            "*Tel:* ".substr($user, 3)."\n".
//                                            "*Responder:* ".__PROD__."/".$empresa."/ws/m/movilc.php?id=".$numeroConsulta."\n";
                                             "*Responder:* ".tenantUrl($empresa,'/responder/consulta/'.$numeroConsulta)."\n";

                                        echo sendChat(trim($telResponsable), $resultado );
                                    }
                                    $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                                }
                            }
                        }
                        if (isset($menuItem[$j]["area"]))
                            $request=Connection::runQuery("UPDATE `contactos` SET `anterior`= '".json_encode($menuItem[$j],JSON_UNESCAPED_UNICODE)."' where id = '".$user."' ");
                    }

                    if(strlen ($numeroReclamo)>0 && strlen ($numeroConsulta)==0 && $numeroReclamo!=0){
                        $mensajePorcesado=$mensajePorcesado."Reclamo N°: *".intval($numeroReclamo)."*" ;

                    }
                    if(strlen ($numeroConsulta)>0){

                        $consultaMensaje="Consulta N°: *".intval($numeroConsulta)."*"."\n".
                            "En breve nos ocuparemos de tu consulta.";
                        $mensajePorcesado=$mensajePorcesado.$consultaMensaje ;
                        echo sendChat($user, $mensajePorcesado);
                        $request=Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '".$user."'  ");
                        $mensajePorcesado = "";
                        die();
                    }
                    if(strlen($mensajePorcesado)>0){
                        echo sendChat($user, $mensajePorcesado);

                    }

                    procesarAccion($menuJson,$menuItem[$j]["menuId"],"0", $mensaje,$pushname,$user,$codigoCliente,$responseWebMaster) ;
                    $esOpcionValida=true;

                }
            }
            if(!$esOpcionValida){

                procesarAccion($menuJson,"4","0", $mensaje,$pushname,$user,$codigoCliente,$responseWebMaster) ;

                if($menuItem[$j]["finaliza"]=="true")
                    $menu="0";
                registrarContacto($pushname,$user,$menu,$espera_respuesta);

            }
        }
    }
}
}


function  consultarReclamo($reclamoId,$user ) {

    $resultado="";
    $request=Connection::runQuery("SELECT *  FROM  reclamos WHERE   telefono  like '".$user ."'  and  reclamoId=".intval($reclamoId));

    if( mysqli_num_rows ($request) >0 ){

        $row = mysqli_fetch_assoc($request);

        $resolucion="";
        if($row["resolucion"]=="null" )
            $resolucion=$row["resolucion"];
        else
            $resolucion=$row["estado"];
        // resolucion= res.getString("estado");

        $resultado="<saludo> *".$row["nick"]."* :\n";
        $resultado.="*Reclamo N°:* ".$row["reclamoId"]."\n".
            "*Motivo:* ".$row["motivo"]."\n".
            "*Fecha:* ".$row["fecha_ingreso"]."\n".
            "*Estado:* ".$row["estado"]."\n".
            "*Resolucion:* ".$row["resolucion"];
        // echo  "resultado: ".$resultado;
    }

    return $resultado;

}


function registrarContacto($pushname,$user,$menu,$espera_respuesta){

    $request=Connection::runQuery("INSERT INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`) VALUES ('".$user."','".$pushname."','".$user."','".$menu."','".$espera_respuesta."', now()) ON DUPLICATE KEY UPDATE nombre='".$pushname."' ,menu='".$menu."', esperaRespuesta='".$espera_respuesta."',fechaHora=now() ");
}

function getSaludo(){
    $hora= date("H");
    //echo "hora: ".$hora;
    $saludo="Buenas noches";

    if($hora>=6 && $hora<= 12)
        $saludo="Buenos dias";
    if($hora>12 && $hora<= 19)
        $saludo="Buenas tardes";
    if($hora>19 && $hora<= 24)
        $saludo="Buenas noches ";
    return  $saludo;

}


function buscarMenuClave($menuJson,$str){
    $res="";

    for($i=0; $i<count($menuJson); $i++) {

        if (isset($menuJson[$i]["palabraClave"])) {

            //JSONArray array= (JSONArray) menuJson.get("palabraClave");
            $arrayPalabras=$menuJson[$i]["palabraClave"];
            for($j=0;$j<count($arrayPalabras);$j++ ){
                if (preg_match("/".$arrayPalabras[$j]."/i",$str )){
                    // if($str.toLowerCase().indexOf(array.get(j).toString().toLowerCase())>=0){
                    $res=$menuJson[$i]["menuId"];//menuJson.get("menuId").toString();
                    $j=count($arrayPalabras);
                }
            }
        }

    }
    return $res;
}
//-----------------METODO ENVIAR MENSAJE-------------------------
function sendChat($telefono,$text)
{
    $mensaje = array(
        "cmd"=>"chat",
        "msg" =>  array("to"=>$telefono,"custom_uid"=>$telefono."".rand(),"body" =>  array("text"=>$text))

    );

    return  json_encode($mensaje,JSON_UNESCAPED_UNICODE)."<ms>";
}


//---------------LINK ----------------------------------------------
function  sendLink($url,$telefono,$title){
    $thumb="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";
    $mensaje = array(
        "cmd"=>"media",
        "msg" =>  array("to"=>$telefono,"custom_uid"=>$telefono."".rand(),"body" =>  array("title"=>$title,"desc"=>"Atiende","url"=>$url,"thumb"=>$thumb))

    );

    return json_encode($mensaje,JSON_UNESCAPED_UNICODE)."<ms>";


}

function  sendLinkPromos($url,$telefono,$title,$thumb,$desc){
    $mensaje = array(
        "cmd"=>"media",
        "msg" =>  array("to"=>$telefono,"custom_uid"=>$telefono."".rand(),"body" =>  array("title"=>$title,"desc"=>$desc,"url"=>$url,"thumb"=>$thumb))

    );

    return json_encode($mensaje,JSON_UNESCAPED_UNICODE)."<ms>";


}

function sendContacto($clienteID,$tel) {

    $request=Connection::runQuery("SELECT `vendedor`,razonSocial,direccion FROM `clientes` WHERE `codigo`  like '".$clienteID."' ");
    $razonSocial="";
    $direccion="";
    if( mysqli_num_rows ($request )>0){
        $row = mysqli_fetch_assoc($request);
        $vendedor=$row["vendedor"];
        $razonSocial=$row["razonSocial"];
        $direccion=$row["direccion"];

    }
    //sendChat("5493764329554","SELECT `vendedor` FROM `clientes` WHERE `codigo`  like '".$clienteID."' ");
    $request=Connection::runQuery("SELECT telefono,nombre FROM `vendedores` WHERE  codigo like '".$vendedor."'  ");
    $telefono="";
    $nombreVend="";
    if( mysqli_num_rows ($request )>0){
        $row = mysqli_fetch_assoc($request);
        $telefono=$row["telefono"];
        $nombreVend=$row["nombre"];

    }

    if(strlen($telefono)>0){

        //SELECT * FROM `pedidos` WHERE `pedidoid`
        $pedidos="Fecha: ".date("d/m/Y H:i:s")."\n";
        $pedidos.="------------------------------\n";
        $pedidos.="*Cliente:* ".$clienteID."\n";
        $pedidos.=$razonSocial."\n";
        $pedidos.="------------------------------\n";
        $pedidos.="*Domicilio:* ".$direccion."\n";
        $pedidos.="------------------------------\n";
        $pedidos.="*Tel:* ".$tel."\n";
        $pedidos.="------------------------------\n";
        $pedidos.="El cliente necesita que te comuniques urgente... AX-BOT";

        echo sendChat("549".$telefono,$pedidos);

        $pedidos="En breve el vendedor se comunicara\n";
        $pedidos.="*Vendedor:* ".$nombreVend."\n";
        // $pedidos.="*Tel:* ".$telefono."\n";
        echo sendChat($tel,$pedidos);
        // `producto`, `descripcion`, `cantidad`


    }

}

function sendWap($clienteID,$pedidoid,$tel) {
    $request=Connection::runQuery("SELECT `vendedor` FROM `clientes` WHERE `codigo`  like '".$clienteID."' ");
    $vendedor="";
    if( mysqli_num_rows ($request )>0){
        $row = mysqli_fetch_assoc($request);
        $vendedor=$row["vendedor"];

    }
    $request=Connection::runQuery("SELECT telefono FROM `vendedores` WHERE  codigo like '".$vendedor."'  ");
    $telefono="";
    if( mysqli_num_rows ($request )>0){
        $row = mysqli_fetch_assoc($request);
        $telefono=$row["telefono"];

    }

    if(strlen($telefono)>0){

        $pedidos="Fecha: ".date("d/m/Y H:i:s")."\n";
        $pedidos.="------------------------------\n";
        $pedidos.="Cliente: ".$clienteID."\n";
        $pedidos.="------------------------------\n";
        $pedidos.="Tel: ".$tel."\n";
        $pedidos.="------------------------------\n";

        $request=Connection::runQuery("SELECT * FROM `pedidos` WHERE `pedidoid` like '".$pedidoid."'  ");
        $total=0;
        if($request)
            while ($row =  mysqli_fetch_assoc($request)){
                $pedidos.="```".str_pad($row["producto"], 10, " ")."```".$row["cantidad"]."\n";
                $total= $total+ floatval($row["subtotal"]) ;
            }
        $pedidos.="------------------------------\n";
        $pedidos.="*Total: $". number_format($total, 2, '.', '')."*\n";
        $pedidos.="------------------------------\n";
        echo sendChat("549".$telefono,$pedidos);

    }

}

function sendGCM($clienteID) {

    $request=Connection::runQuery("SELECT `token` FROM tokenfcm,clientes WHERE tokenfcm.vendedor=clientes.vendedor and clientes.codigo like '".$clienteID."' ORDER BY `fecha` DESC LIMIT 1 ");
    $token="";
    if( mysqli_num_rows ($request )>0){
        $row = mysqli_fetch_assoc($request);
        $token=$row["token"];

    }

    if(strlen($token)>0){

        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array (
            'registration_ids' => array ($token ),
            'data' => array (
                "message" => "pedidowhatsapp",
                "title" => "axbot",
            )
        );
        $fields = json_encode ( $fields );
        $headers = array (
            'Authorization: key=' . "AAAAom3Mn_E:APA91bE0YFdv11LZu44chGNBehn7Ab2xe7NfnAzsmY8DZBTB3gDNEszLxKpe6-YL6Azsa4FRgJ0GjfXxIb76Mf-2Y8nP30JnA6zbCOa2Mqq6qzZPImSWANgVn_f3uwJGB4731hy9AzV4",
            'Content-Type: application/json'
        );

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

        $result = curl_exec ( $ch );
        // echo $result;
        curl_close ( $ch );

    }
}

function saveLog($json,$nombre ){
    //$nombre =date("dmY_His");
    $fp = fopen($nombre.".txt","w+b");
    if( $fp == false ){
        //  echo "Error al crear el archivo";
    }else{
        fwrite($fp,$json);
        fclose($fp);
        //echo "1";
    }
}

?>