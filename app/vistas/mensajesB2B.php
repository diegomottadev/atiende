<?php
//activamos almacenamiento en el buffer
//http://www.atiende.lat/hardsell/reportes/exTicket.php?id=1624
//http://www.atiende.lat/hardsell/reportes/exTicket.php?id=1624
//http://www.atiende.lat/hardsell/reportes/exTicket.php?id=1624
//http://www.atiende.lat/hardsell/reportes/exTicket.php?id=1624

ob_start();
session_start();

if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require_once "../config/global.php";
    require 'header.php';
    include_once("config/Connection.php");
    if ($_SESSION['mensajes'] == 1) {
        if (isset($_POST['_mensaje'])) {
            if (strlen($_POST['editar']) > 0) {
                //echo "UPDATE `mensajes` SET  mensaje ='".$_POST['_mensaje']."', fecha=now() , destino= '".$_POST['_ramos']."'  where id like '".$_POST['_id']."'  ";
                Connection::runQuery("UPDATE `mensajes` SET   titulo='" . $_POST['_titulo'] . "', mensaje ='" . $_POST['_mensaje'] . "',  fecha=now() , destino= '" . $_POST['_ramos'] . "'  where id like '" . $_POST['_id'] . "'  ");
            } else {
                //  INSERT INTO `promo`(`id`, `descripcion`, `precio`, `imagen`, `estado`) VALUES ([value-1],[value-2],[value-3],[value-4],[value-5])
                Connection::runQuery("INSERT INTO `mensajes`(titulo,`mensaje`, `fecha`, `cantidad`, `destino`, `estado`) VALUES  ('" . $_POST['_titulo'] . "', '" . $_POST['_mensaje'] . "', now(),0,'" . $_POST['_ramos'] . "',1)  ");
            }
        }
        ?>

        <style>

            .box1 {
                height: 150px;
                width: 100%;
            }

            table.cabecera td {
                margin: 0px;
                padding-top: 4px;
                padding-right: 2px;


            }

            table.cabecera th {}

            table.cabecera {
                width: 100%;
                border-spacing: 1px;

            }

            .my-custom-scrollbar {
                position: relative;
                height: 150px;

                overflow: auto;
            }

            .table-wrapper-scroll-y {
                display: block;
            }

            .cargando {
                width: 100%;
                height: 100%;
                overflow: hidden;
                top: 0px;
                left: 0px;
                z-index: 10000;
                text-align: center;
                position: absolute;
                background-color: #B8B8B8;
                opacity: 0.6;
                filter: alpha(opacity=40);

            }

            .modalWindow {
                position: fixed;
                font-family: arial;
                font-size: 100%;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background: rgba(0, 0, 0, 0.2);
                z-index: 99999;
                opacity: 0;
                -webkit-transition: opacity 300ms ease-in;
                -moz-transition: opacity 400ms ease-in;
                transition: opacity 400ms ease-in;
                pointer-events: none;
            }

            .modalHeader h3 {
                color: #189CDA;
                border-bottom: 2px groove #efefef;
            }

            .modalWindow:target {
                opacity: 1;
                pointer-events: auto;
            }

            .modalWindow>div {
                width: 580px;
                position: relative;
                margin: 5% auto;
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
                border-radius: 5px;
                background: #fff;
            }

            .modalWindow .modalHeader {
                padding: 5px 20px 0px 20px;
            }

            .modalWindow .modalContent {
                padding: 0px 20px 5px 20px;
            }

            .modalWindow .modalFooter {
                padding: 8px 20px 8px 20px;
            }

            .modalFooter {
                background: #F1F1F1;
                border-top: 1px solid #999;
                -moz-box-shadow: inset 0px 13px 12px -14px #888;
                -webkit-box-shadow: inset 0px 13px 12px -14px #888;
                box-shadow: inset 0px 13px 12px -14px #888;
            }

            .modalFooter p {
                color: #D4482D;
                text-align: right;
                margin: 0;
                padding: 5px;
            }

            .ok,
            .close,
            .cancel {
                background: #189CDA;
                color: #FFFFFF;
                line-height: 25px;
                text-align: center;
                text-decoration: none;
                font-weight: bold;
                -webkit-border-radius: 2px;
                -moz-border-radius: 2px;
                border-radius: 2px;
                -moz-box-shadow: 1px 1px 3px #000;
                -webkit-box-shadow: 1px 1px 3px #000;
                box-shadow: 1px 1px 3px #000;
            }


            .close {
                position: absolute;
                right: 5px;
                top: 5px;
                width: 22px;
                height: 22px;
                font-size: 10px;
            }

            .ok,
            .cancel {
                width: 80px;
                float: right;
                margin-left: 20px;
            }

            .ok:hover {
                background: #D4482D;
            }

            .close:hover,
            .cancel:hover {
                background: #D4482D;
            }

            .clear {
                float: none;
                clear: both;
            }

            .img-container {
                display: block;
                margin-left: auto;
                margin-right: auto;
                width: 50%;
            }
        </style>
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Mensajes &nbsp;&nbsp;&nbsp;&nbsp;</h1>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-success" onclick="nuevo()" id="btnagregar"><span class="fa fa-plus-circle"></span> Nuevo</button>
                                    <button type="button" id="exportarContactosB2B" class="btn btn-primary"><span class="fa fa-arrow-circle-down"></span> Exportar</button>
                                    <button type="button" id="btnCancelarForm" onclick="cancelarform()" class="btn btn-default"><span class="fa fa-reply"></span> Volver</button>


                                </div>
                                <br>
                            </div>

                            <div class="panel-body table-responsive" id="listadoregistros">
                                <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                    <th>Codigo</th>
                                    <th>Titulo</th>
                                    <th>Mensaje</th>
                                    <th>Clientes</th>
                                    <th>Fecha/Hora</th>
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

                                        <label for="id">Código</label>
                                        <input class="form-control" id="id" type="text" name="id" disabled="disabled">
                                    </div>

                                    <div class="form-group">
                                        <label for="titulo">Título</label>
                                        <input class="form-control" id="titulo" type="text" name="titulo" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="mensaje">Mensaje</label>
                                        <textarea class="form-control" rows="3" id="mensaje" name="mensaje" required></textarea>
                                    </div>

                                    <div  class="form-group">
                                        <label class="custom-file-label" for="mensajes">Elegir archivo</label>
                                          <input type="file" class="form-control" name="mensajes" id="mensajes">
                                        </div>
                                    <div  class="form-group">
                                        <div class="pull-right">

                                            <button type="submit" id="btnAgregarForm" class="btn btn-success"><span class="fa fa-save"></span> Guardar</button>
                                        </div>
                                    </div>
                                 </form>
                            </div>
                            <hr>

                            <table id="tbclientesByExcel" class="table table-striped table-bordered table-condensed table-hover">
                                <thead bgcolor="#D3DCE3">
                                    <th>Telefono</th>
                                    <th>Codigo</th>
                                    <th>RazonSocial</th>
                                    <th>Direccion</th>
                                    <th>Vendedor</th>
                                    <th>Localidad</th>
                                    <th>Ramo</th>
                                    <th>Zona</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
            </section>
        </div>
<!--        <div id="bloquea" class="cargando" style="display:none;">-->
            <div class="modal fade" id="myModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" align="center">Enviando mensajes</h5>

                        </div>
                        <div class="modal-body">
                            <h5 align="center"> Espere un momento... </h5>
                            <p align="center" id="contador"></p>
                            <img class="img-container" alt="Espere..." src="loading.gif" />


                        </div>

                    </div>
                </div>
            </div>
        </div>
       <script>
            $("#exportarContactosB2B").click(function () {
                exportar($('#id').val())
            })
        </script>


        <?php
    } else {
        require 'noacceso.php';
    }

    require 'footer.php';
    ?>
    <script>
        document.title = "Atiende | Mensajes a clientes";
    </script>
    <script src="scripts/wsmensajesb2b.js?t=<?php echo time(); ?>"></script>

    <script src="scripts/mensajeb2b.js?t=<?php echo time(); ?>"></script>

    <?php
}

ob_end_flush();
?>

