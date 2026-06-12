<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {

    //require 'header.php';
    require 'headerv1.php';
    if ($_SESSION['reclamos'] == 1) {?>
        <style>
#tbllistado thead th, table.dataTable thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
/* Tarjeta de datos del reclamo (form de edición) */
.reclamo-info { background:#f8f9ff; border:1px solid #e7e9ff; border-left:4px solid #727cf5; border-radius:8px; }
.rc-lbl { display:block; font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#9a9a9a; margin-bottom:1px; }
.rc-val { display:block; font-size:.9rem; font-weight:500; color:#3b3b4f; word-break:break-word; min-height:1.1em; }
</style>
         <!-- start title y botones -->
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
                            <li class="breadcrumb-item active"> Mis Reclamos </li>
                        </ol>
                   <!---->
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n2">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-success rounded-pill pull-right sombra-logo" id="btnExportar" onclick="aExcel()">
                        <i class="mdi mdi-file-excel-outline me-1"></i> Exportar
                    </button>
                    <button class="btn btn-light rounded-pill sombra-logo" id="btnCancel" onclick="cancelarform()" type="button">
                            <i class="mdi mdi-arrow-left-circle me-1"></i> Volver
                    </button>      
                </div>
            </div>
        </div>
        <!-- end  title y botones -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel" style="border-top:3px solid #727cf5;">
                    <div class="card-body pb-2" id="filtrosReclamo">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4 col-lg-3">
                                <label class="form-label mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" id="fBuscar" class="form-control" placeholder="Buscar reclamo...">
                                </div>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Estado</label>
                                <select id="fEstado" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Área</label>
                                <select id="fArea" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Motivo</label>
                                <select id="fMotivo" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-auto">
                                <button type="button" id="fLimpiar" class="btn btn-sm btn-soft-secondary" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Borrar filtros">
                                    <i class="mdi mdi-filter-remove-outline me-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr class="my-0">
                    <div class="card-body p-0">
                        <div class="table-responsive" id="listadoregistros"> <!--table dt-responsive nowrap w-100 dataTable no-footer dtr-inline -->
                            <table id="tbllistado" class="table nowrap w-100 dataTable no-footer dtr-inline">
                                <thead >
                                    <th>Editar</th>
                                    <th>Estado</th>
                                    <th>N Rec</th>
                                    <th style="min-width: 110px;">Fecha</th>
                                    <th>Telefono</th>
                                    <th>Cod.Cliente</th>
                                    <th>Cliente</th>
                                    <th style="min-width: 180px;">Motivo</th>
                                    <th>Area</th>
<!--                                        <th style="min-width: 110px;">Detalle</th>-->
<!--                                        <th style="min-width: 110px;">Resolucion</th>-->
                                    <th style="min-width: 110px;">Fecha Res.</th>
                                    <th>#</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                                <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Datos</span></h6>

                                <div class="reclamo-info p-3 mb-3">
                                    <h6 class="text-muted mb-3" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-clipboard-text-outline me-1"></i> Datos del Reclamo</h6>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-4"><span class="rc-lbl">Nick</span><span class="rc-val" id="nick"></span></div>
                                        <div class="col-6 col-md-4"><span class="rc-lbl">Fecha y Hora</span><span class="rc-val" id="fechaHora"></span></div>
                                        <div class="col-6 col-md-4"><span class="rc-lbl">Cliente</span><span class="rc-val" id="cliente"></span></div>
                                        <div class="col-6 col-md-4"><span class="rc-lbl">Telefono</span><span class="rc-val" id="telefono"></span></div>
                                        <div class="col-6 col-md-4"><span class="rc-lbl">Motivo</span><span class="rc-val" id="motivo"></span></div>
                                        <div class="col-12"><span class="rc-lbl">Detalle del Motivo</span><span class="rc-val" id="detalleMotivo"></span></div>
                                    </div>
                                </div>
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <input class="form-control" type="hidden" name="canal" id="canal" value="0">
                                    <input class="form-control" type="hidden" name="idreclamo" id="idreclamo">        
                                    <div class="row mt-2" >
                                        <div class="form-group col-lg-12">
                                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                                <label class="form-label mb-0"><strong>Resolucion</strong></label>
                                                <div class="form-check form-check-inline form-radio-info mb-0">
                                                    <input type="radio" name="estado" id="estadoa" value="En analisis" class="form-check-input">
                                                    <label class="form-check-label" for="estadoa">En Analisis</label>
                                                </div>
                                                <div class="form-check form-check-inline form-radio-info mb-0">
                                                    <input type="radio" name="estado" id="estadob" value="Finalizado" class="form-check-input">
                                                    <label class="form-check-label" for="estadob">Finalizado</label>
                                                </div>
                                            </div>
                                            <textarea class="form-control" id="resolucion" name="resolucion" rows="4" placeholder="Escribí la resolución del reclamo..."></textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 text-sm-end"> 
                                            <br>                                           
                                            <button class="btn btn-success rounded-pill sombra-logo" type="submit" id="btnGuardar">
                                                <i class="mdi mdi-content-save-all"></i> Guardar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <br>
                                <div class="table-responsive" >
                                    <div class=" col-lg-12 col-md-12 col-xs-12">
                                        <table id="tbmensajes" class="table table-striped dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
                                            <thead style="background-color:#A9D0F5">
                                                <th>ID</th>
                                                <th>Tipo</th>
                                                <th style="min-width: 100px;" >Fecha</th>
                                                <th>Mensaje</th>
                                                <th>Respondido por</th>
                                                <th>Estado</th>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>
        </div>
    <?php }
    if ($_SESSION['reclamos'] == 10) {
        ?>
        <style>

        </style>
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Mis Reclamos </h1>
                                <div class="box-tools pull-right">
                                    <a class="btn btn-success" id="btnExportar" href="#" onClick="aExcel()"><i
                                                class="fa fa-file-excel-o"></i> Exportar</a>
                                    <button class="btn btn-default" id="btnCancel" onclick="cancelarform()"
                                            type="button"><i
                                                class="fa fa-arrow-circle-left"></i> Volver
                                    </button>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive" id="listadoregistros">
                                        <table id="tbllistado"  class="table dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
                                            <thead>
                                            <th style="min-width: 80px;">Editar</th>
                                            <th style="min-width: 80px;">Estado</th>
                                            <th style="min-width: 80px;">N° Rec</th>
                                            <th style="min-width: 80px;">Fecha</th>
                                            <th style="min-width: 80px;">Telefono</th>
                                            <th>Cod.Cliente</th>
                                            <th>Cliente</th>
                                            <th>Motivo</th>
                                            <th>Area</th>
<!--                                            <th >Detalle</th>-->
<!--                                            <th >Resolucion</th>-->
                                            <th >Fecha Res.</th>
                                            <th>#</th>
                                            </thead>
                                            <tbody>
                                            </tbody>

                                        </table>
                                </div>

                                 <style>
                                    #tbllistado tbody tr td .tbdato {
                                        overflow-wrap: break-word;
                                    }

                                    
                                    .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
                                        vertical-align: middle;
                                    }

                                </style>
                                <div class="table-responsive" id="formularioregistros">
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <input class="form-control" type="hidden" name="canal" id="canal" value="0">
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Fecha</label>
                                        <input class="form-control" type="hidden" name="idreclamo" id="idreclamo">
                                        <input class="form-control" type="text" name="fecha" id="fecha"
                                               placeholder="Fecha" disabled>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Cliente</label>
                                        <input class="form-control" type="text" name="cliente" id="cliente"
                                               placeholder="Fecha" disabled>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Nick</label>
                                        <input class="form-control" type="text" name="nick" id="nick" placeholder="Nick"
                                               disabled>
                                    </div>

                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Telefono</label>
                                        <input class="form-control" type="text" name="telefono" id="telefono"
                                               placeholder="Telefono" disabled>
                                    </div>

                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Motivo</label>
                                        <input class="form-control" type="text" name="mitivo" id="motivo"
                                               placeholder="motivo" disabled>
                                    </div>

                                    <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                        <label for="">Detalle</label>
                                        <textarea class="form-control" id="detalle" name="detalle" rows="4"
                                                  disabled></textarea>
                                    </div>
                                    <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                        <label for="">Resolucion</label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <label class="radio-inline">
                                            <input type="radio" name="estado" id="estado" value="En analisis">En
                                            Analisis </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="estado" id="estado" value="Finalizado">Finalizado</label>

                                        <textarea class="form-control" id="resolucion" name="resolucion"
                                                  rows="4"></textarea>
                                    </div>


                                    <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <button class="btn btn-success pull-right" type="submit" id="btnGuardar"><i
                                                    class="fa fa-envelope"></i> Enviar
                                        </button>


                                    </div>
                                </form>
                                <hr/>
                                <div class=" col-lg-12 col-md-12 col-xs-12">
                                    <table id="tbmensajes"
                                           class="table table-striped dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
                                        <thead style="background-color:#A9D0F5">
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th style="min-width: 100px;" >Fecha</th>
                                        <th>Mensaje</th>
                                        <th>Estado</th>
                                        </thead>

                                        </tbody>
                                    </table>
                                </div>
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
        document.title = "Atiende | Reclamos";
    </script>
    <script src="scripts/reclamo.js?t=<?php echo time(); ?>"></script>
    <?php
}

ob_end_flush();
?>
