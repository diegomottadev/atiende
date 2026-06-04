<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require 'headerv1.php';
    if ($_SESSION['repartos'] == 1) {?>
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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Repartos</a></li>
                            <li class="breadcrumb-item active"> Repartidores </li>
                        </ol>
                    </div>
                    <div class="float-start mt-3"><h4 class="page-title"> Repartidores</h4></div>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">
                <div class="text-sm-end d-flex justify-content-end gap-1">
                    <button class="btn btn-sm btn-primary rounded-pill sombra-logo" id="btnExportar" onClick="exportar()">
                        <i class="mdi mdi-file-excel-outline"></i> Exportar
                    </button>
                    <button class="btn btn-sm btn-success rounded-pill sombra-logo" onClick="nuevo()" id="btnAgregar">
                        <i class="mdi mdi-plus"></i> Nuevo
                    </button>
                    <button class="btn btn-sm btn-light rounded-pill sombra-logo" id="btnCancel" onclick="cancelarform()" type="button">
                        <i class="mdi mdi-arrow-left-circle"></i> Volver
                    </button>
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel ribbon-box">
                    <div class="card-body mt-n2">

                        <!-- Importar repartidores -->
                        <style>
                        .upload-zone {
                            display: inline-flex;
                            align-items: center;
                            gap: 10px;
                            border: 1.5px dashed #b3a8f5;
                            border-radius: 8px;
                            padding: 9px 16px;
                            background: #f9f8ff;
                            cursor: pointer;
                            transition: border-color .18s, background .18s;
                            max-width: 480px;
                            width: 100%;
                        }
                        .upload-zone:hover { border-color: #6650EA; background: #f0edff; }
                        .upload-zone.has-file { border-style: solid; border-color: #0acf97; background: #f0fdf8; }
                        .upload-zone__icon { font-size: 1.3rem; color: #6650EA; flex-shrink: 0; }
                        .upload-zone.has-file .upload-zone__icon { color: #0acf97; }
                        .upload-zone__label { font-size: .78rem; color: #6c757d; line-height: 1.3; }
                        .upload-zone__filename { font-size: .8rem; font-weight: 600; color: #0acf97; }
                        .upload-zone__clear {
                            margin-left: auto;
                            flex-shrink: 0;
                            background: none;
                            border: none;
                            color: #aaa;
                            font-size: 1rem;
                            cursor: pointer;
                            padding: 0 2px;
                            line-height: 1;
                        }
                        .upload-zone__clear:hover { color: #fa5c7c; }
                        </style>
                        <div id="import-repartidores" class="mb-3">
                            <form method="post" enctype="multipart/form-data" id="formUp" name="formUp">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('repartidores').click()">
                                        <i class="uil uil-file-upload-alt upload-zone__icon" id="uploadIcon"></i>
                                        <div>
                                            <div class="upload-zone__label" id="uploadLabel">
                                                Importar desde Excel <span class="text-muted fw-normal">.xlsx / .xls / .csv</span>
                                            </div>
                                            <div class="upload-zone__filename d-none" id="uploadFilename"></div>
                                        </div>
                                        <button type="button" class="upload-zone__clear d-none" id="uploadClear"
                                                onclick="clearUpload(event)" title="Quitar archivo">
                                            <i class="mdi mdi-close-circle"></i>
                                        </button>
                                        <input type="file" name="repartidores" id="repartidores"
                                               accept=".xlsx,.xls,.csv" class="d-none">
                                    </div>
                                    <button class="btn btn-sm btn-info rounded-pill sombra-logo" type="submit" id="btnImportar" disabled>
                                        <i class="mdi mdi-cloud-upload me-1"></i> Importar
                                    </button>
                                </div>
                                <div id="mensaje" class="mt-2"></div>
                            </form>
                        </div>
                        <script>
                        document.getElementById('repartidores').addEventListener('change', function() {
                            var zone = document.getElementById('uploadZone');
                            var icon = document.getElementById('uploadIcon');
                            var label = document.getElementById('uploadLabel');
                            var fname = document.getElementById('uploadFilename');
                            var clear = document.getElementById('uploadClear');
                            var btn   = document.getElementById('btnImportar');
                            if (this.files && this.files[0]) {
                                fname.textContent = this.files[0].name;
                                fname.classList.remove('d-none');
                                label.classList.add('d-none');
                                icon.className = 'uil uil-check-circle upload-zone__icon';
                                zone.classList.add('has-file');
                                clear.classList.remove('d-none');
                                btn.disabled = false;
                            }
                        });
                        function clearUpload(e) {
                            e.stopPropagation();
                            var zone = document.getElementById('uploadZone');
                            var icon = document.getElementById('uploadIcon');
                            var label = document.getElementById('uploadLabel');
                            var fname = document.getElementById('uploadFilename');
                            var clear = document.getElementById('uploadClear');
                            var btn   = document.getElementById('btnImportar');
                            document.getElementById('repartidores').value = '';
                            fname.textContent = '';
                            fname.classList.add('d-none');
                            label.classList.remove('d-none');
                            icon.className = 'uil uil-file-upload-alt upload-zone__icon';
                            zone.classList.remove('has-file');
                            clear.classList.add('d-none');
                            btn.disabled = true;
                        }
                        </script>

                        <!-- Tabla -->
                        <div class="table-responsive" id="panelRepartidores">
                            <table id="tblRepartidores" class="table table-sm table-striped table-centered mb-0 nowrap w-100">
                                <thead>
                                    <th style="width:80px;">Acciones</th>
                                    <th style="width:60px;">Cod</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Formulario -->
                        <div id="panelFormRepartidor">
                            <div class="ribbon ribbon-info float-start mt-n2 mb-1">
                                <i class="uil uil-truck me-1"></i> <span id="ribbon-text">Nuevo Repartidor</span>
                            </div>
                            <form action="" name="formRepartidor" id="formRepartidor" method="POST" class="ribbon-content">
                                <input type="hidden" name="id" id="id">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3 position-relative">
                                            <label class="form-label">
                                                <i class="uil uil-user me-1 text-muted"></i> Nombre del repartidor <span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control" type="text" name="nombre" id="nombre" maxlength="50" placeholder="Ej: Juan García" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 position-relative">
                                            <label class="form-label">
                                                <i class="uil uil-phone me-1 text-muted"></i> Teléfono con código de área
                                            </label>
                                            <input class="form-control" type="text" name="telefono" id="telefono" maxlength="256" placeholder="Ej: 5491112345678">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-sm-end d-flex justify-content-end gap-1">
                                        <button class="btn btn-sm btn-light rounded-pill sombra-logo" type="button" onclick="cancelarform()">
                                            <i class="mdi mdi-arrow-left-circle"></i> Cancelar
                                        </button>
                                        <button class="btn btn-sm btn-success rounded-pill sombra-logo" type="submit" id="btnGuardar">
                                            <i class="mdi mdi-content-save-all"></i> Guardar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php }
    if ($_SESSION['repartos'] == 10) {

        ?>
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <div class="box-title">Repartidores&nbsp;&nbsp;</div>
                                <div class="box-tools">

                                        <button class="btn btn-default pull-right" id="btnCancel" onclick="cancelarform()" type="button"><i
                                                class="fa fa-arrow-circle-left"></i> Volver
                                        </button>
                                        <button class="btn btn-primary pull-right" onClick="exportar()" id="btnExportar"> <i class="fa fa-file-excel-o"></i> Exportar</button>
                                        <button class="btn btn-success pull-right" style="margin-right: 10px;" onClick="nuevo()" id="btnAgregar"> <i class="fa fa-plus-circle"></i> Nuevo</button><br>

                                </div>
                            </div>
                            <br>
                            <div class="row" id="import-repartidores">
                                <div class="col-md-8">
                                    <div >
                                        <form method="post" enctype="multipart/form-data" id="formUp" name="formUp">

                                            <div class="form-group col-lg-6 col-md-6 col-xs-12">

                                                <input class="form-control" type="file" name="repartidores"
                                                       id="repartidores">
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
                                <div class=" table-responsive" id="panelRepartidores">
                                    <table id="tblRepartidores" class="table table-striped table-bordered table-condensed table-hover">
                                        <thead>
                                        <th>Cod</th>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Acciones</th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div  style="height: 400px;" id="panelFormRepartidor">
                                    <form action="" name="formRepartidor" id="formRepartidor" method="POST">
                                        <input class="form-control" type="hidden" name="id" id="id">

                                        <div class="col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Nombre</label>
                                            <input class="form-control" type="text" name="nombre" id="nombre" maxlength="50"
                                                   placeholder="Nombre" required>
                                        </div>
                                        <div class=" col-lg-6 col-md-6 col-xs-12">
                                            <label for="">Telefono</label>
                                            <input class="form-control" type="text" name="telefono" id="telefono"
                                                   maxlength="256" placeholder="Telefono">
                                        </div>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
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
        document.title = "Atiende | Repartidores";
    </script>
    <script src="scripts/repartidor.js"></script>
    <?php
}

ob_end_flush();
?>

