<!DOCTYPE html>
<html lang="en">
<title>Listo</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
  

  
</head>
<body >

<div class="container">
<?php 
    define('__ROOT__', dirname(dirname(__FILE__)));
    require(__ROOT__ . '/config/global.php');
    require(__ROOT__ . '/config/Connection.php');
    // Tenant routing from ?t= or fallback by pedidoid
    $_ft = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['t'] ?? ''));
    if ($_ft !== '') {
        Connection::setDatabase('atiende_' . $_ft);
    } elseif (!empty($_GET['ped'])) {
        $_fPed = intval($_GET['ped']);
        try {
            $_fPdo = new PDO('mysql:host='.DB_HOST.';port=3306;dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            foreach ($_fPdo->query("SELECT db_name FROM tenants WHERE deleted_at IS NULL AND estado='activo'")->fetchAll(PDO::FETCH_COLUMN) as $_fDB) {
                $_fLink = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, $_fDB);
                if (!$_fLink) continue;
                $_fRes = mysqli_query($_fLink, "SELECT pedidoid FROM pedidos WHERE pedidoid=$_fPed LIMIT 1");
                if ($_fRes && mysqli_num_rows($_fRes) > 0) { Connection::setDatabase($_fDB); mysqli_close($_fLink); break; }
                mysqli_close($_fLink);
            }
            unset($_fPdo, $_fDB, $_fLink, $_fRes, $_fPed);
        } catch (Exception $_fe) { /* silent */ }
    }
    unset($_ft);
    $telefono = null;
    $cliente = null;
    $csv = null;
    $empresa = explode("/", $_SERVER["REQUEST_URI"])[1];
    if ($_GET["resu"]=="success") 
    {

        Connection::runQuery("UPDATE `pedidos` SET `pagado`=1,flag =0  WHERE `pedidoid` like   '".$_GET["ped"]."' ");
        //$request=Connection::runQuery("UPDATE `pedidos` SET `pagado`=1, flag=0 WHERE `pedidoid` like   '".$_GET["ped"]."' ");

        //$request=Connection::runQuery("SELECT telefono FROM `pedidos` WHERE `pedidoid` like  '".$_GET["ped"]."' ");
      
        //$row = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '".$codigoCliente."' "));
        //$request=Connection::runQuery("UPDATE pedidos SET flag=1 WHERE pedidoid like '".$row["max"]."' ");
        //*--------- GUARDAR PEDIDO EN LA CARPETA CSV------------------------------------------ */  

        $req=Connection::runQuery("SELECT pedidos.* ,  DATE_FORMAT( fecha,'%d-%m-%Y %H:%i:%s') as fechaEnviado, clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `pedidos`, clientes where pedidos.clienteId= clientes.codigo and pedidos.flag =0 and pedidoid like '".$_GET["ped"]."' order by fecha desc ,clienteId ASC");
      
        while ($row = mysqli_fetch_assoc($req)){
            if ($row["producto"]==".001"){
                $csv.="\"".$row["pedidoid"]."\","."\"".$row["clienteId"]."\","."\"".$row["fechaEnviado"]."\","."\"".$row["producto"]."\","."\"".$row["cantidad"]."\","."\"".$row["pagado"]."\","."\"".$row["razonSocial"]."\","."\"".$row["direccion"]."\","."\"".$row["vendedo"]."\",\"\",\"1\",\"".$row["descripcion"]."\"\n";   
            }
            else{
                $csv.="\"".$row["pedidoid"]."\","."\"".$row["clienteId"]."\","."\"".$row["fechaEnviado"]."\","."\"".$row["producto"]."\","."\"".$row["cantidad"]."\","."\"".$row["pagado"]."\","."\"".$row["razonSocial"]."\","."\"".$row["direccion"]."\","."\"".$row["vendedo"]."\",\"\",\"1\",\"".$row["dato9"]."\"\n";
            }
            $cliente = $row["clienteId"];
            $update=Connection::runQuery("UPDATE `pedidos` SET `flag`=1 where  id =".$row["id"]);                     
        }
    
        $filename="../csv/pedidos/"."pedidos".$cliente.date_timestamp_get(date_create()) ; 
        saveLog($csv,$filename);

        $request = Connection::runQuery("SELECT telefono FROM `pedidos` WHERE `pedidoid` like  '" . $_GET["ped"] . "' ");

        if ($request !== null && mysqli_num_rows($request) > 0) {
           $row = mysqli_fetch_assoc($request);
           $telefono = $row["telefono"];
           Connection::runQuery("UPDATE `contactos` SET `anterior`= '', menu=0, esperaRespuesta=0 where telefono like '" . $telefono . "' ");
        }  
    }
     
    function saveLog($json,$nombre ){
         //$nombre =date("dmY_His");  
        $fp = fopen($nombre.".csv","w+b");
        if( $fp == false ){

        }else{
            fwrite($fp,$json);
            fclose($fp);
        }
    }

   ?>
    <script>
         function sendMsjFinished() {
            var empresa = "<?php echo DB_NAME; ?>";
            var telefono = "<?php echo $telefono; ?>";
            var json = {
               type: "_msg_externo",
               empresa: empresa,
               cmd: "chat",
               msg: {
                  to: telefono,
                  custom_uid: telefono + String(Math.random() * 999),
                  body: {
                     text: "✓ Gracias por su compra 🙂 | Operacion: "+ "<?php echo $_GET["collection_id"]; ?>",
                  }
               }
            };
         }

         function openWSConnection(hostname, port, endpoint, mensaje) {

             var webSocketURL = hostname + endpoint;

            console.log("openWSConnection::Connecting to: " + webSocketURL);

            try {
               webSocket = new WebSocket(webSocketURL);
               webSocket.onopen = function(openEvent) {
                  console.log("WebSocket OPEN: " + mensaje);
                  webSocket.send(mensaje);
                  
                  webSocket.close();
                 // location.href ="/alcosa/ajax/bajarPedidosFtp.php";
               };
               webSocket.onclose = function(closeEvent) {
                  console.log("WebSocket CLOSE: " + JSON.stringify(closeEvent, null, 4));
               };
               webSocket.onerror = function(errorEvent) {
                  console.log("WebSocket ERROR: " + JSON.stringify(errorEvent, null, 4));
               };
               webSocket.onmessage = function(messageEvent) {
                  var wsMsg = messageEvent.data;

               };
            } catch (exception) {
               console.error(exception);
            }
         }
    </script>
    <?php
        $telefono = null;
        $request = null;
        $empresa = explode("/", $_SERVER["REQUEST_URI"])[1];
        if ($_GET["resu"] == "success") {
            echo '<script type="text/javascript">',
            'sendMsjFinished();',
            '</script>';
        }  

    ?>

  <div class="alert alert-<?php echo $_GET["resu"] ?>">
   <img src="whatsapp.png">  <strong>Listo!</strong>  El Pago fue realizado con Éxito!
  </div>

  <center>
 <a href="https://wa.me/<?php echo $telefono; ?>" target="_blank">
 <button type="button" class="btn btn-default"/><h4><b><i style="color:green" class="fa fa-whatsapp"></i>&nbsp; Volver a Whatsapp &nbsp;</b></h4></button> </a>
</center>
 
</div>

</body>
</html>
