<?php
    ob_start();
    session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
}else{
    require 'headerv1.php';
    if ($_SESSION['consultas']==1){ ?>
    <style>
#tbllistado thead th, table.dataTable thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
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
                            <li class="breadcrumb-item active"> Mis Consultas </li>
                        </ol>                   
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
                    <div class="card-body pb-2">
                        <!-- Filtros (server-side: buscan en TODO el dataset, no solo la página) -->
                        <div id="filtrosConsulta" class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" id="fBuscar" class="form-control" placeholder="Buscar por N° consulta, teléfono, cliente, motivo...">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Estado</label>
                                <select id="fEstado" class="form-select form-select-sm"><option value="">Todos</option></select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;">Área</label>
                                <select id="fArea" class="form-select form-select-sm"><option value="">Todas</option></select>
                            </div>
                            <div class="col-6 col-md-1">
                                <button type="button" id="fLimpiar" class="btn btn-sm btn-soft-secondary w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Borrar filtros"><i class="mdi mdi-filter-remove-outline"></i></button>
                            </div>
                        </div>
                    </div>
                    <hr class="my-0">
                    <div class="card-body p-0">
                            <div class="table-responsive" id="tablaConsultas"> <!-- dt-responsive -->
<!--                                table dt-responsive nowrap w-100 dataTable no-footer dtr-inline-->
<!--                                <table id="tblConsultas" class="table table-sm table-striped table-centered mb-0  nowrap w-100">-->
                                <table id="tblConsultas" class="table nowrap w-100 dataTable no-footer dtr-inline">
                                    <thead >
                                        <th style="min-width: 80px;">Editar</th>
                                        <th style="min-width: 80px;">Estado</th>
                                        <th style="min-width: 80px;">N° Con</th>
                                        <th style="min-width: 80px;">Fecha</th>
                                        <th style="min-width: 80px;">Telefono</th>
                                        <th>Cod.Cliente</th>
                                        <th>Cliente</th>
                                        <th>Motivo</th>
                                        <th>Area</th>
<!--                                        <th style="min-width: 110px;">Detalle</th>-->
<!--                                        <th style="min-width: 110px;">Resolucion</th>-->
                                        <th>Fecha Res.</th>
                                        <th>#</th>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formRespuestasConsultas">
                                <!--<div class="ribbon ribbon-info float-start mt-n2 mb-1">
                                    <i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Datos del Motivo</span>
                                </div>-->

                                <div class="bs-callout bs-callout-violeta sombra pt-3">
                                    <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> Datos de la Consulta</h6>
                                    <!--<h5><strong>Datos del Reclamo</strong></h5>-->
                                    <ul class="list-unstyled mt-n0" style="padding-left: 3em;">
                                        <li><strong>Nick: </strong> <span id="nick"></span></li>
                                        <li><strong>Fecha y Hora: </strong> <span id="fechaHora"></span></li>
                                        <li><strong>Cliente: </strong><span id="cliente"></span></li>
                                        <li><strong>Telefono: </strong><span id="telefono"></span></li>
                                        <li><strong>Motivo: </strong><span id="motivo"></span></li>
                                        <li><strong>Detalle del Motivo: </strong> <span id="detalleMotivo"></span></li>
                                    </ul>
                                </div>
                                <form action="" name="formRespuestaConsulta" id="formRespuestaConsulta" method="POST">
                                    <input class="form-control" type="hidden" name="idconsulta" id="idconsulta">       
                                    <div class="row mt-2" >
                                        <div class="form-group col-lg-12">
                                            <label  class="form-label"><strong>Resolucion</strong></label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                                            
                                            <div class="form-check form-check-inline form-radio-info">
                                                <input type="radio" name="estado" id="estadoa" value="En analisis"  class="form-check-input">
                                                <label class="form-check-label" for="estadoa">En Analisis</label>
                                            </div>
                                            <div class="form-check form-check-inline form-radio-info">
                                                <input type="radio"  name="estado" id="estadob" value="Finalizado"  class="form-check-input">
                                                <label class="form-check-label" for="estadob">Finalizado</label>
                                            </div>
                                            <textarea class="form-control" id="resolucion" name="resolucion" rows="4"></textarea>
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
                                        <table id="tblMensajeConsulta" class="table table-striped dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
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
    if ($_SESSION['consultas']==10) {
        ?>
        <style>
        </style>
        <div class="content-wrapper" >
            <section class="content"  >
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Consultas </h1>
                                <div class="box-tools pull-right">
                                    <a class="btn btn-success pull-right" id="btnExportar" href="#" onClick="exportarConsultas()" > <i class="fa fa-file-excel-o"></i> Exportar</a>
                                    <button class="btn btn-default" id="btnCancel" onclick="cancelarform()" type="button"><i class="fa fa-arrow-circle-left"></i> Volver</button>

                                </div>
                            </div>
                            <div class="panel-body table-responsive" id="tablaConsultas">
                                <table id="tblConsultas" class="table dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
                                    <thead>
                                    <th  >Codigo</th>
                                    <th style="min-width: 110px;">Fecha</th>
                                    <th>Telefono</th>
                                    <th>Cod Cliente</th>
                                    <th>Cliente</th>
                                    <th style="min-width: 180px;">Motivo</th>
                                    <th>Area</th>
                                    <th style="min-width: 110px;">Detalle</th>
                                    <th style="min-width: 110px;">Resolucion</th>
                                    <th style="min-width: 110px;">Fecha Res.</th>
                                    <th>Estado</th>
                                    <th>Editar</th>
                                    <th>#</th>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <style>
                            
                            #tbllistado{
                                }
                                #tbllistado tbody tr td .tbdato{
                                    overflow-wrap: break-word;
                                }
                                .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th{
                                     vertical-align: middle;
                                 }

                            </style>
                            <div class="panel-body table-responsive"  id="formRespuestasConsultas">
                                <form action="" name="formRespuestaConsulta" id="formRespuestaConsulta" method="POST">
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Fecha</label>
                                        <input class="form-control" type="hidden" name="idconsulta" id="idconsulta">
                                        <input class="form-control" type="text" name="fecha" id="fecha"  placeholder="Fecha"  disabled >
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Cliente</label>
                                        <input class="form-control" type="text" name="cliente" id="cliente" placeholder="Fecha" disabled>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Nick</label>
                                        <input class="form-control" type="text" name="nick" id="nick"  placeholder="Nick" disabled>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Telefono</label>
                                        <input class="form-control" type="text" name="telefono" id="telefono"  placeholder="Telefono" disabled>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Motivo</label>
                                        <input class="form-control" type="text" name="mitivo" id="motivo"  placeholder="motivo" disabled>
                                    </div>
                                    <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                        <label for="">Detalle</label>
                                        <textarea class="form-control"  id="detalle" name="detalle" rows="4" disabled  ></textarea>
                                    </div>
                                    <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                        <label for="">Resolucion</label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <label class="radio-inline">
                                            <input type="radio" name="estado" id="estado"  value="En analisis"  >En Analisis </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="estado" id="estado" value="Finalizado" >Finalizado</label>
                                        <textarea class="form-control"  id="resolucion" name="resolucion" rows="4" ></textarea>
                                    </div>
                                    <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <button class="btn btn-success pull-right" type="submit" id="btnGuardar"><i class="fa fa-save"></i> Guardar</button>
                                    </div>
                                </form>
                                <hr />
                                <table id="tblMensajeConsulta" class="table table-striped dt-responsive nowrap w-100 dataTable no-footer dtr-inline">
                                    <thead style="background-color:#A9D0F5">
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Mensaje</th>
                                    <th>Estado</th>
                                    </thead>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php
    }else{
         //require 'noacceso.php';
    }
    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Consultas";
    </script>
    <script src="scripts/consulta.js?t=<?php echo time(); ?>"></script>
    <?php
}

ob_end_flush();
?>
