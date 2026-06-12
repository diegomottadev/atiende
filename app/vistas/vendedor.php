<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require 'headerv1.php';
    if ($_SESSION['vendedores'] == 1) {?>
        <style>
#tbllistado thead th, table.dataTable thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
.upload-zone { display:inline-flex; align-items:center; gap:10px; border:1.5px dashed #b3a8f5; border-radius:8px; padding:9px 16px; background:#f9f8ff; cursor:pointer; transition:border-color .18s,background .18s; max-width:480px; width:100%; }
.upload-zone:hover { border-color:#6650EA; background:#f0edff; }
.upload-zone.has-file { border-style:solid; border-color:#0acf97; background:#f0fdf8; }
.upload-zone__icon { font-size:1.3rem; color:#6650EA; flex-shrink:0; }
.upload-zone.has-file .upload-zone__icon { color:#0acf97; }
.upload-zone__label { font-size:.78rem; color:#6c757d; line-height:1.3; }
.upload-zone__filename { font-size:.8rem; font-weight:600; color:#0acf97; }
.upload-zone__clear { margin-left:auto; flex-shrink:0; background:none; border:none; color:#aaa; font-size:1rem; cursor:pointer; padding:0 2px; line-height:1; }
.upload-zone__clear:hover { color:#fa5c7c; }
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
                            <li class="breadcrumb-item active"> Vendedores </li>
                        </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-success rounded-pill pull-right sombra-logo"  id="btnExportar" onClick="exportarVendedores()">
                        <i class="mdi mdi-file-excel-outline me-1"></i> Exportar
                    </button>
                    <button class="btn btn-primary rounded-pill sombra-logo" id="btnAgregar" onClick="nuevo()" type="button">
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
                    <div id="panelLista">
                    <div class="card-body pb-2">
                        <div id="subirarchivo" class="mb-3">
                            <form method="post" enctype="multipart/form-data" id="formUp" name="formUp">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('vendedores').click()">
                                        <i class="uil uil-file-upload-alt upload-zone__icon" id="uploadIcon"></i>
                                        <div>
                                            <div class="upload-zone__label" id="uploadLabel">
                                                Importar vendedores desde Excel <span class="text-muted fw-normal">.xlsx / .xls / .csv</span>
                                            </div>
                                            <div class="upload-zone__filename d-none" id="uploadFilename"></div>
                                        </div>
                                        <button type="button" class="upload-zone__clear d-none" id="uploadClear" onclick="clearUpload(event)" title="Quitar archivo">
                                            <i class="mdi mdi-close-circle"></i>
                                        </button>
                                        <input type="file" name="vendedores" id="vendedores" accept=".xlsx,.xls,.csv" class="d-none">
                                    </div>
                                    <button class="btn btn-sm btn-info rounded-pill sombra-logo" type="submit" id="btnImportar" disabled>
                                        <i class="mdi mdi-cloud-upload me-1"></i> Importar
                                    </button>
                                </div>
                                <div id="mensaje" class="mt-2"></div>
                            </form>
                        </div>
                        <script>
                        document.getElementById('vendedores').addEventListener('change', function() {
                            var z=document.getElementById('uploadZone'),i=document.getElementById('uploadIcon'),l=document.getElementById('uploadLabel'),f=document.getElementById('uploadFilename'),c=document.getElementById('uploadClear'),b=document.getElementById('btnImportar');
                            if(this.files&&this.files[0]){f.textContent=this.files[0].name;f.classList.remove('d-none');l.classList.add('d-none');i.className='uil uil-check-circle upload-zone__icon';z.classList.add('has-file');c.classList.remove('d-none');b.disabled=false;}
                        });
                        function clearUpload(e){e.stopPropagation();document.getElementById('vendedores').value='';document.getElementById('uploadFilename').classList.add('d-none');document.getElementById('uploadLabel').classList.remove('d-none');document.getElementById('uploadIcon').className='uil uil-file-upload-alt upload-zone__icon';document.getElementById('uploadZone').classList.remove('has-file');document.getElementById('uploadClear').classList.add('d-none');document.getElementById('btnImportar').disabled=true;}
                        </script>
                        <div id="filtrosVendedor" class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" id="fBuscar" class="form-control" placeholder="Buscar vendedor...">
                                </div>
                            </div>
                            <div class="col-6 col-md-1">
                                <button type="button" id="fLimpiar" class="btn btn-sm btn-soft-secondary w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Borrar filtros"><i class="mdi mdi-filter-remove-outline"></i></button>
                            </div>
                        </div>
                    </div>
                    <hr class="my-0">
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" id="listadoregistros"> <!-- dt-responsive -->
                            <table id="tbllistado" class=" table table-striped table-centered mb-0  nowrap w-100">
                                <thead >
                                    <th>Opciones</th>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Telefono</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                        <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Editar Vendedor</span></h6>
                        <ul class="nav nav-tabs mb-3" id="vendedorTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-datos-btn" data-bs-toggle="tab" data-bs-target="#tab-datos" type="button" role="tab"><i class="mdi mdi-account-edit-outline me-1"></i> Editar Vendedor</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-clientes-btn" data-bs-toggle="tab" data-bs-target="#tab-clientes" type="button" role="tab"><i class="mdi mdi-account-group-outline me-1"></i> Clientes</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tab-datos" role="tabpanel">
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Codigo</label>
                                                <input class="form-control" type="hidden" name="codigo" id="codigo">
                                                <input class="form-control" type="text" name="idvendedor" id="idvendedor" required>                                                
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Nombre</label>
                                                <input class="form-control" type="text" name="nombre" id="nombre" maxlength="50" placeholder="Nombre" required>
                                            </div>                                           
                                        </div>                                    
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Telefono</label>
                                                <input class="form-control" type="text" name="telefono" id="telefono" maxlength="256" placeholder="Ej: 3764278402">
                                                <small class="text-muted">Número de WhatsApp. Podés cargarlo con o sin código de país (ej: <code>3764278402</code> o <code>5493764278402</code>); se normaliza solo al guardar. No incluyas el <code>15</code>.</small>
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
                            <div class="tab-pane fade" id="tab-clientes" role="tabpanel">
                                <div id="clientesAvisoNuevo" class="alert alert-warning d-none">
                                    <i class="mdi mdi-information-outline me-1"></i> Guardá el vendedor primero para poder asignarle clientes.
                                </div>
                                <div id="clientesVendedorInfo" class="d-flex align-items-center gap-2 mb-3 p-2 px-3 rounded sombra-panel" style="background:#eef0ff;border-left:4px solid #727cf5;">
                                    <i class="mdi mdi-account-tie text-primary" style="font-size:1.6rem;"></i>
                                    <div>
                                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Vendedor</div>
                                        <div class="fw-bold" id="clientesVendedorTexto" style="color:#4a4f9e;">—</div>
                                    </div>
                                </div>
                                <div id="clientesPanel" class="row g-3">
                                    <div class="col-lg-6">
                                        <h6 class="text-muted mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;"><i class="mdi mdi-account-multiple-plus-outline me-1"></i> Disponibles</h6>
                                        <div class="input-group input-group-sm mb-2" style="max-width:320px;">
                                            <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                            <input type="text" id="fBuscarDisp" class="form-control" placeholder="Buscar cliente...">
                                        </div>
                                        <div class="table-responsive">
                                            <table id="tblClientesDisponibles" class="table table-striped table-centered mb-0 nowrap w-100">
                                                <thead>
                                                    <th>Opciones</th>
                                                    <th>Codigo</th>
                                                    <th>Cliente</th>
                                                    <th>Localidad</th>
                                                    <th>Telefono</th>
                                                    <th>Vendedor</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <h6 class="text-muted mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;"><i class="mdi mdi-account-check-outline me-1"></i> Asignados a este vendedor</h6>
                                        <div class="input-group input-group-sm mb-2" style="max-width:320px;">
                                            <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                            <input type="text" id="fBuscarAsig" class="form-control" placeholder="Buscar cliente...">
                                        </div>
                                        <div class="table-responsive">
                                            <table id="tblClientesAsignados" class="table table-striped table-centered mb-0 nowrap w-100">
                                                <thead>
                                                    <th>Opciones</th>
                                                    <th>Codigo</th>
                                                    <th>Cliente</th>
                                                    <th>Localidad</th>
                                                    <th>Telefono</th>
                                                    <th>Vendedor</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php }
    if ($_SESSION['vendedores'] == 10) {

        ?>
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Vendedores&nbsp;&nbsp; </h1>
                                <div class="box-tools">
                                    <button class="btn btn-default" id="btnCancel" onclick="cancelarform()" type="button"><i
                                                class="fa fa-arrow-circle-left"></i> Volver
                                    </button>
                                    <a class="btn btn-success" style="margin-right: 10px;" onClick="exportarVendedores()" id="btnExportar"> <i class="fa fa-file-excel-o"></i> Exportar</a><br>
                                </div>
                            </div>
                            <br>
                            <div class="row" id="subirarchivo">
                                <div class="col-md-8">
                                    <div >
                                        <form method="post" enctype="multipart/form-data" id="formUp" name="formUp">

                                            <div class="form-group col-lg-6 col-md-6 col-xs-12">

                                                <input class="form-control" type="file" name="vendedores"
                                                       id="vendedores">
                                            </div>

                                            <div class="form-group col-lg-6 col-md-6 col-xs-12">

                                                <button class="btn btn-info" type="submit"><i
                                                            class="fa fa-cloud-upload"></i> Importar
                                                </button>
                                            </div>

                                            <br>

                                            <samp id="mensaje"> </samp>

                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="box-body">
                                <div class="table-responsive" id="listadoregistros">
                                    <table id="tbllistado"
                                           class="table table-striped table-bordered table-condensed table-hover">
                                        <thead>
                                        <th>Opciones</th>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Telefono</th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="" style="height: 400px;" id="formularioregistros">
                                <form action="" name="formulario" id="formulario" method="POST">

                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Codigo</label>
                                        <input class="form-control" type="text" name="idvendedor" id="idvendedor"  required>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Nombre</label>

                                        <input class="form-control" type="text" name="nombre" id="nombre" maxlength="50"
                                               placeholder="Nombre" required>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Telefono</label>
                                        <input class="form-control" type="text" name="telefono" id="telefono"
                                               maxlength="256" placeholder="Telefono">
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
            </section>
        </div>
        <?php
    } else {
        //require 'noacceso.php';
    }

    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Vendedores";
    </script>
    <script src="scripts/vendedor.js?t=<?php echo time(); ?>"></script>
    <?php
}

ob_end_flush();
?>

