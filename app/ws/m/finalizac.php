<!DOCTYPE html>
<html lang="en">
<head>
    <title>Atiende | Listo</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="../../public/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/css/atiende.css">
    <script src="../../public/js/jquery.min.js"></script>
    <script src="../../public/bootstrap/dist/js/bootstrap.min.js"></script>
    <link href="../../public/sweetAlert2/sweetalert2.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    <script src="../../public/sweetAlert2/sweetalert2.all.min.js"></script>
</head>
<body >
<?php
    error_reporting(E_ALL ^ E_WARNING);

//    include_once("../../config/Connection.php");
//    $empresa = explode("/", $_SERVER["REQUEST_URI"])[1];
//    $request = Connection::runQueryLogin("SELECT telefono FROM `empresa` WHERE `empresa` like '".$empresa."' ");
//    if( mysqli_num_rows ($request )>0){
//          $row = mysqli_fetch_assoc($request);
//          $telefono = $row["telefono"];
//      }

    $responseWebMaster = getWebMasterConfig();
    $telefono = $responseWebMaster["empresa"]["telefono"];
   ?>

    <div class="container">
        <div class="alert alert-success sombra">
            <img src="whatsapp.png">  <strong>Listo!</strong>  La respuesta de la consulta fue enviado
        </div>

        <center>
            <a href="https://wa.me/<?php echo $telefono; ?>" target="_blank" class="btn btn-success btn-lg sombra button-whatsapp"  style="margin-top:5%">          
                <i class="glyphicon glyphicon-whatsapp" aria-hidden="true"></i> 
                        &nbsp;Volver a Whatsapp &nbsp;        
            </a>
        </center>
    </div>
</body>
<script src="../../public/js/jquery.min.js"></script>
<script src="../../public/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="../../public/sweetAlert2/sweetalert2.all.min.js"></script>
</html>
