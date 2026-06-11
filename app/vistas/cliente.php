<?php
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.html");
} else {
    require 'headerv1.php';
    if ($_SESSION['ventas'] == 1) {?>
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
/* Formulario de edición — secciones agrupadas */
#formularioregistros .form-section + .form-section { margin-top:1.25rem; padding-top:1.1rem; border-top:1px solid #eef0f4; }
#formularioregistros .form-section__title { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#727cf5; margin-bottom:.85rem; display:flex; align-items:center; gap:.45rem; }
#formularioregistros .form-section__title i { font-size:1rem; }
#formularioregistros .form-section__hint { font-weight:500; text-transform:none; letter-spacing:0; color:#aab1bd; font-size:.72rem; }
#formularioregistros .form-label { font-size:.75rem; font-weight:600; color:#6c757d; margin-bottom:.3rem; }
#formularioregistros .form-control:focus { border-color:#b3a8f5; box-shadow:0 0 0 .15rem rgba(114,124,245,.12); }
#formularioregistros .form-footer { margin-top:1.5rem; padding-top:1rem; border-top:1px solid #eef0f4; display:flex; justify-content:flex-end; gap:.5rem; }
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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Base de Datos</a></li>
                            <li class="breadcrumb-item active"> Clientes </li>
                        </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-primary rounded-pill pull-right sombra-logo me-2" id="btnNuevo" onclick="nuevoCliente()" type="button">
                        <i class="mdi mdi-plus me-1"></i> Nuevo
                    </button>
                    <button class="btn btn-success rounded-pill pull-right sombra-logo"  id="btnExportar" onClick="exportarClientes()">
                        <i class="mdi mdi-file-excel-outline me-1"></i> Exportar
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
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('clientes').click()">
                                        <i class="uil uil-file-upload-alt upload-zone__icon" id="uploadIcon"></i>
                                        <div>
                                            <div class="upload-zone__label" id="uploadLabel">
                                                Importar clientes desde Excel <span class="text-muted fw-normal">.xlsx / .xls / .csv</span>
                                            </div>
                                            <div class="upload-zone__filename d-none" id="uploadFilename"></div>
                                        </div>
                                        <button type="button" class="upload-zone__clear d-none" id="uploadClear" onclick="clearUpload(event)" title="Quitar archivo">
                                            <i class="mdi mdi-close-circle"></i>
                                        </button>
                                        <input type="file" name="clientes" id="clientes" accept=".xlsx,.xls,.csv" class="d-none">
                                    </div>
                                    <button class="btn btn-sm btn-info rounded-pill sombra-logo" type="submit" id="btnImportar" disabled>
                                        <i class="mdi mdi-cloud-upload me-1"></i> Importar
                                    </button>
                                </div>
                                <div id="mensaje" class="mt-2"></div>
                            </form>
                        </div>
                        <script>
                        document.getElementById('clientes').addEventListener('change', function() {
                            var z=document.getElementById('uploadZone'),i=document.getElementById('uploadIcon'),l=document.getElementById('uploadLabel'),f=document.getElementById('uploadFilename'),c=document.getElementById('uploadClear'),b=document.getElementById('btnImportar');
                            if(this.files&&this.files[0]){f.textContent=this.files[0].name;f.classList.remove('d-none');l.classList.add('d-none');i.className='uil uil-check-circle upload-zone__icon';z.classList.add('has-file');c.classList.remove('d-none');b.disabled=false;}
                        });
                        function clearUpload(e){e.stopPropagation();document.getElementById('clientes').value='';document.getElementById('uploadFilename').classList.add('d-none');document.getElementById('uploadLabel').classList.remove('d-none');document.getElementById('uploadIcon').className='uil uil-file-upload-alt upload-zone__icon';document.getElementById('uploadZone').classList.remove('has-file');document.getElementById('uploadClear').classList.add('d-none');document.getElementById('btnImportar').disabled=true;}
                        </script>
                        <!-- Filtros (server-side: buscan/filtran en TODO el dataset) -->
                        <div id="filtrosCliente" class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" id="fBuscar" class="form-control" placeholder="Buscar en todos los datos del cliente...">
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Vendedor</label>
                                <select id="fVendedor" class="form-select form-select-sm"><option value="">Todos</option></select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Ramo</label>
                                <select id="fRamo" class="form-select form-select-sm"><option value="">Todos</option></select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Zona</label>
                                <select id="fZona" class="form-select form-select-sm"><option value="">Todas</option></select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Lista</label>
                                <select id="fLista" class="form-select form-select-sm"><option value="">Todas</option></select>
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
                            <table id="tbllistado" class="table table-striped table-centered mb-0  nowrap w-100">
                                <thead >
                                    <th>Opciones</th>
                                    <th>Codigo</th>
                                    <th>Vendedor</th>
                                    <th>RazonSocial</th>
                                    <th>Direccion</th>
                                    <th>Localidad</th>
                                    <th>Telefono</th>
                                    <th>Ramo</th>
                                    <th>Zona</th>
                                    <th>Lista</th>
                                    <th>Latitud</th>
                                    <th>Longitud</th>
                                    <th>Deposito</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                        <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Editar Cliente</span></h6>
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <input type="hidden" name="modo" id="modo" value="editar">

                                    <!-- Identificación -->
                                    <div class="form-section">
                                        <div class="form-section__title"><i class="mdi mdi-card-account-details-outline"></i> Identificación</div>
                                        <div class="row g-3">
                                            <div class="col-lg-3 col-md-4">
                                                <label class="form-label">Código</label>
                                                <input class="form-control" type="text" name="codigo" id="codigo" maxlength="255" placeholder="Ej: D0001" required>
                                                <small class="text-muted" id="codigoHint" style="display:none;">El código es la clave del cliente; no se puede cambiar al editar.</small>
                                            </div>
                                            <div class="col-lg-5 col-md-8">
                                                <label class="form-label">Razón Social</label>
                                                <input class="form-control" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Razón Social del cliente" required>
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <label class="form-label">CUIL</label>
                                                <input class="form-control" type="text" name="cuil" id="cuil" maxlength="20" placeholder="Opcional">
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <label class="form-label">DNI</label>
                                                <input class="form-control" type="text" name="dni" id="dni" maxlength="20" placeholder="Opcional">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ubicación y contacto -->
                                    <div class="form-section">
                                        <div class="form-section__title"><i class="mdi mdi-map-marker-outline"></i> Ubicación y contacto</div>
                                        <div class="row g-3">
                                            <div class="col-lg-6">
                                                <label class="form-label">Dirección</label>
                                                <input class="form-control" type="text" name="direccion" id="direccion" maxlength="100" placeholder="Calle y número" required>
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Localidad</label>
                                                <input class="form-control" type="text" name="localidad" id="localidad" maxlength="20" placeholder="Localidad">
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Teléfono</label>
                                                <input class="form-control" type="text" name="telefono" id="telefono" maxlength="20" placeholder="Número de teléfono">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Datos comerciales -->
                                    <div class="form-section">
                                        <div class="form-section__title"><i class="mdi mdi-storefront-outline"></i> Datos comerciales</div>
                                        <div class="row g-3">
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Vendedor</label>
                                                <input class="form-control" type="text" name="vendedor" id="vendedor" maxlength="50" placeholder="Vendedor">
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Ramo</label>
                                                <input class="form-control" type="text" name="ramo" id="ramo" maxlength="70" placeholder="Ramo">
                                            </div>
                                            <div class="col-lg-2 col-md-4">
                                                <label class="form-label">Zona</label>
                                                <input class="form-control" type="text" name="zona" id="zona" placeholder="Zona">
                                            </div>
                                            <div class="col-lg-2 col-md-4">
                                                <label class="form-label">Lista</label>
                                                <input class="form-control" type="text" name="lista" id="lista" maxlength="20" placeholder="Lista de precio">
                                            </div>
                                            <div class="col-lg-2 col-md-4">
                                                <label class="form-label">Depósito</label>
                                                <input class="form-control" type="text" name="deposito" id="deposito" maxlength="20" placeholder="Depósito">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Geolocalización -->
                                    <div class="form-section">
                                        <div class="form-section__title"><i class="mdi mdi-crosshairs-gps"></i> Geolocalización <span class="form-section__hint">— opcional</span></div>
                                        <div class="row g-3">
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Latitud</label>
                                                <input class="form-control" type="text" name="latitud" id="latitud" maxlength="50" placeholder="-32.89">
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Longitud</label>
                                                <input class="form-control" type="text" name="longitud" id="longitud" maxlength="50" placeholder="-68.84">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-footer">
                                        <button class="btn btn-light rounded-pill" type="button" onclick="cancelarform()">
                                            <i class="mdi mdi-close me-1"></i> Cancelar
                                        </button>
                                        <button class="btn btn-success rounded-pill sombra-logo" type="submit" id="btnGuardar">
                                            <i class="mdi mdi-content-save-all me-1"></i> Guardar
                                        </button>
                                    </div>
                                </form>
                    </div>
                </div>
            </div>
        </div>
    <?php }
    if ($_SESSION['ventas'] == 10) {
        ?>
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <div class="box-title">Clientes</div>
                                <div class="box-tools">
                                    <a class="btn btn-success pull-right" id="btnExportar" href="#"
                                       onClick="exportarClientes()"> <i
                                                class="fa fa-file-excel-o"></i> Exportar</a>
                                    <button class="btn btn-default  pull-right" id="btnCancel" onclick="cancelarform()"  type="button"><i class="fa fa-arrow-circle-left"></i> Volver
                                    </button>
                                </div>
                            </div>
                            <br>
                            <div id="subirarchivo">
                                <div class="row">
                                    <div class="col-md-8">
                                        <form method="post" enctype="multipart/form-data" id="formUp" name="formUp">

                                            <div class="form-group col-lg-6 col-md-6 col-xs-12">

                                                <input class="form-control" type="file" name="clientes" id="clientes">
                                            </div>

                                            <div class="form-group col-lg-6 col-md-6 col-xs-12">

                                                <button class="btn btn-info" type="submit"><i
                                                            class="fa fa-cloud-upload"></i> importar
                                                </button>
                                            </div>
                                            <samp id="mensaje"> </samp>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive" id="listadoregistros">
                                    <table id="tbllistado"  class="table table-striped table-bordered table-condensed table-hover">
                                        <thead>
                                        <th>Opciones</th>
                                        <th>Codigo</th>
                                        <th>Vendedor</th>
                                        <th>RazonSocial</th>
                                        <th>Direccion</th>
                                        <th>Localidad</th>
                                        <th>Telefono</th>
                                        <th>Ramo</th>
                                        <th>Zona</th>
                                        <th>Lista</th>
                                        <th>Latitud</th>
                                        <th>Longitud</th>
                                        <th>Deposito</th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="" style="height: 400px;" id="formularioregistros">
                                    <form action="" name="formulario" id="formulario" method="POST">
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Nombre : </label>
                                            <input class="form-control" type="hidden" name="codigo" id="codigo">
                                            <input class="form-control" type="text" name="nombre" id="nombre"
                                                   maxlength="100"
                                                   placeholder="Nombre del cliente" required>
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Dirección : </label>
                                            <input class="form-control" type="text" name="direccion" id="direccion"
                                                   maxlength="100"
                                                   placeholder="Direccion" required>
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Localidad : </label>
                                            <input class="form-control" type="text" name="localidad" id="localidad"
                                                   maxlength="20"
                                                   placeholder="Localidad">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Ramo</label>
                                            <input class="form-control" type="text" name="ramo" id="ramo" maxlength="70"
                                                   placeholder="Ramo">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Telefono </label>
                                            <input class="form-control" type="text" name="telefono" id="telefono"
                                                   maxlength="20"
                                                   placeholder="Número de Telefono">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Zona </label>
                                            <input class="form-control" type="text" name="zona" id="zona"
                                                   placeholder="Zona">
                                        </div>

                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Lista  </label>
                                            <input class="form-control" type="text" name="lista" id="lista" maxlength="20"
                                                   placeholder="Lista de precio">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Vendedor  </label>
                                            <input class="form-control" type="text" name="vendedor" id="vendedor"
                                                   maxlength="50"
                                                   placeholder="Vendedor">
                                        </div>

                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Depósito  </label>
                                            <input class="form-control" type="text" name="deposito" id="deposito" maxlength="20"
                                                   placeholder="Depósito">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Latitud  </label>
                                            <input class="form-control" type="text" name="latitud" id="latitud"
                                                   maxlength="50"
                                                   placeholder="Latitud">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Longitud  </label>
                                            <input class="form-control" type="text" name="longitud" id="longitud"
                                                   maxlength="50"
                                                   placeholder="Longitud">
                                        </div>

                                        <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <button class="btn btn-success pull-right" type="submit" id="btnGuardar"><i
                                                        class="fa fa-save"></i>
                                                Guardar
                                            </button>
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
        //require 'noacceso.php';
    }
    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Clientes";
    </script>
    <script src="scripts/cliente.js?t=<?php echo time(); ?>""></script>
    <?php
}

ob_end_flush();
?>