<?php
if (strlen(session_id()) < 1)
    session_start();
define('__ROOT__', dirname(dirname(__DIR__)));
require (__ROOT__.'/config/global.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Atiende | Consulta | Supervisor</title>
    <meta charset="utf-8">
    <!-- URL linda /responder/consulta/{id}: resolver TODAS las rutas relativas (assets, C_Respuesta.php,
         finaliza.php, loading.gif) como si el documento estuviera en /ws/m/ — si no, se resuelven contra
         /responder/consulta/ y dan 404 (mismo patrón que el <base> de /pedidos). -->
    <base href="/ws/m/">
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

<script type="text/javascript">
    const globalNombreEmpresa = '<?php echo DB_NAME; ?>';
    const globalUrl = '<?php echo tenantUrl(); ?>';
</script>
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
            alert('Ingrese su mensaje');
            //$("#form_01")[0].reportValidity();
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
        var _estado="En analisis";
        if(document.getElementById("estado2").checked )
            _estado="Finalizado";
        var json = {
            consultaId: document.getElementById('consultaId').value,
            resolucion : document.getElementById('resolucion').value,
            estado: _estado,
        };
        $.ajax({
            type: "POST",
            url: 'C_Respuesta.php',
            data: "json="+encodeURIComponent(JSON.stringify(json))+"&t="+encodeURIComponent(document.getElementById('tokenT').value),
            success: function(data){
                if(parseInt(data)>0){
                    //  alert (data);
                    var _estado="En analisis";
                    if(document.getElementById("estado2").checked )
                        _estado="Finalizado";

                    var mensaje=mostrarSaludo()+" *"+document.getElementById('nick').value+"* , tenemos  novedades de su consulta:\n";
                    mensaje+="*Consulta N°:* "+PadLeft(document.getElementById('consultaId').value,10)+"\n"+
                        "*Motivo:* "+document.getElementById('motivo').value+"\n"+
                        "*Fecha:* "+ fechaHora()+"\n"+
                        "*Estado:* "+_estado+"\n"+
                        "*Resolucion:* "+document.getElementById('resolucion').value+"\n";


                    var json = {
                        type: "_msg_externo",
                        empresa: document.getElementById('empresa').value,
                        cmd:"chat",
                        msg: {
                            to :document.getElementById('telefono').value,
                            custom_uid: document.getElementById('telefono').value,
                            body:{
                                text: mensaje
                            }
                        }
                    };
                    // El envío al cliente ya lo hizo C_Respuesta.php por la API directa de WhatsApp
                    // (antes salía por WebSocket/WEB_MASTER legacy, que no entrega en tenants migrados).
                    document.getElementById('bloquea').style.display='none';
                    document.location.href="/responder/listo";

                }else{
                    console.log(data);
                    document.getElementById('bloquea').style.display='none';
                    alert("Ocurrió un error inesperado");
                }
            }
        });
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

    function openWSConnection(hostname, port, endpoint, mensaje) {
        var webSocketURL = hostname + endpoint;

        console.log("openWSConnection::Connecting to: " + webSocketURL);
        try {
            webSocket = new WebSocket(webSocketURL);
            webSocket.onopen = function(openEvent) {

                // console.log("WebSocket OPEN: " + JSON.stringify(openEvent, null, 4));
                //var obj = JSON.parse('{ "name":"John", "age":30, "city":"New York"}');
                webSocket.send(mensaje);
                webSocket.close();
            };
            webSocket.onclose = function (closeEvent) {
                console.log("WebSocket CLOSE: " + JSON.stringify(closeEvent, null, 4));
                document.getElementById('bloquea').style. display='none';
                document.location.href="finaliza.php"

            };
            webSocket.onerror = function (errorEvent) {
                console.log("WebSocket ERROR: " + JSON.stringify(errorEvent, null, 4));
            };
            webSocket.onmessage = function (messageEvent) {
                var wsMsg = messageEvent.data;
                console.log("WebSocket MESSAGE: " + wsMsg);

            };
        } catch (exception) {
            console.error(exception);
        }
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
        require_once("../../config/tenant_subdominio.php");
        resolverTenantPorSubdominio(); // resolver el tenant por subdominio (link sin login)
        require "../../modelos/Consulta.php";
        //$request=Connection::runQuery("SELECT consultas.*,clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `consultas` LEFT JOIN clientes ON consultas.clienteId = clientes.codigo WHERE consultaId like '".$_GET["id"]."' and estado <> 'Finalizado' ");
        $consulta = new Consulta();
        $idGet = intval($_GET["id"] ?? 0);
        $tokGet = isset($_GET["t"]) ? $_GET["t"] : '';
        $tokEsperado = substr(hash_hmac('sha256', 'consulta:' . $idGet, (defined('PLATFORM_ENCRYPTION_KEY') ? PLATFORM_ENCRYPTION_KEY : '')), 0, 32);
        if (!hash_equals($tokEsperado, $tokGet)) {
            echo "<br><div class='alert alert-danger sombra'><strong>Enlace inválido o vencido</strong></div>";
            echo "</div></body></html>";
            exit;
        }
        $request = $consulta->listarRespCons($idGet);
        $resolucion="";
        if( mysqli_num_rows ($request )>0){
            $row = mysqli_fetch_assoc($request);

            if(strlen ($row["resolucion"])>2);
            $resolucion=$row["resolucion"];
            ?>
            <h4 class="ps-2 pt-2">Responder Consulta</h4>
            <ul class="nav nav-tabs nav-bordered mb-3">
                <li class="nav-item">
                    <a href="#tab-chat" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                        <span ><i class="uil uil-comments "></i> Mensajes</span>
                       
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-info" data-bs-toggle="tab" aria-expanded="false" class="nav-link ">
                        <span><i class="mdi mdi-clipboard-text-multiple"></i> Info de la Consulta</span>
                       
                    </a>
                </li>    
            </ul>
            <div class="card border sombra" style="border-width: 0.25rem !important;border-radius: 15px;">
            <div class="tab-content">
                <?php 
                $respConsulta = $consulta->listarChats($idGet);
                ?>
                <div class="tab-pane show active" id="tab-chat">
                    <?php $tituloMotivo = trim((string)($row["motivo"] ?? '')); if ($tituloMotivo !== '') { ?>
                    <div class="px-3 py-2 border-bottom">
                        <h5 class="mb-0 text-truncate" title="<?php echo htmlspecialchars($tituloMotivo, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="mdi mdi-message-alert-outline text-primary me-1"></i><?php echo htmlspecialchars($tituloMotivo, ENT_QUOTES, 'UTF-8'); ?>
                        </h5>
                    </div>
                    <?php } ?>
                    <div class="card-body px-0 pb-0 chat-body chat-supervisor " >
                        <ul class="conversation-list px-3"  style="max-height: 538px">
                            <?php
                            // Mensaje original de la consulta: el cliente lo escribió por WhatsApp y se
                            // guarda en `consultas.detalle` (NO en msj_consultas), por eso el chat arrancaba
                            // vacío. Lo mostramos como primer globo (lado Cliente). El motivo va de titular
                            // del chat (arriba), así el globo queda solo con el detalle. Es solo display.
                            $detalleOriginal = trim((string)($row["detalle"] ?? ''));
                            $horaOriginal    = '';
                            if (!empty($row["fecha_ingreso"])) {
                                $tsOriginal = strtotime($row["fecha_ingreso"]);
                                if ($tsOriginal) { $horaOriginal = date('H:i', $tsOriginal); }
                            }
                            if ($detalleOriginal !== '') {
                            ?>
                                <li class="clearfix">
                                    <div class="chat-avatar">
                                        <img src="../../public/img/avatars/user1.png" class="rounded" alt="Cliente" />
                                        <i><?php echo htmlspecialchars($horaOriginal, ENT_QUOTES, 'UTF-8'); ?></i>
                                    </div>
                                    <div class="conversation-text">
                                        <div class="ctext-wrap">
                                            <i>Cliente</i>
                                            <p><?php echo nl2br(htmlspecialchars($detalleOriginal, ENT_QUOTES, 'UTF-8')); ?></p>
                                        </div>
                                    </div>
                                </li>
                            <?php } ?>
                            <?php $etiquetaFecha ='';
                            while ($reg = $respConsulta->fetch_object()) { ?>
                                <?php $fecha = $reg->fechaMensaje;
                                if($etiquetaFecha!== $fecha){ ?>
                                    <li class="text-center mt-0 mb-0">
                                        <h4><?php $etiquetaFecha = $fecha; ?>                                        
                                            <span class="badge  badge-secondary-lighten rounded-pill"><?=$etiquetaFecha?></span>                                        
                                        </h4>
                                    </li>
                                <?php } ?>
                                <li class="clearfix <?php echo $reg->canal==-1?'':'odd'?>">
                                    <div class="chat-avatar">                                    
                                        <img src="../../public/img/avatars/<?php echo $reg->canal!=-1?'user2.png':'user1.png'?>" class="rounded" alt="Shreyu N" />
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
                    </div>
                    <div class="card-footer text-muted " style="background: #a19e9e14;">
                        <form class="needs-validation mt-2" novalidate="" name="chat-form" id="form_01">
                            <input id="nick" type="hidden" value="<?php echo htmlspecialchars($row["nick"], ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="tokenT" type="hidden" value="<?php echo htmlspecialchars($_GET['t'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="consultaId" type="hidden" value="<?php echo htmlspecialchars($row["consultaId"], ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="motivo" type="hidden" value="<?php echo htmlspecialchars($row["motivo"], ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="fecha_hora" type="hidden" value="<?php echo htmlspecialchars($row["fecha_hora"], ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="telefono" type="hidden" value="<?php echo htmlspecialchars($row["telefono"], ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="empresa" type="hidden" value="<?php echo htmlspecialchars($row["empresa"], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="row">
                                <div class="col-sm-auto mt-n2">Finalizar?<br>
                                    <input type="checkbox" id="estado2"  data-switch="bool"/>
                                    <label for="estado2" data-off-label="No" data-on-label="Si"></label>
                                </div>
                                <div class="col mb-2 mt-n1 mb-sm-0">  
                                    <div contenteditable class="fake-textarea text-break form-control col" id="divMsg" oninput="document.querySelector('#resolucion').textContent = this.innerText"></div>                             
                                    <textarea class="form-control mensajeChat" name="resolucion" id="resolucion" required="" placeholder="Ingrese su mensaje"></textarea>                                
                                </div>
                                <div class="col-sm-auto">
                                    <button  type="button" class="btn btn-sm btn-success chat-send sombra" onclick="enviarFormulario();"><i class='uil uil-message'></i> </button>
                                </div>
                            </div>
                        </form>
                        <!-- <form  id="form_01"> 
                            <div class="row">                            
                                <div class="col mb-2 mt-n1 mb-sm-0">  
                                    <div contenteditable class="fake-textarea text-break form-control col" id="divMsg" oninput="document.querySelector('#resolucion').textContent = this.innerText"></div>                             
                                    <textarea class="form-control mensajeChat"  id="resolucion" required="" placeholder="Ingrese su mensaje"></textarea>                                
                                </div>
                                <div class="col-sm-auto">                            
                                    <button  type="button" class="btn btn-sm btn-success chat-send sombra" onclick="enviarFormulario();"><i class='uil uil-message'></i> </button>                            
                                </div>
                            </div>
                        </form> -->
                    </div>
                </div>
                <div class="tab-pane " id="tab-info" style="padding: 1.5em;">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="font-15"><i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Codigo Cliente</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["clienteId"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"><i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Razon Social Cliente</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["razonSocial"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"><i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Direccion Cliente</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["direccion"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"><i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Tel. Cliente</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["telefono"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"> <i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Cod. Consulta</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["consultaId"], ENT_QUOTES, 'UTF-8');?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"> <i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Motivo</h6>
                            <p class="text-sm lh-150 text-break"> <?php echo htmlspecialchars($row["motivo"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"> <i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Fecha Consulta</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["fecha_ingreso"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="font-15"> <i class="mdi mdi-spin mdi-rhombus-split text-info"></i> Detalle del Motivo</h6>
                            <p class="text-sm lh-150 text-break"><?php echo htmlspecialchars($row["detalle"], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        
                    </div>
                </div> 
            </div>   
        </div>
            <?php
        }else{
            echo "<div class='alert alert-danger sombra'>
                    <strong>
                        <span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span>&nbsp;Error!
                    </strong> 
                    La consulta ya fue resuelta o no existe 
                </div>";
        }
        ?>
    </div>
        <div id="bloquea" class="cargando" style="display:none;">
            <img style="margin-left: 5%;margin-top: 15%" alt="Espere..." src="loading.gif" />
            <div align="center">
                <h3>Espere un momento...</h3>
            </div>
        </div>
    </body>
</html>