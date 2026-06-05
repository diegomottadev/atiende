<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require 'headerv1.php';
    if ($_SESSION['reclamos'] == 1) {?>
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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Reclamos</a></li>
                            <li class="breadcrumb-item active"> Sectores </li>
                        </ol>
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
                    <div class="card-body pb-2" id="filtrosArea">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" id="fBuscar" class="form-control" placeholder="Buscar en sectores...">
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="button" id="fLimpiar" class="btn btn-sm btn-soft-secondary" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Borrar filtros"><i class="mdi mdi-filter-remove-outline me-1"></i> Limpiar</button>
                            </div>
                        </div>
                    </div>
                    <hr class="my-0">
                    <div class="card-body p-0" id="listadoregistros">
                        <div class="table-responsive">
                            <table id="tbllistado" class="table table-striped table-centered mb-0 dt-responsive nowrap w-100">
                                <thead >
                                    <th style="min-width: 6em!important;">Opciones</th>
                                    <th>ID</th>
                                    <th>Sector</th>
                                    <th>Teléfono</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                        <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Nuevo Sector</span></h6>
                        <form action="" name="formulario" id="formulario" method="POST">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3 position-relative">
                                        <label for="" class="form-label">Sector</label>
                                        <input class="form-control" type="hidden" name="idpersona" id="idpersona">
                                        <input class="form-control" type="hidden" name="tipo_persona" id="tipo_persona" value="Proveedor">
                                        <input class="form-control" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Nombre del sector" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3 position-relative">
                                        <label for="" class="form-label">Telefono</label>
                                        <input class="form-control" type="text" name="telefono" id="telefono" maxlength="20" placeholder="Número de Telefono">
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
    <?php 
    } else {
        //require 'noacceso.php';
    }
    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Reclamos | Sectores";
    </script>
    <script src="scripts/area.js?t=<?php echo time(); ?>"></script>

    <?php
}

ob_end_flush();
?>
