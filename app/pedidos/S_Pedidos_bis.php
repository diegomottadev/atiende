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
// Saneo anti-SQLi: TODO viene de $_POST['json'] en un endpoint público sin auth.
// Escapamos cada string con Connection::escape() y casteamos el id de link_pedidos a int.
$pedEsc  = intval($dataJson["ped"] ?? 0);
$telEsc  = Connection::escape($dataJson["telefono"] ?? '');
$nomEsc  = Connection::escape($dataJson["nombre"] ?? '');
$vendEsc = Connection::escape($vendedor);
for($i=0;$i<count($pedidos);$i++) {
    $codigoCliente = $pedidos[$i][5];
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
    // Resetear el contacto a estado inicial tras guardar el pedido, SIN borrar la sesión de
    // vendedor: REPLACE INTO borraba la fila y la recreaba con vendedor_codigo=NULL (el teléfono
    // del pedido es el del vendedor en su flujo), perdiendo la identidad. Con upsert se preserva.
    $request=Connection::runQuery("INSERT INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`) VALUES ('".$telEsc."','".$nomEsc."','".$telEsc."','0','0', now()) ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`), `telefono`=VALUES(`telefono`), `menu`='0', `esperaRespuesta`='0', `fechaHora`=now()");
    $request=Connection::runQuery("UPDATE `link_pedidos` SET estado= 1 where id =".$pedEsc);
}
$codCliEsc = Connection::escape($codigoCliente);
$row = mysqli_fetch_array(Connection::runQuery("SELECT pedidoid FROM pedidos where clienteId ='".$codCliEsc."'  ORDER BY fecha DESC limit 1 "));

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

