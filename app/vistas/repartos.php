<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
}else {
    require 'headerv1.php';
    define('__ROOT__', dirname(dirname(__FILE__)));
    require(__ROOT__ . '/config/global.php');
    if ($_SESSION['repartos'] == 1) {?>
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item" style="margin-top: -0.7em">
                                <a href="javascript: void(0);">                            
                                    <img src="../public/img/logo30x30.png" alt="" class="icono-ruta-ClubPedido" >            
                                    Atiende
                                </a>
                            </li>
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Repartos</a></li>
                            <li class="breadcrumb-item active"> Asignacion de Pedidos </li>
                        </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-light rounded-pill sombra-logo" id="btnCancelar" onclick="cancelarform()" type="button">
                        <i class="mdi mdi-arrow-left-circle me-1"></i> Volver
                    </button> 
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel  ribbon-box">
                    <div class="card-body mt-n2">
                        <div class="box-body">
                            <div class="row">
                                <div id="asignarRepartidor" class="col-12 d-flex flex-wrap align-items-center gap-2">
                                    <label class="form-label mb-0 text-muted fw-bold" style="font-size:.72rem; letter-spacing:.3px; white-space:nowrap;">
                                        <i class="uil uil-truck me-1"></i> Asignar pedidos tildados (col. <b>#</b>) a:
                                    </label>
                                    <select class="form-select form-select-sm" name="repartidor" id="repartidor" style="max-width:240px;" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Elegí el repartidor que entregará los pedidos seleccionados"> </select>
                                    <button class="btn btn-primary btn-sm" id="btnAsignar" onclick="asignar()" type="button"
                                            data-bs-toggle="tooltip" data-bs-trigger="hover" title="Asignar los pedidos tildados al repartidor elegido">
                                        <i class="uil-check-circle"></i> Asignar
                                    </button>
                                    <span id="filtroRepartoSlot" class="d-flex align-items-center gap-2 ms-auto"></span>
                                </div>
                            </div>
                            <div class=" table-responsive mt-n2" id="listadoregistros">
                                <table id="tbllistadoRepartos"  class="table table-striped table-centered mb-0  nowrap w-100">
                                    <thead >
                                        <th style="width:40px; text-align:center;">#</th>
                                        <th  style="min-width: 100px;">Acciones</th>
                                        <th >N°Ped</th>
                                        <th style="min-width: 120px;">Fecha</th>
                                        <th >Id Cli.</th>
                                        <th style="min-width: 120px;">Cliente</th>
                                        <th >Telefono</th>
                                        <th >Total</th>
                                        <th style="min-width: 120px;">Asignación</th>
                                        <th style="min-width: 120px;">Notificación</th>
                                        <th >Estado</th>
                                        <th  style="min-width: 120px;">Repartidor</th>
                                        <th >Asig.</th>
                                        <th >Enviado</th>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <div id="formularioregistros">
                                <div class="ribbon ribbon-info float-start mb-1">
                                    <i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Datos del Pedido</span>
                                </div>
                                <form action="" name="formulario" id="formulario" method="POST" class="ribbon-content">
                                    <div class="row">                                        
                                        <div class="col-lg-8">
                                            <div class="mb-2 position-relative">
                                                <input class="form-control" type="hidden" name="idventa" id="idventa">
                                                <input class="form-control" type="hidden" name="clienteid" id="clienteid">
                                                <input class="form-control" type="hidden" name="tipo" id="tipo" value="1">
                                                <label for="" class="form-label">Cliente</label>
                                                <input class="form-control" type="text" name="idcliente" id="idcliente" placeholder="Cliente" disabled="disabled">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Fecha</label>
                                                <input class="form-control" type="text" name="fecha_hora" id="fecha_hora" disabled="disabled" required>
                                            </div>                             
                                        </div>
                                    </div>
                                    <div class="row">                                        
                                        <div class="col-lg-6">
                                            <div class="mb-1 position-relative">
                                                <label for="" class="form-label">Domicilio</label>
                                                <input class="form-control" type="text" name="domicilio" id="domicilio" placeholder="domicilio " disabled="disabled">
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="mb-1 position-relative">
                                                <label for="" class="form-label">N&uacute;mero</label>
                                                <input class="form-control" type="text" name="num_comprobante" id="num_comprobante" maxlength="10" placeholder="Número" disabled="disabled" required>
                                            </div>                                            
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="mb-1 position-relative">
                                                <label for="" class="form-label">Total</label>
                                                <input class="form-control" type="text" name="total_input" id="total_input" disabled="disabled">
                                            </div>                                            
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="mb-1 position-relative">
                                                <label for="" class="form-label">Tel </label>
                                                <input class="form-control" type="text" name="telefono" id="telefono" disabled="disabled">
                                            </div>                                            
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span id="cambiarEstado"> </span>
                                            <span id="imprimir"> </span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 mt-3">
                                            <table id="detalles" class="table table-sm table-striped table-centered mb-0  nowrap w-100">
                                                <thead style="background-color:#8b74d2c7;color:white">
                                                    <th>Codigo</th>
                                                    <th>Articulo</th>
                                                    <th align="center">Cantidad</th>
                                                    <th align="right">Precio Venta</th>
                                                    <th align="center">Descuento</th>
                                                    <th align="right">Subtotal</th>
                                                </thead>
                                                <tfoot>
                                                    <th colspan="5" align="right">TOTAL</th>
                                                    <th>
                                                        <h4 id="total">$ 0.00</h4>
                                                        <input type="hidden" name="total_venta" id="total_venta">
                                                    </th>
                                                </tfoot>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div id="formulariormsj" class="mt-1">
                                <div class="ribbon ribbon-info float-start mb-1">
                                    <i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Envio de Mensajes</span>
                                </div>
                                <div class="ribbon-content">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Mensaje</label>
                                                <input class="form-control" type="text" name="mensajeD" id="mensajeD">
                                            </div> 
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="float-start ">
                                                <div class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input" id="finalizarCheck" checked>
                                                    <label class="form-check-label" for="finalizarCheck">Finalizar</label><!--
                                                    <label><input type="checkbox" value="" id="finalizarCheck" checked>Finalizar</label>-->
                                                </div>
                                            </div>
                                            <div class="text-sm-end">
                                                <a class="btn btn-primary btn-sm" onClick="enviarMensaje('<?php echo explode("/", $_SERVER["REQUEST_URI"])[1] ?>')"
                                                id="btnEnviar">
                                                    <i class="mdi mdi-rotate-315 mdi-send"></i> Enviar mensaje
                                                </a>
                                                <a class="btn btn-info btn-sm" onClick="enviarLink('<?php echo explode("/", $_SERVER["REQUEST_URI"])[1] ?>')"
                                                id="btnEnviar">
                                                    <i class="uil uil-link"></i> Enviar link pago
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <table id="tbmensajes"class="table table-sm table-striped table-centered mb-0  nowrap w-100 mt-2">
                                        <thead style="background-color:#8b74d2c7;color:white">
                                            <th>ID</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th>Mensaje</th>
                                            <th>estado</th>
                                        </thead>

                                        </tbody>
                                    </table>
                                    <div class="row">
                                        <div class="col-lg-12 text-sm-end">
                                            <button class="btn btn-success rounded-pill sombra-logo" type="submit" id="btnGuardar">
                                                <i class="mdi mdi-content-save-all"></i> Guardar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal -->
        <div id="sendMessage" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="primary-header-modalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header modal-colored-header bg-success">
                        <h4 class="modal-title" id="primary-header-modalLabel">Enviar mensaje personalizado</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="telefono" value="">
                        <input type="hidden" id="razonSocial" value="">
                        <textarea class="form-control" id="mensaje" name="mensaje" rows="4"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" onclick="enviarMsjACliente()"><span class="uil uil-envelope"></span> Enviar</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><span class="uil uil-times"></span></spna> Volver</button>                        
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        <!-- fin modal -->
    <?php }else {
        require 'noacceso.php';
    }
    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Asignación de pedidos";       

        function comentario(comentario) {
            $('#myModal').modal('show');
            $('#obs').html(comentario);
        }

        function sendMessageCustomizer(telefono, razonSocial) {
            $('#sendMessage').modal('show');
            $(".modal-body #telefono").val(telefono);
            $(".modal-body #razonSocial").val(razonSocial);
            $(".modal-body #mensaje").val('Hola ' + razonSocial + ' te informamos que ya estamos gestionando tu pedido y pronto te lo enviaremos');
        }
    </script>

    <script src="scripts/reparto.js?t=<?php echo time(); ?>"></script>
    <?php
    if (isset($_GET["pedidoid"])) {
        ?>
        <script> mostrarform(true);
            mostrar('<?php echo intval($_GET["pedidoid"] ?? 0); ?>');
        </script>
        <?php
    }
}
ob_end_flush();
?>

