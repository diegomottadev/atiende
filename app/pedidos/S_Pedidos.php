<?php 

include_once("../config/Connection.php");

//Connection::runQuery("INSERT INTO `pedidos`(`clienteId`, `fecha`, `producto`, `descripcion`, `cantidad`, `precio`, `descuento`,pedidoid, `subtotal`, `flag`) VALUE ()");

$json=$_POST["json"];
$dataJson= json_decode($json,true);

$pedidos= $dataJson["mensaje"];
$vendedor = $dataJson["ved"]!=="" ? $dataJson["ved"] : null ;
$cant=0;
// Saneo anti-SQLi: todo viene de $_POST['json'] (endpoint público sin auth).
$pedEsc  = intval($dataJson["ped"] ?? 0);
$telEsc  = Connection::escape($dataJson["telefono"] ?? '');
$nomEsc  = Connection::escape($dataJson["nombre"] ?? '');
$vendEsc = Connection::escape($vendedor);
for($i=0;$i<count($pedidos);$i++) {
    $cli  = Connection::escape($pedidos[$i][5]);
    $prod = Connection::escape($pedidos[$i][0]);
    $dsc  = Connection::escape($pedidos[$i][1]);
    $cnt  = Connection::escape($pedidos[$i][2]);
    $prc  = Connection::escape($pedidos[$i][4]);
    $d9   = Connection::escape($pedidos[$i][6]);
    $sub  = Connection::escape($pedidos[$i][3]);
    $insert= Connection::runQuery("INSERT INTO `pedidos`(`clienteId`, `fecha`, `producto`, `descripcion`, `cantidad`, `precio`, `descuento`,pedidoid,telefono, dato9, `subtotal`, `flag`,`vendedorId`)
     VALUE ('".$cli."',now(),'".$prod."','".$dsc."','".$cnt."','".$prc."','0','".$pedEsc."','".$telEsc."','".$d9."','".$sub."','-1','".$vendEsc."')");
    if($insert)
    $cant++;
}
if($cant>0){
// Upsert en vez de REPLACE INTO: preserva vendedor_codigo (REPLACE borraba la fila y la
// recreaba sin la sesión de vendedor, cuyo teléfono es el del que carga el pedido).
$request=Connection::runQuery("INSERT INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`) VALUES ('".$telEsc."','".$nomEsc."','".$telEsc."','11','1', now()) ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`), `telefono`=VALUES(`telefono`), `menu`='11', `esperaRespuesta`='1', `fechaHora`=now()");
$request=Connection::runQuery("UPDATE `link_pedidos` SET estado= 1 where id =".$pedEsc);
}
echo $cant;

?>