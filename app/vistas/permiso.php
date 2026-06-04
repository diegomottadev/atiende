<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {

    require 'header.php';
    if ($_SESSION['seguridad'] == 1) {
        ?>
        <div class="content-wrapper">
            <!-- Main content -->
            <section class="content">

                <!-- Default box -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Permisos
                                    <button id="btnagregar" class="btn btn-success" onclick="mostrarform(true)"><i
                                                class="fa fa-plus-circle"></i>Nuevo
                                    </button>
                                </h1>
                                <div class="box-tools pull-right">
                                </div>
                            </div>
                            <div class="box-body">
                            <div class="table-responsive" id="listadoregistros">
                                <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                    <th>Nombre</th>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                    <th>Nombre</th>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php
    } else {
        require 'noacceso.php';
    }
    require 'footer.php'
    ?>
    <script>
        document.title = "Atiende | Permisos";
    </script>
    <script src="scripts/permiso.js"></script>
    <?php
}

ob_end_flush();
?>
