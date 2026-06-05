<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
}else{
    
    require 'headerv1.php';
    define('__ROOT__', dirname(dirname(__FILE__)));
    require(__ROOT__ . '/config/global.php'); 
    if ($_SESSION['ventas'] == 1) {
        ?>
        <style>
#tbllistado thead th, table.dataTable thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
</style>
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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Ventas</a></li>
                            <li class="breadcrumb-item active"> Pedidos </li>
                        </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-success rounded-pill pull-right sombra-logo"  id="btnExportar" onClick="aExcel()">
                        <i class="mdi mdi-file-excel-outline me-1"></i> Exportar
                    </button>
                    <button class="btn btn-light rounded-pill sombra-logo" id="btnCancelar" onclick="cancelarform()" type="button">
                        <i class="mdi mdi-arrow-left-circle me-1"></i> Volver
                    </button> 
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel" style="border-top:3px solid #727cf5;">
                    <div class="card-body p-0">
                            <div class="table-responsive" id="listadoregistros">

<!--                                table dt-responsive nowrap w-100 dataTable no-footer dtr-inline-->
                                <table id="tbllistado"  class="table nowrap w-100 dataTable no-footer dtr-inline">
                                    <thead>
                                    <!--width="16%" -->
                                    <th style="min-width: 120px;">Acciones</th>
                                    <th>N°Ped</th>
                                    <th style="min-width: 120px;">Fecha</th>
                                    <th>Id Cli.</th>
                                    <th style="min-width: 120px;">Cliente</th>
                                    <th style="min-width: 120px;">Telefono</th>
                                    <th>Total</th>
                                    <th>Pagado</th>
                                    <th>F. pago</th>
                                    <th>Ob</th>
                                    <th>Estado</th>
                                    <th>Origen</th>
                                    <th style="min-width: 120px;">Vendedor</th>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                                <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Datos del Pedido</span></h6>
                                <form action="" name="formulario" id="formulario" method="POST">
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
                                                    <th align="right">Imagen</th>
                                                </thead>
                                                <tfoot>
                                                    <th colspan="7" align="right">TOTAL</th>
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
                    <div class="card-body px-3 pb-3" id="formulariormsj">
                                <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Envio de Mensajes</span></h6>
                                <div class="">
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
                                    <table id="tbmensajes"class="table dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
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

        <!-- modal envio de mensaje-->
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
        <!-- inicio modal observacion -->
        <div   id="myModal"  class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm ">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-body p-1">
                        <div class="text-center">                    
                            <h4 class="mt-2"><i class="mdi mdi-comment-processing h3 text-info"></i> Observaci&oacute;n</h4>
                            <p class="mt-3"><span id ="obs"></span></p>
                            <button type="button" class="btn btn-info my-2" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
        <!-- fin modal observacion -->
        <?php
    }else {
        //require 'noacceso.php';
    }
    require 'footerv1.php';?>
    <script>
        document.title = "Atiende | Pedidos";
        
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

    <script src="scripts/venta.js?t=<?php echo time(); ?>"></script>
    <?php
    if (isset($_GET["pedidoid"])) {
        ?>
        <script> mostrarform(true);
            mostrar('<?php echo $_GET["pedidoid"]; ?>');
        </script>
        <?php
    }
} 
ob_end_flush();
?>

