<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {

    require 'headerv1.php';
    if ($_SESSION['bd'] == 1) {?>
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
/* (El indicador de orden — flecha única a la izquierda, asc/desc, negrita en
   la columna activa — vive ahora GLOBAL en headerv1.php y aplica a todas las
   tablas del sistema.) */
/* miniaturas clickeables (abren modal de imagen ampliada) */
#tbllistado tbody img { cursor:zoom-in; transition:transform .12s; }
#tbllistado tbody img:hover { transform:scale(1.08); }
/* Caja del código de barras */
.barcode-box{border:1px solid #e3e7f1;border-radius:10px;padding:12px;background:#fff;text-align:center;}
.barcode-box svg{max-width:100%;height:auto;}
/* Filas más compactas en la tabla de artículos */
#tbllistado tbody td{padding-top:.2rem!important;padding-bottom:.2rem!important;vertical-align:middle;}
#tbllistado tbody img{border-radius:5px;}
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
                            <li class="breadcrumb-item active"> Articulos </li>
                        </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-success rounded-pill pull-right sombra-logo"  id="btnExportar" onClick="exportarArticulos()">
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
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('articulos').click()">
                                        <i class="uil uil-file-upload-alt upload-zone__icon" id="uploadIcon"></i>
                                        <div>
                                            <div class="upload-zone__label" id="uploadLabel">
                                                Importar artículos desde Excel <span class="text-muted fw-normal">.xlsx / .xls / .csv</span>
                                            </div>
                                            <div class="upload-zone__filename d-none" id="uploadFilename"></div>
                                        </div>
                                        <button type="button" class="upload-zone__clear d-none" id="uploadClear" onclick="clearUpload(event)" title="Quitar archivo">
                                            <i class="mdi mdi-close-circle"></i>
                                        </button>
                                        <input type="file" name="articulos" id="articulos" accept=".xlsx,.xls,.csv" class="d-none">
                                    </div>
                                    <button class="btn btn-sm btn-info rounded-pill sombra-logo" type="submit" id="btnImportar" disabled>
                                        <i class="mdi mdi-cloud-upload me-1"></i> Importar
                                    </button>
                                </div>
                                <div id="mensaje" class="mt-2"></div>
                            </form>
                        </div>
                        <script>
                        document.getElementById('articulos').addEventListener('change', function() {
                            var z=document.getElementById('uploadZone'),i=document.getElementById('uploadIcon'),l=document.getElementById('uploadLabel'),f=document.getElementById('uploadFilename'),c=document.getElementById('uploadClear'),b=document.getElementById('btnImportar');
                            if(this.files&&this.files[0]){f.textContent=this.files[0].name;f.classList.remove('d-none');l.classList.add('d-none');i.className='uil uil-check-circle upload-zone__icon';z.classList.add('has-file');c.classList.remove('d-none');b.disabled=false;}
                        });
                        function clearUpload(e){e.stopPropagation();document.getElementById('articulos').value='';document.getElementById('uploadFilename').classList.add('d-none');document.getElementById('uploadLabel').classList.remove('d-none');document.getElementById('uploadIcon').className='uil uil-file-upload-alt upload-zone__icon';document.getElementById('uploadZone').classList.remove('has-file');document.getElementById('uploadClear').classList.add('d-none');document.getElementById('btnImportar').disabled=true;}
                        </script>
                        <?php
                            require_once "../modelos/Articulo.php";
                            $articulo = new Articulo();

                            $rspta = $articulo->listarCabecera();
                            while ($row = mysqli_fetch_assoc($rspta)) {
                                // $row[]="#";
                                $cabecera = array_keys($row);
                            }
                            array_unshift($cabecera, "#");
                            $cabecera[] = "imagen";
                        ?>
                        <!-- Filtros (client-side: buscan en TODO el dataset, no solo la página) -->
                        <div id="filtrosArticulo" class="d-flex flex-wrap align-items-end gap-2">
                            <!-- Zona de filtros de datos (qué filas se ven) -->
                            <div class="row g-2 align-items-end flex-grow-1">
                                <div class="col-12 col-md-3">
                                    <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Buscar</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                        <input type="text" id="fBuscar" class="form-control" placeholder="Buscar en todos los datos del producto...">
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Rubro</label>
                                    <select id="fRubro" class="form-select form-select-sm"><option value="">Todos</option></select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Subrubro</label>
                                    <select id="fSubrubro" class="form-select form-select-sm"><option value="">Todos</option></select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Línea</label>
                                    <select id="fLinea" class="form-select form-select-sm"><option value="">Todas</option></select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Marca</label>
                                    <select id="fMarca" class="form-select form-select-sm"><option value="">Todas</option></select>
                                </div>
                            </div>
                            <!-- Zona de controles de vista (qué columnas se ven) — cluster a la derecha -->
                            <div class="d-flex align-items-end gap-2 ms-auto" id="vistaControls">
                                <button type="button" id="fLimpiar" class="btn btn-sm btn-soft-secondary" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Borrar filtros"><i class="mdi mdi-filter-remove-outline"></i></button>
                                <!-- el JS (initComplete) inyecta acá el dropdown "Columnas" -->
                                <div id="colvisHost" class="d-inline-block"></div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-0">
                    <div class="card-body p-0">
                        <div class="table-responsive" id="listadoregistros">
                            <table id="tbllistado"  class="table table-sm table-striped table-centered mb-0  nowrap w-100">
                                <thead>
                                <?php
                                for ($i = 0; $i < count($cabecera); $i++)
                                    echo "<th style='text-align: center;'>" . $cabecera[$i] . "</th>";
                                ?>
                                </thead>
                                <tbody style="text-align: center;">
                                </tbody>
                                <tfoot>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                        <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Editar Articulo</span></h6>
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Descripcion(*)</label>
                                                <input class="form-control" type="hidden" name="idarticulo" id="idarticulo">
                                                <input class="form-control" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Nombre" required>
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Rubro</label>
                                                <input class="form-control" type="text" name="rubro" id="rubro" maxlength="100" placeholder="Rubro" required>
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Linea</label>                                                
                                                <input class="form-control" type="text"  name="linea" id="linea" required>
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Calibre</label>
                                                <input class="form-control" type="text" name="calibre" id="calibre" maxlength="256" placeholder="Calibre">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="imagen" class="form-label">Imagen del producto</label>
                                                <input class="form-control" type="file" name="imagen" id="imagen" accept="image/jpeg">
                                                <input type="hidden" name="imagenactual" id="imagenactual">
                                                <small class="text-muted d-block mt-1"><i class="mdi mdi-information-outline"></i> Formato: JPG · Tamaño máximo: <strong>2 MB</strong></small>
                                                <div class="mt-2">
                                                    <div style="position:relative;display:inline-block;">
                                                        <img src="" alt="" id="imagenmuestra" onerror="this.onerror=null;this.src='../files/articulos/camara.jpg';this.style.display='';var b=document.getElementById('btnQuitarImagen');if(b)b.style.display='none';" style="width:96px;height:96px;border-radius:12px;object-fit:cover;display:none;border:2px solid #e2e0f0;box-shadow:0 2px 6px rgba(0,0,0,.08);">
                                                        <button type="button" id="btnQuitarImagen" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Quitar" style="display:none;position:absolute;top:-2px;right:-2px;width:26px;height:26px;padding:0;border-radius:50%;background:#fa5c7c;color:#fff;border:2px solid #fff;font-size:15px;line-height:20px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.3);cursor:pointer;"><i class="mdi mdi-close"></i></button>
                                                    </div>
                                                </div>
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label class="form-label">Código <span class="text-muted fw-normal">(de barras)</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-barcode"></i></span>
                                                    <input class="form-control" type="text" name="codigo" id="codigo" placeholder="Código del producto" required>
                                                    <button class="btn btn-outline-primary" type="button" id="btnGenerar" onclick="generarbarcode()">
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="genSpinner" role="status" aria-hidden="true"></span>
                                                        <i class="mdi mdi-barcode-scan me-1" id="genIcon"></i>Generar
                                                    </button>
                                                </div>
                                                <div id="barcodeWrap" class="barcode-box mt-2" style="display:none;">
                                                    <div id="print"><svg id="barcode"></svg></div>
                                                    <button class="btn btn-sm btn-soft-secondary mt-2" type="button" onclick="imprimir()"><i class="mdi mdi-printer me-1"></i>Imprimir</button>
                                                </div>
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

        <!-- Modal: imagen ampliada del artículo -->
        <div class="modal fade" id="modalImgArticulo" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0" id="modalImgTitulo"></h5>
                            <small class="text-muted" id="modalImgSub"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalImgFoto" src="" alt="" class="img-fluid rounded" onerror="this.onerror=null;this.src='../files/articulos/camara.jpg';" style="max-height:72vh;">
                    </div>
                </div>
            </div>
        </div>
    <?php }
     else {
        require 'noacceso.php';
    }
    require 'footerv1.php'
    ?>
    <script>
        document.title = "Atiende | Articulos";
    </script>
    <script src="../public/js/JsBarcode.all.min.js"></script>
    <script src="../public/js/jquery.PrintArea.js"></script>
    <script src="scripts/articulo.js?t=<?php echo time(); ?>"></script>

    <?php
}

ob_end_flush();
?>