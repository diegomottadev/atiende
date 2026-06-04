<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {

    require 'headerv1.php';
    
    if ($_SESSION['reclamos'] == 1) { ?>
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
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item" style="margin-top: -0.7em">
                                <a href="javascript: void(0);">                            
                                    <img src="../public/img/logo30x30.png" alt="" class="icono-ruta-ClubPedido" >            
                                    Atiende
                                </a>
                            </li>
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Reclamos</a></li>
                            <li class="breadcrumb-item active"> Motivos </li>
                        </ol>
                    </div>
                    <div class="float-start mt-3"><h4 class="page-title">Reclamos | Motivos</h4></div>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-success rounded-pill pull-right sombra-logo" id="btnAgregar" onclick="mostrarform(true)">
                        <i class="mdi mdi-plus me-1"></i> Nuevo
                    </button>
                    <button class="btn btn-light rounded-pill sombra-logo" id="btnCancel" onclick="cancelarform()" type="button">
                            <i class="mdi mdi-arrow-left-circle me-1"></i> Volver
                    </button>    
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel" style="border-top:3px solid #727cf5;">
                    <div class="card-body pb-2" id="listadoregistros">
                        <div class="table-responsive">
                            <table id="tbllistado" class="table table-sm table-striped table-centered mb-0 dt-responsive nowrap w-100">
                                <thead >
                                    <th style="min-width: 6em!important;">Opciones</th>
                                    <th>Codigo</th>
                                    <th>Motivo</th>
                                    <th>Area</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                        <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Nuevo Motivo</span></h6>
                        <form action="" name="formulario" id="formulario" method="POST">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3 position-relative">
                                        <label for="" class="form-label">Codigo</label>
                                        <input class="form-control" type="hidden" name="id" id="id">
                                        <input class="form-control" type="text" name="codigo" id="codigo" maxlength="100" placeholder="Codigo del motivo" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3 position-relative">
                                        <label for="" class="form-label">Motivo</label>
                                        <input class="form-control" type="text" name="motivo" id="motivo" maxlength="70" placeholder="motivo">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3 position-relative">
                                        <label for="" class="form-label">Area</label>
                                        <select class="form-control select2" data-toggle="select2" name="idarea" id="idarea" required>
                                            <option disabled>-- Seleccionar --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-sm-end">
                                    <button class="btn btn-success rounded-pill sombra-logo" type="submit" id="btnGuardar">
                                        <i class="mdi mdi-content-save-all"></i> Guardar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } else
    if ($_SESSION['reclamos'] == 10) {
        ?>
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                    <h1 class="box-title">Reclamos | Motivos</h1>
                                    <div class="box-tools pull-right">
                                        <button class="btn btn-success pull-right" id="btnAgregar"
                                                onclick="mostrarform(true)"><i class="fa fa-plus-circle"></i> Nuevo
                                        </button>
                                        <button class="btn btn-default" id="btnCancel" onclick="cancelarform()"
                                                type="button"><i
                                                    class="fa fa-arrow-circle-left"></i> Volver
                                        </button>
                                    </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive" id="listadoregistros">
                                        <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
                                            <thead>
                                            <th>Opciones</th>
                                            <th>Codigo</th>
                                            <th>Motivo</th>
                                            <th>Area</th>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                </div>
                                <div class="" style="height: 400px;" id="formularioregistros">
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Codigo</label>
                                        <input class="form-control" type="hidden" name="id" id="id">
                                        <input class="form-control" type="text" name="codigo" id="codigo" 
                                               maxlength="100" placeholder="Codigo de motivo" required>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Motivo</label>
                                        <input class="form-control" type="text" name="motivo" id="motivo" maxlength="70"
                                               placeholder="motivo">
                                    </div>

                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Area</label>
                                        <select name="idarea" id="idarea" class="form-control selectpicker"
                                                data-Live-search="true" required></select>
                                    </div>


                                    <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <label for="">&nbsp;</label>

                                        <div>
                                            <button class="btn btn-success pull-right" type="submit" id="btnGuardar"><i
                                                        class="fa fa-save"></i> Guardar
                                            </button>
                                        </div>


                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.box -->

            </section>
            <!-- /.content -->
        </div>
        <?php
    } else {
       // require 'noacceso.php';
    }
    require 'footerv1.php';
    
    ?>
    


    <script>
        document.title = "Atiende | Reclamos | Motivos";
    </script>
    <script src="scripts/motivo.js"></script>
    <style>
        #codigo{
            text-transform: uppercase;
        }        
    </style>

    <?php
}

ob_end_flush();
?>
