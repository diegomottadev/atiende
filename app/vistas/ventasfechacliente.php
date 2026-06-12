<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require 'headerv1.php';
    if ($_SESSION['ventas'] == 1) {?>
    <style>
    .vfc-stat {
        display: inline-flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-left: 3px solid #727cf5;
        border-radius: 6px;
        padding: 7px 16px;
        min-width: 140px;
    }
    .vfc-stat-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #98a6ad;
        font-weight: 700;
        margin-bottom: 1px;
    }
    .vfc-stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #313a46;
        line-height: 1.2;
    }
    #tbllistado thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }
    /* igualar alto de select2 single con form-control-sm (~31px) */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        height: 31px !important;
        line-height: 29px !important;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: .875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 29px !important;
        padding-left: 8px;
        font-size: .875rem;
        color: #6c757d;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 29px !important;
    }
    </style>
        <!-- title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item" style="margin-top:-.7em">
                                <a href="javascript:void(0);">
                                    <img src="../public/img/logo30x30.png" alt="" class="icono-ruta-ClubPedido">
                                    Atiende
                                </a>
                            </li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Ventas</a></li>
                            <li class="breadcrumb-item active">Consulta de Ventas</li>
                        </ol>
                </div>
            </div>
        </div>
        <!-- botones fuera del card -->
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">
                <div class="text-sm-end d-flex justify-content-end gap-1">
                    <button class="btn btn-sm btn-success rounded-pill sombra-logo" id="btnExportar" onclick="aExcel()">
                        <i class="mdi mdi-file-excel-outline"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
        <!-- /title -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel" style="border-top:3px solid #727cf5;">

                    <!-- filtros -->
                    <div class="card-body pb-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-4">
                                <label for="fBuscar" class="form-label">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" id="fBuscar" class="form-control" placeholder="Cliente, producto, pedido… (mín. 3 caracteres)">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_inicio" id="fecha_inicio" value="<?php echo date("Y-m-d"); ?>">
                            </div>
                            <div class="col-lg-2">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_fin" id="fecha_fin" value="<?php echo date("Y-m-d"); ?>">
                            </div>
                            <div class="col-lg-3">
                                <label for="idcliente" class="form-label">Cliente</label>
                                <select class="form-control form-control-sm select2" data-toggle="select2" name="idcliente" id="idcliente">
                                    <option value="">— Todos los clientes —</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-1">
                                <button type="button" id="fLimpiar" class="btn btn-sm btn-soft-secondary w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Borrar filtros"><i class="mdi mdi-filter-remove-outline"></i></button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    <!-- tabla -->
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-center gap-3 py-2 border-bottom flex-wrap">
                            <div class="vfc-stat">
                                <span class="vfc-stat-label">Pedidos</span>
                                <span class="vfc-stat-value" id="pedidos">—</span>
                            </div>
                            <div class="vfc-stat">
                                <span class="vfc-stat-label">Importe Total</span>
                                <span class="vfc-stat-value" id="importe">—</span>
                            </div>
                        </div>
                        <div class="table-responsive mt-0">
                            <table id="tbllistado" class="table table-striped table-centered mb-0 nowrap w-100">
                                <thead>
                                    <th>Fecha</th>
                                    <th>Cod</th>
                                    <th>Cliente</th>
                                    <th>Ramo</th>
                                    <th>Localidad</th>
                                    <th>Ped. N°</th>
                                    <th>Cód. Producto</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>SubTotal</th>
                                    <th>Lista</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?php
    }else if ($_SESSION['ventas'] == 10) {

        ?>
        <div class="content-wrapper">
            <section class="content">

                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Consulta de ventas</h1>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-success pull-right" onClick="aExcel()" id="btnExportar"> <i class="fa fa-file-excel-o"></i>
                                        Exportar
                                    </button>
                                </div>
                            </div>
                            <div class="box-body with-border">
                                <div class="row">
                                    <div class="form-group col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <label>Fecha Inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio"
                                               value="<?php echo date("Y-m-d"); ?>">
                                    </div>
                                    <div class="form-group col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <label>Fecha Fin</label>
                                        <input type="date" class="form-control" name="fecha_fin" id="fecha_fin"
                                               value="<?php echo date("Y-m-d"); ?>">
                                    </div>
                                    <div class="form-group  col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                        <label>Cliente</label>
                                        <select name="idcliente" id="idcliente" class="form-control selectpicker"
                                                data-live-search="true" required>
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-1 col-md-1 col-sm-1 col-xs-12">
                                        <label>&nbsp;</label> <br/>
                                        <button class="btn btn-primary pull-right" onclick="listar()"><i class="fa fa-search"></i>
                                            Buscar
                                        </button>

                                    </div>
                                </div>
                                <div class="row">

                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Cantidad de pedidos: </label>
                                        <input class="form-control" type="text" name="pedidos" id="pedidos"
                                               disabled="disabled">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Importe total : </label>
                                        <input class="form-control" type="text" name="importe" id="importe"
                                               disabled="disabled">
                                    </div>
                                </div>
                            </div>
                            <table id="tbllistado"
                                   class="table table-striped table-bordered table-condensed table-hover">
                                <!--  `id`, `clienteId`, `fecha`, `producto`, `descripcion`, `cantidad`, `precio`, `descuento`, `pedidoid`,-->
                                <thead>
                                <th>Fecha</th>
                                <th>Cod </th>
                                <th>Cliente</th>
                                <th>Ramo</th>
                                <th>Localidad</th>
                                <th>Ped. N°</th>
                                <th>Cod. Producto</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>SubTotal</th>
                                <th>Lista</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php
    } else {
        require 'noacceso.php';
    }

    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Consulta de ventas";
    </script>
    <script src="scripts/ventasfechacliente.js?t=<?php echo time(); ?>"></script>
    <?php
}

ob_end_flush();
?>

