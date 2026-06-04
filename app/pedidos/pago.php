<!DOCTYPE html>
<html lang="en">
<title>Listo</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>


</head>
<body>

<div class="container">
    <?php
    include_once("../config/Connection.php");

//    $empresa = explode("/", $_SERVER["REQUEST_URI"])[1];
//
//    $request = Connection::runQueryLogin("SELECT telefono FROM `empresa` WHERE `empresa` like '" . $empresa . "' ");
//    if (mysqli_num_rows($request) > 0) {
//        $row = mysqli_fetch_assoc($request);
//        $telefono = $row["telefono"];
//    }


    $pagado = 0;
    $request = Connection::runQuery("SELECT `pagado` FROM `pedidos` WHERE `pedidoid` like  '" . $_GET["ped"] . "' ");

    if (mysqli_num_rows($request) > 0) {
        $row = mysqli_fetch_assoc($request);
        $pagado = $row["pagado"];
    }


    if ($pagado == 0) {


        require __DIR__ . '/vendor/autoload.php';

        // Agrega credenciales
        MercadoPago\SDK::setAccessToken('APP_USR-7770582135185709-011714-7cfe76df1027d6910a0f1006621417a8-57940166');
        //MercadoPago\SDK::setAccessToken('TEST-1778597074880887-011714-d86e076d7327d77d51e076407c22b337-702823555');
        // Crea un objeto de preferencia
        $preference = new MercadoPago\Preference();

        $preference->back_urls = array(
            "success" => "http://atiende.lat/atiende/pedidos/fin.php?ped=" . $_GET["ped"] . "&resu=success",
            "failure" => "http://atiende.lat/atiende/pedidos/index.php?ped=" . $_GET["ped"] . "&resu=failure",
            "pending" => "http://atiende.lat/atiende/pedidos/index.php?ped=" . $_GET["ped"] . "&resu=pending"
        );

        $preference->payment_methods = array(

            "excluded_payment_types" => array(
                array("id" => "ticket")
            )
        );
        $preference->auto_return = "approved";

        $shipments = new MercadoPago\Shipments();
        $shipments->cost = 0;
        $shipments->mode = "not_specified";
        $preference->shipments = $shipments;


        // Crea un ítem en la preferencia
        $item = new MercadoPago\Item();
        $item->title = 'Mi Pedido ';
        $item->quantity = 1;
        $item->unit_price = $_GET["monto"];
        $preference->items = array($item);
        $preference->save();

        header("Location: " . $preference->init_point);
    } else {
        ?>
        <div class="alert alert-success">
            <img src="whatsapp.png"> <strong>Gracias</strong> El Pedido ya está pagado!
        </div>

        <center>
            <a href="https://wa.me/<?php echo $telefono; ?>" target="_blank">
                <button type="button" class="btn btn-default"/>
                <h4><b><i style="color:green" class="fa fa-whatsapp"></i>&nbsp; Volver a Whatsapp &nbsp;</b>
                </h4></button> </a>
        </center>


        <?php
    }
    ?>


</body>
</html>
