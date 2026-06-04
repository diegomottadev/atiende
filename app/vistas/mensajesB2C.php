<?php

ob_start();
session_start();

if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require_once "../config/global.php";
    require 'header.php';
    include_once("config/Connection.php");
    if ($_SESSION['mensajes'] == 1) {
?>
    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h1 class="box-title">Mensajes &nbsp;&nbsp;&nbsp;&nbsp;</h1>
                            <div class="box-tools pull-right">
                                <button class="btn btn-success pull-right" onclick="nuevo()" id="btnagregar"><span class="fa fa-plus-circle"></span> Nuevo</button>
                                <button type="button" id="exportarContactos" class="btn btn-primary"><span class="fa fa-arrow-circle-down"></span> Exportar</button>

                                <button type="button" id="btnCancelarForm" onclick="cancelarform()" class="btn btn-default"><span class="fa fa-reply"></span> Volver</button>

                            </div>
                            <br>
                        </div>
                        <div class="panel-body table-responsive" id="listadoregistros">
                            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
                                <thead>
                                <th>#Id</th>
                                <th>Titulo</th>
                                <th>Mensaje</th>
                                <th>Clientes</th>
                                <th>Fecha/Hora</th>
<!--                                <th>Estado</th>-->
                                <th>Acciones</th>

                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="panel-body" style="height: 100%;display: none" id="formularioMensajes">
                            <form  method="post" enctype="multipart/form-data" id="formMensajeByExcel" name="formMensajeByExcel">
                                <div class="form-group">
                                    <input type="hidden" name="eliminar" id="eliminar">
                                    <input type="hidden" name="editar" id="editar">
                                    <input type="hidden" name="_ramos" id="_ramos">
                                    <input type="hidden" name="_tienedatos" id="_tienedatos">
                                    <label for="_id">Código</label>
                                    <input class="form-control" id="_id" type="text" name="_id" disabled="disabled">
                                </div>

                                <div class="form-group">
                                    <label for="_titulo">Título</label>
                                    <input class="form-control" id="_titulo" type="text" name="_titulo" required>
                                </div>

                                <div class="form-group">
                                    <label for="_mensaje">Mensaje</label>
                                    <textarea class="form-control" rows="3" id="_mensaje" name="_mensaje" required></textarea>
                                </div>

                                <div  class="form-group">
                                        <label class="custom-file-label" for="mensajes">Elegir archivo</label>
                                        <input type="file" class="form-control" name="contactos" id="contactos">
                                </div>
                                <div  class="form-group">
                                    <button type="submit" id="btnAgregarForm" class="btn btn-success pull-right"><span class="fa fa-save"></span> Guardar</button>
                                </div

                            </form>
                        </div>

                    </div>
                    <div class="box">
                        <div class="box-header with-border">
                            <h1 class="box-title">Contactos &nbsp;&nbsp;&nbsp;&nbsp;</h1>
                            <table id="tbclientesByExcel" class="table table-striped table-bordered table-condensed table-hover">
                                <thead bgcolor="#D3DCE3">
                                <th>Teléfono</th>
                                <th>Codigo</th>
                                <th>RazonSocial</th>
                                <th>Direccion</th>
                                <th>Localidad</th>
                                <th>Ramo</th>
                                <th>Zona</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </section>
    </div>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" align="center">Enviando mensajes</h5>
                </div>
                <div class="modal-body">
                    <h5 align="center"> Espere un momento, no cierre esta página</h5>
                    <p align="center" id="contador"></p>
                    <h1 align="center" >
                         <img class="img-container" alt="Espere..." src="loading.gif" />
                    </h1>
                </div>
            </div>
        </div>
    </div>
    <script>
        $("#exportarContactos").click(function () {
            exportar($('#_id').val())
        })
    </script>
    <?php
    } else {
        require 'noacceso.php';
    }
    require 'footer.php';
    ?>
    <script>
        document.title = "Atiende | Mensajes a prospectos";
    </script>
    <script src="scripts/ws.js?t=<?php echo time(); ?>"></script>
    <script src="scripts/mensajeb2c.js?t=<?php echo time(); ?>"></script>

    <?php
}
ob_end_flush();
?>

