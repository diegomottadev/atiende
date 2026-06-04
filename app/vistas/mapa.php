<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    include_once("../config/Connection.php");
    require 'headerv1.php';
    if ($_SESSION['ventas'] == 1) {?>
    <style>
/* ── mapa hero ── */
#map {
    width: 100%;
    height: calc(100vh - 240px);
    min-height: 480px;
    border-radius: 0 8px 8px 0;
}
/* ── panel lateral ── */
#panel-lateral {
    height: calc(100vh - 240px);
    min-height: 480px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e9ecef;
}
#filtro-panel {
    padding: 12px 14px 10px;
    border-bottom: 1px solid #e9ecef;
    background: #fafafa;
    border-radius: 8px 0 0 0;
    flex-shrink: 0;
}
#lista-panel {
    flex: 1;
    overflow-y: auto;
    padding: 8px 10px;
}
/* ── marcadores ── */
.marker {
    background-image: url('images/mapbox-icon.jpg');
    background-size: cover;
    width: 44px; height: 44px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
/* ── popup ── */
.mapboxgl-popup { max-width: 250px !important; }
.mapboxgl-popup-content {
    text-align: center;
    font-family: 'Segoe UI', 'Open Sans', sans-serif;
    border-radius: 12px;
    padding: 14px 18px 16px;
    box-shadow: 0 8px 24px rgba(108,99,255,.20);
}
.mapboxgl-popup-close-button { font-size: 1.15rem; color: #bbb; padding: 2px 7px; }
.mapboxgl-popup-close-button:hover { color: #6c63ff; background: transparent; }
.map-popup .mp-name  { font-size:.95rem; font-weight:800; color:#333; text-transform:capitalize; line-height:1.2; margin-bottom:2px; }
.map-popup .mp-cod   { font-size:.7rem; color:#9a9a9a; font-weight:600; margin-bottom:7px; }
.map-popup .mp-ramo  { display:inline-block; font-size:.68rem; color:#6c63ff; background:rgba(108,99,255,.12); border-radius:10px; padding:2px 10px; font-weight:700; margin-bottom:9px; text-transform:capitalize; }
.map-popup .mp-total-label { display:block; font-size:.6rem; text-transform:uppercase; letter-spacing:.6px; color:#9a9a9a; font-weight:700; margin-bottom:1px; }
.map-popup .mp-total { font-size:1.3rem; font-weight:800; color:#3cd4ac; letter-spacing:-.5px; line-height:1.1; }
/* ── tabla ── */
table td:nth-child(3) { text-align: right; }
table.ventas td:nth-child(3)::before { content: "$ "; }
.total-row td { background-color:#6650EA; color:#fff; font-weight:600; }
/* ── select2 ── */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--multiple {
    min-height:29px; max-height:29px; overflow:hidden;
    padding:2px 4px; border:1px solid #ced4da; border-radius:4px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice { margin-top:3px; font-size:10px; padding:0 5px; }
.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field { margin-top:3px; font-size:12px; }
/* ── label filtro ── */
.filtro-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#9a9a9a; margin-bottom:2px; display:block; }
</style>

    <!-- breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item" style="margin-top:-0.7em">
                            <a href="javascript:void(0);">
                                <img src="../public/img/logo30x30.png" alt="" class="icono-ruta-ClubPedido"> Atiende
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Mapa de Ventas</li>
                    </ol>
                </div>
                <div class="float-start mt-2 mb-n2"><h4 class="page-title">Mapa de Ventas</h4></div>
            </div>
        </div>
    </div>

    <!-- contenido principal: panel + mapa en una sola card sin padding interior -->
    <div class="row">
        <div class="col-12">
            <div class="card sombra-panel p-0 overflow-hidden" style="border-radius:10px;">
                <div class="row g-0">

                    <!-- ── Panel lateral ── -->
                    <div class="col-lg-4 col-md-5" id="panel-lateral">

                        <!-- Filtros -->
                        <div id="filtro-panel">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span style="font-size:.75rem; font-weight:700; color:#6650EA; letter-spacing:.3px;">
                                    <i class="uil uil-filter me-1"></i> Filtros
                                </span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" id="customRadio3" name="optradio" value="ventas" checked>
                                    <label class="btn btn-outline-primary" style="font-size:.7rem; padding:2px 10px;" for="customRadio3">Ventas</label>
                                    <input type="radio" class="btn-check" id="customRadio4" name="optradio" value="reclamos">
                                    <label class="btn btn-outline-primary" style="font-size:.7rem; padding:2px 10px;" for="customRadio4">Reclamos</label>
                                </div>
                            </div>
                            <form action="" name="formulario" id="formulario" method="POST">
                                <div class="row g-1">
                                    <div class="col-6">
                                        <label class="filtro-label" for="fecha_inicio">Desde</label>
                                        <input class="form-control form-control-sm" type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo date("Y-m-d"); ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="filtro-label" for="fecha_fin">Hasta</label>
                                        <input class="form-control form-control-sm" type="date" name="fecha_fin" id="fecha_fin" value="<?php echo date("Y-m-d"); ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="filtro-label" for="vendedor">Vendedor</label>
                                        <select class="form-control form-control-sm select2 select2-multiple" data-toggle="select2" name="vendedor" id="vendedor" multiple="multiple" data-placeholder="Todos">
                                            <?php
                                            $request = Connection::runQuery("SELECT vendedor FROM `clientes` GROUP BY `vendedor`");
                                            if ($request)
                                                while ($row = mysqli_fetch_array($request))
                                                    echo "<option value='" . $row["vendedor"] . "'>" . $row["vendedor"] . "</option>";
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="filtro-label" for="ramo">Ramo</label>
                                        <select class="form-control form-control-sm select2 select2-multiple" data-toggle="select2" name="ramo" id="ramo" multiple="multiple" data-placeholder="Todos">
                                            <?php
                                            $request = Connection::runQuery("SELECT ramo FROM `clientes` GROUP BY `ramo`");
                                            if ($request)
                                                while ($row = mysqli_fetch_array($request))
                                                    if ($row["ramo"] != '')
                                                        echo "<option value='" . $row["ramo"] . "'>" . $row["ramo"] . "</option>";
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="filtro-label" for="zona">Zona</label>
                                        <select class="form-control form-control-sm select2 select2-multiple" data-toggle="select2" name="zona" id="zona" multiple="multiple" data-placeholder="Todas">
                                            <?php
                                            $request = Connection::runQuery("SELECT zona FROM `clientes` GROUP BY `zona`");
                                            if ($request)
                                                while ($row = mysqli_fetch_array($request))
                                                    if ($row["zona"] != '')
                                                        echo "<option value='" . $row["zona"] . "'>" . $row["zona"] . "</option>";
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6 repartidor">
                                        <label class="filtro-label" for="repartidor">Repartidor</label>
                                        <select class="form-control form-control-sm select2 select2-multiple" data-toggle="select2" name="repartidos" id="repartidor" multiple="multiple" data-placeholder="Todos">
                                            <?php
                                            $request = Connection::runQuery("SELECT id, nombre FROM repartidores");
                                            if ($request)
                                                while ($row = mysqli_fetch_array($request))
                                                    if ($row["id"] != '')
                                                        echo "<option value='" . $row["id"] . "'>" . $row["nombre"] . "</option>";
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex gap-1 mt-1">
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill sombra-logo flex-fill" onclick="mostrarMapa()">
                                            <i class="mdi mdi-magnify"></i> Buscar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light rounded-pill sombra-logo flex-fill" onclick="limpiarDatos()">
                                            <i class="mdi mdi-eraser-variant"></i> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Lista de resultados -->
                        <div id="lista-panel">
                            <div id="resultado">
                                <div id="tabla1">
                                    <table id="tbllistado" class="table table-sm table-striped table-centered mb-0 w-100 ventas">
                                        <thead>
                                            <th style="width:12%">Cod</th>
                                            <th>Razón social</th>
                                            <th style="width:20%">Total</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Total fijo al pie del panel -->
                        <div style="padding:8px 12px; border-top:1px solid #e9ecef; background:#6650EA; border-radius:0 0 0 8px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-size:.72rem; font-weight:700; color:rgba(255,255,255,.8); text-transform:uppercase; letter-spacing:.5px;">Total</span>
                                <span id="total" style="font-size:1.1rem; font-weight:800; color:#fff;"></span>
                            </div>
                        </div>

                    </div><!-- /panel-lateral -->

                    <!-- ── Mapa hero ── -->
                    <div class="col-lg-8 col-md-7 p-0">
                        <div id="map"></div>
                    </div>

                </div><!-- /row g-0 -->
            </div><!-- /card -->
        </div>
    </div>
    <?php 
    }else{
        require 'noacceso.php';
    }
    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Mapa de ventas";
    </script>
   
   <script src="scripts/mapa.js?t=<?php echo time(); ?>"></script>
<?php 
}
ob_end_flush();
?>