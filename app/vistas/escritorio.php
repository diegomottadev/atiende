<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require 'headerv1.php';
    if ($_SESSION['escritorio'] == 1) {
        require_once "../modelos/Consultas.php";
        $consulta = new Consultas();
        $mesAnio = '';$inicio = false;
        $listMeses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        if (!isset($_POST["fecha"])) {            
            /* mes - anio */
            $inicio = true;
            $_POST["fecha"] = date("Y") . "-" . date("m");
            $mesInt =  intval(date('m'))-1;
            $mesAnio = $listMeses[$mesInt] . " " . date("Y");  
        }else{
            $mesAnio = $_POST["fecha"];
            $aux =  explode(" ", $_POST["fecha"] );            
            $resultado = array_search($aux[0], $listMeses);
            $_POST["fecha"] = date("Y") . "-" . ($resultado+1);            
        }
        if (!isset($_POST["year"])) {
            $_POST["year"] =  date("Y");
        }
        if (!isset($_POST["fechaCurrent"])) {
            /*$fecha = new DateTime();
            $_POST["fechaCurrent"] =  $fecha->format('d-m-Y' );*/
            $_POST["fechaCurrent"] = date("Y")."-".date("m")."-".date("d");
            $fechaActual= date("d")."-".date("m")."-".date("Y");
        }else{
            $fechaArray = explode("-",$_POST["fechaCurrent"]);
            $fechaActual= $fechaArray[2].'-'.$fechaArray[1].'-'.$fechaArray[0];
        }
        ?>
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
                            <li class="breadcrumb-item active"> Panel de Control </li>
                        </ol>
                </div>
            </div>
        </div>
       

        <!-- filtros -->
        <div class="row">
            <div class="col-12 mt-n3 mb-3">
                <div class="page-title-box">
                    <div class="page-title-right mt-0">
                        <form class="d-flex align-items-end" method="post" action="escritorio.php" >

                            <div class="d-flex flex-column ms-2">
                                <label for="filtroYear" class="text-uppercase text-muted fw-bold mb-1" style="font-size:.66rem; letter-spacing:.5px;">Año</label>
                                <div class="input-group sombra-panel" id="year">
                                    <input type="text" id="filtroYear" name="year" class="form-control" data-provide="datepicker" data-date-min-view-mode="2" data-date-format="yyyy" data-date-container="#year" data-date-autoclose="true" data-date-end-date="yyyy" value="<?php echo $_POST["year"]?>">
                                        <span class="input-group-text bg-info border-info text-white">
                                            <i class="mdi mdi-calendar-range font-13"></i>
                                        </span>
                                </div>
                            </div>
                            <div class="d-flex flex-column ms-2">
                                <label for="filtroMes" class="text-uppercase text-muted fw-bold mb-1" style="font-size:.66rem; letter-spacing:.5px;">Mes</label>
                                <div class="input-group datepicker-translated sombra-panel" id="fecha">
                                    <input type="text" id="filtroMes" name="fecha" class="form-control fecha" data-provide="datepicker" data-date-format="MM yyyy" data-date-min-view-mode="1" data-date-container="#fecha" data-date-autoclose="true"  data-date-language="es"  data-date-end-date="0d" data-date-start-date="Enero 2020" value="<?php echo $mesAnio;?>">
                                        <span class="input-group-text bg-info border-info text-white">
                                            <i class="mdi mdi-calendar-range font-13"></i>
                                        </span>
                                </div>
                            </div>
                            <div class="d-flex flex-column ms-2">
                                <label for="filtroDia" class="text-uppercase text-muted fw-bold mb-1" style="font-size:.66rem; letter-spacing:.5px;">Día</label>
                                <div class="input-group sombra-panel" id="fechaCurrent">
                                    <input type="text" id="filtroDia" name="fechaCurrent" class="form-control fechaCurrent" data-provide="datepicker" data-date-format="dd-mm-yyyy" data-date-container="#fechaCurrent" data-date-autoclose="true"  data-date-start-date="01-01-2020" data-date-language="es" data-date-end-date="0d" value="<?php echo $fechaActual;?>">
                                    <span class="input-group-text bg-info border-info text-white">
                                        <i class="mdi mdi-calendar-range font-13"></i>
                                    </span>
                                </div>
                            </div>
                            <button class="btn btn-info ms-2 sombra-panel" type="submit" title="Actualizar">
                                <i class="mdi mdi-autorenew"></i>
                            </button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- fin filtros -->
        <?php
            $cliVentas = 0;
            $rsptav = $consulta->clientesReclamosMes($_POST["fecha"]);
            $regv = $rsptav ? $rsptav->fetch_object() : null;
            $cliReclamos = $regv ? $regv->cantidad : 0;

            $rsptav = $consulta->clientesVentasMes($_POST["fecha"]);

            while ($rsptav && ($regv = $rsptav->fetch_object())) {
                $cliVentas++;
            }
            $totalClientes = $cliVentas + $cliReclamos;
            if ($totalClientes != NULL || $totalClientes != 0) {
                $_venta = round(($cliVentas / $totalClientes) * 100);
                $_reclamo = round(($cliReclamos / $totalClientes) * 100);

            } else {
                $_venta = 0;
                $_reclamo = 0;
            }
        ?>
        <!-- ===== KPI CARDS ===== -->
        <div class="row g-3 mb-3">

            <div class="col-xl-2 col-lg-4 col-sm-6">
                <div class="card sombra-panel border-start border-4 border-info h-100 mb-0">
                    <div class="card-body py-3">
                        <i class='mdi mdi-chart-areaspline-variant float-end text-muted fs-4'></i>
                        <p class="text-uppercase mb-1 text-muted fw-bold" style="font-size:.68rem">Total Operaciones</p>
                        <h3 class="my-1"><?php echo $totalClientes; ?></h3>
                        <p class="mb-0 text-muted" style="font-size:.74rem">
                            <span class="text-info">Ventas: <?php echo $cliVentas . " (" . $_venta . "%)"; ?></span><br>
                            <span class="text-success">Reclamos: <?php echo $cliReclamos . " (" . $_reclamo . "%)"; ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <?php
                $rsptav = $consulta->totalventaMes($_POST["fecha"]);
                $regv   = $rsptav ? $rsptav->fetch_object() : null;
                $totalv = $regv ? $regv->total_venta : 0;
            ?>
            <div class="col-xl-2 col-lg-4 col-sm-6">
                <div class="card sombra-panel border-start border-4 border-primary h-100 mb-0">
                    <div class="card-body py-3">
                        <i class='mdi mdi-cart-variant float-end text-muted fs-4'></i>
                        <p class="text-uppercase mb-1 text-muted fw-bold" style="font-size:.68rem">Ventas del mes</p>
                        <h3 class="my-1">$<?php echo number_format(round($totalv), 0, "", "."); ?></h3>
                        <p class="mb-0 text-muted" style="font-size:.74rem"><?php echo $mesAnio; ?></p>
                    </div>
                </div>
            </div>

            <?php
                $rspta        = $consulta->totalreclamosMes($_POST["fecha"]);
                $reclamosTotal = 0; $r_Pendiente = 0; $r_Finalizado = 0; $r_Analisis = 0; $i = 0;
                while ($rspta && ($reg = $rspta->fetch_object())) {
                    if ($reg->estado == "En analisis") $r_Analisis  = $reg->cantidad;
                    if ($reg->estado == "Pendiente")   $r_Pendiente = $reg->cantidad;
                    if ($reg->estado == "Finalizado")  $r_Finalizado = $reg->cantidad;
                    $reclamosTotal += $reg->cantidad; $i++;
                }
                $f = $reclamosTotal ? round(($r_Finalizado / $reclamosTotal) * 100) : 0;
                $a = $reclamosTotal ? round(($r_Analisis   / $reclamosTotal) * 100) : 0;
                $p = $reclamosTotal ? round(($r_Pendiente  / $reclamosTotal) * 100) : 0;
            ?>
            <div class="col-xl-2 col-lg-4 col-sm-6">
                <div class="card sombra-panel border-start border-4 border-danger h-100 mb-0">
                    <div class="card-body py-3">
                        <i class='mdi mdi-chat-remove float-end text-muted fs-4'></i>
                        <p class="text-uppercase mb-1 text-muted fw-bold" style="font-size:.68rem">Reclamos del mes</p>
                        <h3 class="my-1"><?php echo $reclamosTotal; ?></h3>
                        <div class="mt-2" style="font-size:.72rem;line-height:1.7">
                            <div class="d-flex align-items-center text-nowrap">
                                <span class="d-inline-block rounded-circle bg-success me-1 align-middle" style="width:8px;height:8px"></span>
                                <span class="text-muted">Finalizados</span>
                                <span class="ms-auto fw-semibold text-dark"><?php echo $r_Finalizado; ?> <span class="text-muted fw-normal">(<?php echo $f; ?>%)</span></span>
                            </div>
                            <div class="d-flex align-items-center text-nowrap">
                                <span class="d-inline-block rounded-circle bg-warning me-1 align-middle" style="width:8px;height:8px"></span>
                                <span class="text-muted">En análisis</span>
                                <span class="ms-auto fw-semibold text-dark"><?php echo $r_Analisis; ?> <span class="text-muted fw-normal">(<?php echo $a; ?>%)</span></span>
                            </div>
                            <div class="d-flex align-items-center text-nowrap">
                                <span class="d-inline-block rounded-circle bg-danger me-1 align-middle" style="width:8px;height:8px"></span>
                                <span class="text-muted">Pendientes</span>
                                <span class="ms-auto fw-semibold text-dark"><?php echo $r_Pendiente; ?> <span class="text-muted fw-normal">(<?php echo $p; ?>%)</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                $rsptad = $consulta->totalventaDia($_POST["fechaCurrent"]);
                $regd   = $rsptad ? $rsptad->fetch_object() : null;
                $totald = $regd ? $regd->total_venta : 0;
            ?>
            <div class="col-xl-2 col-lg-4 col-sm-6">
                <div class="card sombra-panel border-start border-4 border-success h-100 mb-0">
                    <div class="card-body py-3">
                        <i class='mdi mdi-cart-plus float-end text-muted fs-4'></i>
                        <p class="text-uppercase mb-1 text-muted fw-bold" style="font-size:.68rem">Ventas del día</p>
                        <h3 class="my-1">$<?php echo number_format(round($totald), 0, "", "."); ?></h3>
                        <p class="mb-0 text-muted" style="font-size:.74rem"><?php echo $fechaActual; ?></p>
                    </div>
                </div>
            </div>

            <?php
                $totalconsultasMesPorEstado = $consulta->totalconsultasMesEstado($_POST["fecha"]);
                $c_Pendiente = 0; $c_Finalizado = 0; $c_Analisis = 0; $i = 0;
                while ($totalconsultasMesPorEstado && ($reg = $totalconsultasMesPorEstado->fetch_object())) {
                    if ($reg->estado == "En analisis") $c_Analisis  = $reg->cantidad;
                    if ($reg->estado == "Pendiente")   $c_Pendiente = $reg->cantidad;
                    if ($reg->estado == "Finalizado")  $c_Finalizado = $reg->cantidad;
                    $i++;
                }
                $totalconsultasMes = $consulta->totalconsultasMes($_POST["fecha"]);
                $consultaTotal = 0;
                while ($totalconsultasMes && ($reg = $totalconsultasMes->fetch_object())) $consultaTotal += $reg->cantidad;
                $f_consulta = $consultaTotal ? round(($c_Finalizado / $consultaTotal) * 100) : 0;
                $a_consulta = $consultaTotal ? round(($c_Analisis   / $consultaTotal) * 100) : 0;
                $p_consulta = $consultaTotal ? round(($c_Pendiente  / $consultaTotal) * 100) : 0;
            ?>
            <div class="col-xl-2 col-lg-4 col-sm-6">
                <div class="card sombra-panel border-start border-4 border-warning h-100 mb-0">
                    <div class="card-body py-3">
                        <i class='mdi mdi-chat-question float-end text-muted fs-4'></i>
                        <p class="text-uppercase mb-1 text-muted fw-bold" style="font-size:.68rem">Consultas del mes</p>
                        <h3 class="my-1"><?php echo number_format($consultaTotal, 0, "", "."); ?></h3>
                        <div class="mt-2" style="font-size:.72rem;line-height:1.7">
                            <div class="d-flex align-items-center text-nowrap">
                                <span class="d-inline-block rounded-circle bg-success me-1 align-middle" style="width:8px;height:8px"></span>
                                <span class="text-muted">Finalizadas</span>
                                <span class="ms-auto fw-semibold text-dark"><?php echo $c_Finalizado; ?> <span class="text-muted fw-normal">(<?php echo $f_consulta; ?>%)</span></span>
                            </div>
                            <div class="d-flex align-items-center text-nowrap">
                                <span class="d-inline-block rounded-circle bg-warning me-1 align-middle" style="width:8px;height:8px"></span>
                                <span class="text-muted">En análisis</span>
                                <span class="ms-auto fw-semibold text-dark"><?php echo $c_Analisis; ?> <span class="text-muted fw-normal">(<?php echo $a_consulta; ?>%)</span></span>
                            </div>
                            <div class="d-flex align-items-center text-nowrap">
                                <span class="d-inline-block rounded-circle bg-danger me-1 align-middle" style="width:8px;height:8px"></span>
                                <span class="text-muted">Pendientes</span>
                                <span class="ms-auto fw-semibold text-dark"><?php echo $c_Pendiente; ?> <span class="text-muted fw-normal">(<?php echo $p_consulta; ?>%)</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                $solicitudesPorMes  = $consulta->solicitudesPorMes($_POST["fecha"]);
                $solicitudesTotal = 0;
                while ($solicitudesPorMes && ($reg = $solicitudesPorMes->fetch_object())) $solicitudesTotal += $reg->cantidad;
            ?>
            <div class="col-xl-2 col-lg-4 col-sm-6">
                <div class="card sombra-panel border-start border-4 border-secondary h-100 mb-0">
                    <div class="card-body py-3">
                        <i class='mdi mdi-badge-account-horizontal-outline float-end text-muted fs-4'></i>
                        <p class="text-uppercase mb-1 text-muted fw-bold" style="font-size:.68rem">Solicitudes del mes</p>
                        <h3 class="my-1"><?php echo number_format($solicitudesTotal, 0, "", "."); ?></h3>
                        <p class="mb-0 text-muted" style="font-size:.74rem">&nbsp;</p>
                    </div>
                </div>
            </div>

        </div><!-- /.row KPIs -->

        <!-- ===== GRÁFICOS DIARIOS ===== -->
        <?php
            $compras10 = $consulta->comprasultimos_10dias($_POST["fecha"]);
            $fechasc = ''; $totalesc = '';
            while ($compras10 && ($regfechac = $compras10->fetch_object())) {
                $fechasc  .= '"' . $regfechac->fecha . '",';
                $totalesc .= "'" . $regfechac->total . "',";
            }
            $fechasc  = substr($fechasc,  0, -1);
            $totalesc = substr($totalesc, 0, -1);
            $ventas12 = $consulta->ventasultimos_12meses($_POST["fecha"]);
            $fechasv = ''; $totalesv = '';
            while ($ventas12 && ($regfechav = $ventas12->fetch_object())) {
                $fechasv  .= '"' . $regfechav->fecha . '",';
                $totalesv .= $regfechav->total . ',';
            }
            $fechasv  = substr($fechasv,  0, -1);
            $totalesv = substr($totalesv, 0, -1);
        ?>
        <div class="row mb-3">
            <div class="col-xl-6">
                <div class="card sombra-panel h-100">
                    <div class="card-body">
                        <div id="area-ventasDiarias"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card sombra-panel h-100">
                    <div class="card-body">
                        <div id="area-reclamosDiarios"></div>
                    </div>
                </div>
            </div>
        </div><!-- /.row gráficos diarios -->

       <div class="row">
            <?php 
                // evolucion de ventas por  mes en $
                $ventas_mes = $consulta->ventas_x_Mes($_POST["year"]);
                $fechasMes = '';
                $totalesMes = '';$t = null;
                while ($ventas_mes && ($regfechac = $ventas_mes->fetch_object())) {
                    $fechasMes .= "'". $regfechac->fecha . "',";
                    //$totalesMes .= strval(number_format(round($regfechac->total), 0, "", "")) . ',';
                    $totalesMes .= "'".$regfechac->total."',";                   
                }                
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 m-n1 pb-0"> 
                        <h5 class="card-title">Evolución de ventas por mes  (<?php echo $_POST['year'];?>)</h5>
                        <div id="bar-ventasMensuales"  ></div>
                    </div>
                    <!-- end card body-->
                </div>
            </div>
            <?php
                // Evolución de pedidos por mes
                $pedidos_mes = $consulta->pedidos_x_Mes($_POST["year"]);
                $fechaPedidoMes = '';
                $pedidosMes = '';
                while ($pedidos_mes && ($regfechac = $pedidos_mes->fetch_object())) {
                    $fechaPedidoMes = $fechaPedidoMes . '"' . $regfechac->fecha . '",';

                    $pedidosMes = $pedidosMes . strval(number_format(round($regfechac->total), 0, "", ".")) . ',';
                }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3"> 
                        <h5 class="card-title">Evolución de pedidos por mes  (<?php echo $_POST['year'];?>)</h5>
                        <div id="bar-pedidosMensuales"  ></div>
                    </div>
                    <!-- end card body-->
                </div>
            </div>
            <?php
            // Evolución ticket por mes
                $ticket_mes = $consulta->ticket_promedio_Mes($_POST["year"]);
                $fechaTicketMes = '';
                $TickeMes = '';
                while ($ticket_mes && ($regfechac = $ticket_mes->fetch_object())) {
                    $fechaTicketMes = $fechaTicketMes . '"' . $regfechac->fecha . '",';
                   // $totalesMes = $totalesMes . strval(number_format(round($regfechac->total), 0, "", "")) . ',';
                    //$TickeMes = $TickeMes . strval(number_format(round($regfechac->total), 0, "", "")) . ',';
                    $TickeMes .= "'".$regfechac->total."', ";
                }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3">
                        <h5 class="card-title">Evolución de tickets por mes  (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-ticketsMensuales"  ></div>
                    </div>
                </div>
            </div>
        
        <?php
            $reclamos_mes = $consulta->reclamosMes($_POST["year"]);
            $fechaReclamotMes = '';
            $reclamoMes = '';
            while ($reclamos_mes && ($regfechac = $reclamos_mes->fetch_object())) {
                $fechaReclamotMes = $fechaReclamotMes . '"' . $regfechac->fecha . '",';
                //$reclamoMes=$reclamoMes.$regfechac->total.',';
                $reclamoMes = $reclamoMes . strval(number_format(round($regfechac->total), 0, "", ".")) . ',';
            }
        ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3"> 
                        <h5 class="card-title">Evolución de Reclamos por mes  (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-reclamosMensuales"  ></div>
                    </div>
                </div>
            </div>
            <?php
                $reclamos_motivo = $consulta->reclamos_x_Motivos($_POST["year"]);
                $LreclamoMotivo = '';
                $reclamoMotivo = '';
                while ($reclamos_motivo && ($regfechac = $reclamos_motivo->fetch_object())) {
                    $LreclamoMotivo = $LreclamoMotivo . '"' . $regfechac->motivo . '",';
                    $reclamoMotivo = $reclamoMotivo . strval(number_format($regfechac->cantidad, 0, "", ".")) . ',';
                }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3" style="padding-left: 4rem"> 
                        <h5 class="card-title mb-3">Cantidad de Reclamos por motivo (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-reclamosxMotivos"  style="margin-left: -3.25rem !important; min-height: 365px;" class="mt-n2"></div>
                    </div>
                </div>
            </div>
            <?php
                $reclamos_sector = $consulta->reclamos_x_Sector($_POST["year"]);
                $LreclamoSector = '';
                $reclamoSector = '';
                while ($reclamos_sector && ($regfechac = $reclamos_sector->fetch_object())) {
                    $LreclamoSector = $LreclamoSector . '"' . $regfechac->sector . '",';
                    $reclamoSector = $reclamoSector . strval(number_format($regfechac->cantidad, 0, "", ".")) . ',';
                }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3 "> 
                    <h5 class="card-title">Cantidad de Reclamos por Sector (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-reclamosxSector"  ></div>
                    </div>
                </div>
            </div>
        
        <?php
            $solicitudesPorMesLine = $consulta->solicitudesPorMesBar($_POST['year']);
            $fechasSolicitudes = '';
            $totalesSolicitudes = '';
            $fechasSolicitudes = substr($fechasSolicitudes, 0, -1);
            $totalesSolicitudes = substr($totalesSolicitudes, 0, -1);
            while ($solicitudesPorMesLine && ($regfechac = $solicitudesPorMesLine->fetch_object())) {
                $fechasSolicitudes = $fechasSolicitudes . '"' . $regfechac->fecha . '",';
                $totalesSolicitudes = $totalesSolicitudes . strval(round($regfechac->total)) . ',';
            }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3"> 
                        <h5 class="card-title">Evolución solicitudes diarios por mes (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-solicitudesMensuales"  ></div>
                    </div>
                </div>
            </div>
            <?php
                $consultasPorMesLine = $consulta->consultasPorMesLine($_POST["year"]);
                $fechasConsultas = '';
                $totalesConsultas = '';
                while ($consultasPorMesLine && ($regfechac = $consultasPorMesLine->fetch_object())) {
                    $fechasConsultas = $fechasConsultas . '"' . $regfechac->fecha . '",';
                    $totalesConsultas = $totalesConsultas . strval(round($regfechac->total)) . ',';
                }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3"> 
                        <h5 class="card-title">Evolución de consultas por mes (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-consultasMensuales"  ></div>
                    </div>
                </div>
            </div>
            <?php
                $consultas_sector = $consulta->consultasPorSector($_POST['year']);
                $LconsultaSector = '';
                $consultaSector = '';
                while ($consultas_sector && ($regfechac = $consultas_sector->fetch_object())) {
                    $LconsultaSector = $LconsultaSector . '"' . $regfechac->sector . '",';
                    $consultaSector = $consultaSector . strval(number_format($regfechac->cantidad, 0, "", ".")) . ',';
                }
            ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card sombra-panel">
                    <div class="card-body mt-n1 mb-n3"> 
                    <h5 class="card-title">Cantidad de consultas por sector (<?php echo $_POST['year'];?>)</h5> 
                        <div id="bar-consultasxSector"  ></div>
                    </div>
                </div>
            </div>
        </div>

        
    <?php
    }else{
        require 'noacceso.php';
    }
    require 'footerv1.php';?>
    <script src="../public/assets/js/vendor/apexcharts.min.js"></script>
    <script src="scripts/escritorio.js?t=<?php echo time(); ?>"></script>   

    <script type="text/javascript">        
        document.title = "Atiende | Panel de control";
        /* Ventas Diarias */
        var ctx = document.querySelector("#area-ventasDiarias");
        var ventasDiarias = new ApexCharts(ctx, {
            title: { 
                text: 'Evolución ventas diarias (<?php echo $mesAnio;?>)', align: 'left'
            },
            chart: {
                type: 'area',
                zoom: { enabled: false },
                height: 210,
                dropShadow: {
                    enabled: true,
                    color: '#000',
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 0.2
                }, 
            },
            colors : ['#00E396'],
            series: [{
                name: 'Venta Diaria',
                data: [<?php echo $totalesc;?>]
            }],
            stroke: {
                //curve: 'straight'
                curve: 'smooth'
            },
            xaxis: {
                title: { text: 'Dias del mes de <?php  echo $mesAnio;?>'},
                labels: {
                    formatter: function (value) {
                        return value ;
                    }
                }, /* dias del mes */
                categories: [<?php  echo $fechasc ?>]
            },            
            tooltip: {
                y: {
                    formatter: function (val) {
                    return "$ " + val ;
                    }
                }
            },
            markers: { 
                size: [7, 10] //es el punto union entre los ejes x e y
            },
            grid: { //es para ver las lineas de la grafica
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5
                },
            },
            dataLabels: {//para q no se vea el label con el valor correspondiente al eje x, en la posicion y
                enabled: false
                },
        });
        ventasDiarias.render();
        
        
        /* Evolucion de reclamos diarios */
        var ctx1 = document.querySelector("#area-reclamosDiarios");
        var reclamosDiarios = new ApexCharts(ctx1, {
            title: { 
                text: 'Evolución de reclamos diarios (<?php echo $mesAnio;?>)', align: 'left'
            },
            chart: {
                type: 'area',
                zoom: { enabled: false },
                height: 210,                
                dropShadow: {
                    enabled: true,
                    color: '#000',
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 0.2
                },    
            },
            series: [{
                name: 'Reclamos Diarios',
                data: [<?php echo $totalesv;?>]
            }],
            xaxis: {
                title: { text: 'Dias del mes de <?php  echo $mesAnio;?>'},
                labels: {
                    formatter: function (value) {
                        return value ;
                    }
                }, /* dias del mes */
                categories: [<?php  echo $fechasv ?>]
            },
            yaxis: {
                //title: { text: 'Cantidad de Reclamos'},
                labels: {
                    formatter: function (value) {
                        return  value;
                    }
                },
            },
            markers: { 
                size: [7, 10] //es el punto union entre los ejes x e y
            },
            grid: { //es para ver las lineas de la grafica
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5
                },
            },
            dataLabels: {
                enabled: false
            },
        });
        reclamosDiarios.render();
        
        /* Evolucion de Ventas por mes */
        var ctx2 = document.querySelector("#bar-ventasMensuales");
        var ventasMensuales = new ApexCharts(ctx2, {            
            series: [{
                name: 'Ventas Mensuales',
                data: [<?php echo $totalesMes ?>]
                
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
           // colors: ['#33b2df', '#13d8aa', '#2b908f', '#90ee7e', '#33b2df', '#13d8aa', '#2b908f', '#90ee7e','#33b2df', '#13d8aa', '#2b908f', '#90ee7e'],
            /*colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e',
                    '#f48024', '#69d2e7','#00E396','#008FFB'],*/
            colors: ['#33b2df'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    //distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $fechasMes;?>]
            },        
            tooltip: {
                y: {
                    formatter: function (val) {
                    return "$ " + val ;
                    }
                }
            }
        });
        ventasMensuales.render();
      

        /* Evolucion de pedidos por mes */
        var ctx3 = document.querySelector("#bar-pedidosMensuales");
        var pedidosMensuales = new ApexCharts(ctx3, {
            series: [{
                name: 'Pedidos Mensuales',
                data: [<?php echo $pedidosMes ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
           // colors: ['#33b2df', '#13d8aa', '#2b908f', '#90ee7e', '#33b2df', '#13d8aa', '#2b908f', '#90ee7e','#33b2df', '#13d8aa', '#2b908f', '#90ee7e'],
            colors: ['#00E396'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    //distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $fechaPedidoMes ?>],
            },        
            tooltip: {
                y: {
                    formatter: function (val) {
                    return  val ;
                    }
                }
            }
        });
        pedidosMensuales.render();

        /* Evolucion de tickets por Mes */
        var ctx4 = document.querySelector("#bar-ticketsMensuales");
        var ticketsMensuales = new ApexCharts(ctx4, {            
            series: [{
                name: 'Tickets Mensuales',
                data: [<?php echo $TickeMes ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
           // colors: ['#33b2df', '#13d8aa', '#2b908f', '#90ee7e', '#33b2df', '#13d8aa', '#2b908f', '#90ee7e','#33b2df', '#13d8aa', '#2b908f', '#90ee7e'],
            colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e',
                    '#f48024', '#69d2e7','#00E396','#008FFB'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    //distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $fechaTicketMes ?>],
            },        
            tooltip: {
                y: {
                    formatter: function (val) {
                    return "$ " + val ;
                    }
                }
            }
        });
        ticketsMensuales.render();

        /*  Evolucion de Reclamos por mes */
        var ctx5 = document.querySelector("#bar-reclamosMensuales");
        var reclamosMensuales = new ApexCharts(ctx5, {            
            series: [{
                name: 'Reclamos Mensuales',
                data: [<?php echo $reclamoMes ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
           // colors: ['#33b2df', '#13d8aa', '#2b908f', '#90ee7e', '#33b2df', '#13d8aa', '#2b908f', '#90ee7e','#33b2df', '#13d8aa', '#2b908f', '#90ee7e'],
            colors: ['#00E396'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    //distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $fechaReclamotMes ?>],
            },        
            tooltip: {
                y: {
                    formatter: function (val) {
                    return val ;
                    }
                }
            }
        });
        reclamosMensuales.render();


        /* Reclamos por motivo */
        var motivos = [<?php echo $LreclamoMotivo ?>];
        motivos.forEach(function(valor, indice, array) {
            if (valor.length > 21){ 
                var posEspacio = valor.indexOf(" ",20);
                if (posEspacio == 20){
                    array[indice] = [valor.slice(0,20),valor.slice(21)];
                }else{
                    /* var res = valor.slice(0,20).split("").reverse().join('').indexOf(" ", 0);
                    res = 20 - res  ;
                    array[indice] = [valor.slice(0,res),valor.slice(res)]; */
                    var arrayValor = valor.split("");
                    for (i = 20; i--; i>0){
                        if (arrayValor[i] == ' '){
                            array[indice] = [valor.slice(0,i),valor.slice(i+1)];
                            break;
                        }
                    }                    
                }
            }
        });
        var ctx6 = document.querySelector("#bar-reclamosxMotivos");
        var reclamosxMotivos = new ApexCharts(ctx6, {
            series: [{
                name: 'Cantidad de Reclamos',
                data: [<?php echo $reclamoMotivo ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
            colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e','#f48024', '#69d2e7'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    horizontal: true,
                    distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: motivos,            
            },
            /*yaxis: {
                labels: {
                    show: false
                }
            },*/
            tooltip: {
                y: {
                    formatter: function (val) {
                    return val ;
                    }
                }
            }
        });
        reclamosxMotivos.render();

        /* Reclamos por Sector */
        var ctx7 = document.querySelector("#bar-reclamosxSector");
        var reclamosxSector = new ApexCharts(ctx7, {
            series: [{
                name: 'Cantidad de Reclamos',
                data: [<?php echo $reclamoSector ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
            colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e','#f48024', '#69d2e7'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    horizontal: true,
                    distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $LreclamoSector?>],            
            },
            /*yaxis: {
                labels: {
                    show: false
                }
            },*/
            tooltip: {
                y: {
                    formatter: function (val) {
                    return val ;
                    }
                }
            }
        });
        reclamosxSector.render();

        /* Evolucion de Solicitudes diarias por mes */
        var ctx8 = document.querySelector("#bar-solicitudesMensuales");
        var solicitudesMensuales = new ApexCharts(ctx8, {
            series: [{
                name: 'Solicitudes Mensuales',
                data: [<?php echo $totalesSolicitudes ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
           // colors: ['#33b2df', '#13d8aa', '#2b908f', '#90ee7e', '#33b2df', '#13d8aa', '#2b908f', '#90ee7e','#33b2df', '#13d8aa', '#2b908f', '#90ee7e'],
           colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e',
                    '#f48024', '#69d2e7','#00E396','#008FFB'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    //distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $fechasSolicitudes ?>],
            },        
            tooltip: {
                y: {
                    formatter: function (val) {
                    return val ;
                    }
                }
            }
        });
        solicitudesMensuales.render();

        /* Evolucion de consulta por mes */
        var ctx9 = document.querySelector("#bar-consultasMensuales");
        var consultasMensuales = new ApexCharts(ctx9, {
            series: [{
                name: 'Consultas Mensuales',
                data: [<?php echo $totalesConsultas ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
           // colors: ['#33b2df', '#13d8aa', '#2b908f', '#90ee7e', '#33b2df', '#13d8aa', '#2b908f', '#90ee7e','#33b2df', '#13d8aa', '#2b908f', '#90ee7e'],
            colors: ['#00E396'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    //distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories:  [<?php echo $fechasConsultas ?>],
            },        
            tooltip: {
                y: {
                    formatter: function (val) {
                    return val ;
                    }
                }
            }
        });
        consultasMensuales.render();

        /* Cantidad de consultas por sector */
        var ctx10 = document.querySelector("#bar-consultasxSector");
        var reclamosxSector = new ApexCharts(ctx10, {
            series: [{
                name: 'Cantidad de Consultas',
                data: [<?php echo $consultaSector ?>],
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
            colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e','#f48024', '#69d2e7'],
            plotOptions: {                
                bar: {
                    borderRadius: 4,
                    columnWidth: '75%',
                    horizontal: true,
                    distributed: true,/* hace que vea las legendas  y cada barra se vea de un color */                    
                }
            },            
            legend: {show: false },
            dataLabels: { enabled: false},        
            xaxis: {
                categories: [<?php echo $LconsultaSector?>],            
            },
            /*yaxis: {
                labels: {
                    show: false
                }
            },*/
            tooltip: {
                y: {
                    formatter: function (val) {
                    return val ;
                    }
                }
            }
        });
        reclamosxSector.render();
        

    </script>
    
<?php    
}
ob_end_flush();
?>