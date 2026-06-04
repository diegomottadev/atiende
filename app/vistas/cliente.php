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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Base de Datos</a></li>
                            <li class="breadcrumb-item active"> Clientes </li>
                        </ol>
                    </div>
                    <div class="float-start mt-3"><h4 class="page-title"> Clientes</h4></div>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
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
                    </div>
                    <hr class="my-0">
                    <div class="card-body p-0">
                        <div class="table-responsive" id="listadoregistros"> <!-- dt-responsive -->
                            <table id="tbllistado" class="table table-sm table-striped table-centered mb-0  nowrap w-100">
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
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Nombre</label>
                                                <input class="form-control" type="hidden" name="codigo" id="codigo">
                                                <input class="form-control" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Nombre del cliente" required>                                                
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Direcci&oacute;n</label>
                                                <input class="form-control" type="text" name="direccion" id="direccion" maxlength="100" placeholder="Direccion" required>
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Localidad</label>                                                
                                                <input class="form-control" type="text" name="localidad" id="localidad" maxlength="20" placeholder="Localidad">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Ramo</label>
                                                <input class="form-control" type="text" name="ramo" id="ramo" maxlength="70" placeholder="Ramo">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Telefono</label>                                                
                                                <input class="form-control" type="text" name="telefono" id="telefono" maxlength="20" placeholder="Número de Telefono">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Zona</label>
                                                <input class="form-control" type="text" name="zona" id="zona" placeholder="Zona">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Lista</label>                                                
                                                <input class="form-control" type="text" name="lista" id="lista" maxlength="20" placeholder="Lista de precio">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Vendedor</label>
                                                <input class="form-control" type="text" name="vendedor" id="vendedor" maxlength="50" placeholder="Vendedor">
                                            </div>                                           
                                        </div>
                                    </div>  
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Dep&oacute;sito</label>                                                
                                                <input class="form-control" type="text" name="deposito" id="deposito" maxlength="20" placeholder="Depósito">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Latitud</label>
                                                <input class="form-control" type="text" name="latitud" id="latitud" maxlength="5" placeholder="Latitud">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Longitud</label>                                                
                                                <input class="form-control" type="text" name="longitud" id="longitud" maxlength="50" placeholder="Longitud">
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