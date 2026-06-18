<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } // antes de cualquier salida HTML, si no session_start() falla con "headers already sent" ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Atiende | Consulta</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link href="../../public/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="../../public/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    
    <script src="../../public/js/jquery.min.js"></script>
    <script src="../../public/assets/js/vendor.min.js"></script>
    <link href="../../public/sweetAlert2/sweetalert2.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    <script src="../../public/sweetAlert2/sweetalert2.all.min.js"></script>
    <link href="../../public/css/styleChat.css" rel="stylesheet" type="text/css" />
    
</head>

<?php $idc = intval($_GET["idconsulta"] ?? 0); ?>
<script>

    function NoBack(){
        history.go(1);
    }


    function enviarFormulario(){

        if($("#form_01")[0].checkValidity()) {
            Swal.fire({
                    title:'',
                    text: '¿Desea enviar el mensaje de respuesta?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#727cf5',
                    cancelButtonColor: '#fa5c7c',
                    cancelButtonText: 'Cancelar',
                    confirmButtonText: 'Aceptar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('bloquea').style. display='block';
                        enviarRespuesta();
                    }
                })                
        }
        else {
            //$("#form_01")[0].reportValidity();
            alert('Ingrese su mensaje'); 
        }
    }

    function PadLeft(value, length) {
        return (value.toString().length < length) ? PadLeft("0" + value, length) :
            value;
    }

    function mostrarSaludo(){
        var	ahora=new Date();
        var hora=ahora.getHours();
        var texto="";
        if(hora<12){
            texto="Buenos Días";

        }
        if(hora>12 && hora<18){
            texto="Buenas Tardes";

        }

        if(hora>18 && hora<24){
            texto="Buenas Noches";

        }

        return texto;

    }



    //-----------------------------------------------------------------------------------
    function enviarRespuesta(){

        $.ajax({
            type: "POST",
            url: '../../ajax/consulta.php?op=guardarMensaje',
            data: "idconsulta=<?php echo $idc; ?>&resolucion="+document.getElementById('resolucion').value+"&tipo=1&canal=-1" ,
            success: function(data){
                console.log(data);
                document.getElementById('bloquea').style. display='none';
                document.location.href="finalizac.php"
                /**------------------------- */
            }
        });


        //	 openWSConnection('54.175.225.1','8080','/wsbot/ws',JSON.stringify(json));



    }
    function fechaHora () {
        now = new Date();
        year = "" + now.getFullYear();
        month = "" + (now.getMonth() + 1); if (month.length == 1) { month = "0" + month; }
        day = "" + now.getDate(); if (day.length == 1) { day = "0" + day; }
        hour = "" + now.getHours(); if (hour.length == 1) { hour = "0" + hour; }
        minute = "" + now.getMinutes(); if (minute.length == 1) { minute = "0" + minute; }
        second = "" + now.getSeconds(); if (second.length == 1) { second = "0" + second; }
        return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;
    }

    $(document).ready(function () { 
        /* es para el scroll vaya al final de la conversacion */   
        $(".chat-body").animate({ scrollTop: $('.chat-body')[0].scrollHeight}, 1000); 
    });
</script>

<body OnLoad="NoBack();">
    <div class="container">
    <?php
    include_once("../../config/Connection.php");
    require_once("../../config/global.php");
    require_once("../../config/tenant_subdominio.php");
    resolverTenantPorSubdominio(); // resolver el tenant por subdominio (página sin login)
    $row = mysqli_fetch_array(Connection::runQuery("SELECT estado FROM `consultas` WHERE `consultaId` = ".$idc));
     if($row && $row["estado"]!="Finalizado"){
            require "../../modelos/Consulta.php";
            $consulta = new Consulta();
            $request = $consulta->listarChats($idc);
        ?>
        <h4 class="ps-2 pt-2">Responder Consulta</h4>
        <div class="card border sombra" style="border-width: 0.25rem !important;border-radius: 15px;">
            <!-- data-simplebar data-simplebar-primary data-simplebar-lg  -->
                <div class="card-body px-0 pb-0 chat-body"   >
                    <ul class="conversation-list px-3"  style="max-height: 538px">
                        <?php $etiquetaFecha ='';
                        while ($reg = $request->fetch_object()) { ?>
                            <?php $fecha = $reg->fechaMensaje;
                            if($etiquetaFecha!== $fecha){ ?>
                                <li class="text-center mt-0 mb-0">
                                    <h4><?php $etiquetaFecha = $fecha; ?>                                        
                                        <span class="badge  badge-secondary-lighten rounded-pill"><?=$etiquetaFecha?></span>                                        
                                    </h4>
                                </li>
                            <?php } ?>
                            <li class="clearfix <?php echo $reg->canal==-1?'odd':''?>">
                                <div class="chat-avatar">                                    
                                    <img src="../../public/img/avatars/<?php echo $reg->canal==-1?'user2.png':'user1.png'?>" class="rounded" alt="Shreyu N" />
                                    <i><?php echo $reg->hora?></i>
                                </div>
                                <div class="conversation-text">
                                    <div class="ctext-wrap">
                                        <i><?php echo htmlspecialchars($reg->usuario, ENT_QUOTES, 'UTF-8')?></i>
                                        <p> <?php echo htmlspecialchars($reg->mensaje, ENT_QUOTES, 'UTF-8')?></p>
                                    </div>
                                </div>   
                            </li>
                         <?php
                        }
                        ?>
                        <li class="pb-2"></li>                        
                    </ul>                   
                    <!-- end row -->
                </div> <!-- end card-body --> <!-- mt-n2-->
                <div class="card-footer text-muted " style="background: #a19e9e14;">
                    <form  id="form_01"> <!--class="needs-validation mt-2" novalidate="" name="chat-form"-->
                        <div class="row">                            
                            <div class="col mb-2 mt-n1 mb-sm-0">  
                                <div contenteditable class="fake-textarea text-break form-control col" id="divMsg" oninput="document.querySelector('#resolucion').textContent = this.innerText"></div>                             
                                <textarea class="form-control mensajeChat" name="resolucion" id="resolucion" required="required" placeholder="Ingrese su mensaje"></textarea>                                
                            </div>
                            <div class="col-sm-auto">                            
                                <button  type="button" class="btn btn-sm btn-success chat-send sombra float-end" onclick="enviarFormulario();"><i class='uil uil-message'></i> </button>                            
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <div id="bloquea" class="cargando" style="display:none;">
            <img style="margin-left: 5%;margin-top: 15%" alt="Espere..." src="loading.gif" />
            <div align="center">
                <h3>Espere un momento...</h3>
            </div>
        </div>
        <?php
    }else{

        echo " <br> <div class='alert alert-danger sombra'>
              <strong>
              <span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span>&nbsp;Alerta!
              </strong> 
              La consulta ya fue finalizada.
            </div>";
        }

    ?>

    </div>
</body>
</html>