<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.html");
} else {

    require 'header.php';

    if ($_SESSION['escritorio'] == 1) {

        require_once "../modelos/Consultas.php";
        $consulta = new Consultas();

        if (!isset($_POST["fecha"])) {
            $_POST["fecha"] = date("Y") . "-" . date("m");
        }

        if (!isset($_POST["year"])) {
            $_POST["year"] =  date("Y");
        }

        if (!isset($_POST["fechaCurrent"])) {
            $fecha = new DateTime();
            //$fecha->sub(new DateInterval('P1D'));
            $_POST["fechaCurrent"] =  $fecha->format('Y-m-d' );
            //$_POST["fechaCurrent"] = date("Y") . "-" . date("m") . "-" . date("d");
        }

        ?>
        <link rel="stylesheet" href="../public/css/atiende.css">
        <style>
            .tagVenta {
                background: #f4f7fc;
            }
        </style>
        <div class="content-wrapper">
            <!-- Main content -->
            <section class="content">

                <!-- Default box -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Panel de control</h1>
                                <div class="box-tools pull-right">

                                </div>
                            </div>

                            <div class="panel-body" >
                                <form method="post" action="escritorio.php" id="formFecha" style="padding-bottom:4%;">
                                    <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                        <?php $years = range(2020, strftime("%Y", time())); ?>
                                        <select class="form-control" id="year" name="year">
                                            <option >-- Seleccione --</option>
                                            <?php foreach($years as $year) : ?>
                                                <option value="<?php echo $year; ?>"
                                                    <?php echo ($year == $_POST["year"])?'selected="selected"':'';?> >
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                    </div>
                                    <div class="form-group col-lg-7 col-md-7 col-xs-9">
                                        <input type="month" class="form-control" name="fecha" onchange="actualizar()"
                                               id="fecha" value="<?php echo $_POST["fecha"]; ?>">
                                    </div>
                                    <div class="form-group col-lg-2 col-md-2 col-xs-9">
                                        <input type="date" class="form-control " name="fechaCurrent" onchange="actualizar()"
                                               id="fechaCurrent" value="<?php echo $_POST["fechaCurrent"]; ?>">
                                    </div>
                                    <div class="form-group col-lg-1 col-md-1 col-xs-1">

                                        <button class="btn btn-success sombra" type="submit"><i class="fa fa-refresh"></i>

                                        </button>
                                    </div>
                                </form>

                                <?php
                                    $cliVentas = 0;
                                    $rsptav = $consulta->clientesReclamosMes($_POST["fecha"]);
                                    $regv = $rsptav->fetch_object();
                                    $cliReclamos = $regv->cantidad;

                                    $rsptav = $consulta->clientesVentasMes($_POST["fecha"]);

                                    while ($regv = $rsptav->fetch_object()) {
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
                                
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="panel panel-default tagVenta sombra">
                                        <div class="panel-body">
                                            <h5 class="colorTagFont"> <strong> Total operaciones</strong></h5>
                                            <h3><strong><?php echo $totalClientes; ?></strong></h3>
                                            <p>
                                            </p>
                                            <p>
                                            </p>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>

                                                    <td align="left">
                                                        Ventas: <span style="color:#00ACD7;" ><?php echo $cliVentas . " | " . $_venta . "%"; ?></span>
                                                    </td>
                                                    <td align="rigth">
                                                        Reclamos: <span style="color:#34A853;" ><?php echo $cliReclamos . " | " . $_reclamo . "%"; ?></span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                                <?php
                                    $rsptav = $consulta->totalventaMes($_POST["fecha"]);

                                    $regv = $rsptav->fetch_object();

                                    $totalv = $regv->total_venta;
                                ?>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="panel panel-default tagVenta sombra">
                                        <div class="panel-body">
                                            <h5 class="colorTagFont"> <strong> Total Ventas</strong></h5>
                                            <h3><strong>&#36;&nbsp;<?php echo  number_format(round($totalv), 0, "", "."); ?></strong></h3>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td>
                                                        &nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    $rspta = $consulta->totalreclamosMes($_POST["fecha"]);
                                    $i = 0;
                                    $reclamosTotal = 0;
                                    $r_Pendiente = 0;
                                    $r_Finalizado = 0;
                                    $r_Analisis = 0;
                                    while ($reg = $rspta->fetch_object()) {

                                        if ($reg->estado == "En analisis")
                                            $r_Analisis = $reg->cantidad;
                                        if ($reg->estado == "Pendiente")
                                            $r_Pendiente = $reg->cantidad;
                                        if ($reg->estado == "Finalizado")
                                            $r_Finalizado = $reg->cantidad;

                                        $reclamosTotal += $reg->cantidad;
                                        $i++;
                                    }
                                    if ($reclamosTotal != NULL || $reclamosTotal != 0) {
                                        $f = round(($r_Finalizado / $reclamosTotal) * 100);
                                        $a = round(($r_Analisis / $reclamosTotal) * 100);
                                        $p = round(($r_Pendiente / $reclamosTotal) * 100);
                                    } else {
                                        $f = 0;
                                        $a = 0;
                                        $p = 0;
                                    }
                                ?>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="panel panel-default tagVenta sombra">
                                        <div class="panel-body">
                                            <h5 class="colorTagFont"> <strong> Reclamos total</strong></h5>
                                            <h3><strong><?php echo $reclamosTotal; ?></strong></h3>



                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td  align="center">
                                                        F <span style="color:#34A853;" ><?php echo  $r_Finalizado . " | " . $f . "%"; ?></span>

                                                    </td>
                                                    <td align="center">
                                                        A <span style="color:#FBBC05;" ><?php echo   $r_Analisis . " | " . $a . "%"; ?></span>
                                                    </td>
                                                    <td align="center">
                                                        P <span style="color:#EA4335;" ><?php echo   $r_Pendiente . " | " . $p . "%"; ?></span>
                                                    </td>
                                                </tr>
                                            </table>

                                        </div>

                                    </div>
                                </div>
                                <?php
                                    $rsptad = $consulta->totalventaDia($_POST["fechaCurrent"]);

                                    $regd = $rsptad->fetch_object();

                                    $totald = $regd->total_venta;
                                ?>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="panel panel-default tagVenta sombra">
                                        <div class="panel-body">
                                            <h5 class="colorTagFont"> <strong> Ventas del dia</strong></h5>
                                            <h3><strong>&#36;&nbsp;<?php echo number_format(round($totald), 0, "", "."); ?></strong></h3>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td>
                                                        &nbsp;
                                                    </td>
                                                </tr>
                                            </table>

                                        </div>

                                    </div>
                                </div>
                                <?php

                                    $totalconsultasMesPorEstado = $consulta->totalconsultasMesEstado($_POST["fecha"]);

                                    $c_Pendiente = 0;
                                    $c_Finalizado = 0;
                                    $c_Analisis = 0;
                                    $i = 0;
                                    while ($reg = $totalconsultasMesPorEstado->fetch_object()) {

                                        if ($reg->estado == "En analisis")
                                            $c_Analisis = $reg->cantidad;
                                        if ($reg->estado == "Pendiente")
                                            $c_Pendiente = $reg->cantidad;
                                        if ($reg->estado == "Finalizado")
                                            $c_Finalizado = $reg->cantidad;
                                        $i++;
                                    }

                                    $totalconsultasMes = $consulta->totalconsultasMes($_POST["fecha"]);

                                    $consultaTotal = 0;

                                    while ($reg = $totalconsultasMes->fetch_object()) {

                                        $consultaTotal += $reg->cantidad;
                                    }

                                    if ($consultaTotal != NULL || $consultaTotal != 0) {
                                        $f_consulta = round(($c_Finalizado / $consultaTotal) * 100);
                                        $a_consulta = round(($c_Analisis / $consultaTotal) * 100);
                                        $p_consulta = round(($c_Pendiente / $consultaTotal) * 100);
                                    } else {
                                        $f_consulta = 0;
                                        $a_consulta = 0;
                                        $p_consulta = 0;
                                    }
                                ?>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="panel panel-default tagVenta sombra">
                                        <div class="panel-body">
                                            <h5 class="colorTagFont"> <strong> Consultas</strong></h5>

                                            <h3><strong><?php echo number_format(round($consultaTotal), 0, "", "."); ?></strong></h3>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td  align="center">
                                                        F <span style="color:#34A853;" ><?php echo  $c_Finalizado . " | " . $f_consulta . "%"; ?></span>

                                                    </td>
                                                    <td align="center">
                                                        A <span style="color:#FBBC05;" ><?php echo   $c_Analisis . " | " . $a_consulta . "%"; ?></span>
                                                    </td>
                                                    <td align="center">
                                                        P <span style="color:#EA4335;" ><?php echo   $c_Pendiente . " | " . $p_consulta . "%"; ?></span>
                                                    </td>
                                                </tr>
                                            </table>

                                        </div>

                                    </div>
                                </div>
                                <?php
                                    $solicitudesPorMes = $consulta->solicitudesPorMes($_POST["fecha"]);

                                    $solicitudesTotal = 0;
                                    while ($reg = $solicitudesPorMes->fetch_object()) {

                                        $solicitudesTotal += $reg->cantidad;
                                    }

                                ?>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="panel panel-default tagVenta sombra">
                                        <div class="panel-body">
                                            <h5 class="colorTagFont"><strong> Solicitudes de clientes</strong></h5>

                                            <h3><strong><?php echo number_format(round($solicitudesTotal), 0, "", "."); ?> </strong></h3>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td>
                                                        &nbsp;
                                                    </td>
                                                </tr>
                                            </table>

                                        </div>

                                    </div>
                                </div>
                                <div class="panel-body">
                                    <?php
                                        $compras10 = $consulta->comprasultimos_10dias($_POST["fecha"]);
                                        $fechasc = '';
                                        $totalesc = '';
                                        while ($regfechac = $compras10->fetch_object()) {
                                            $fechasc = $fechasc . '"' . $regfechac->fecha . '",';

                                            $totalesc = $totalesc . strval(round($regfechac->total)) . ',';

                                        }
                                    ?>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12" style="height: 50% !important;">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución ventas diarias (mes)
                                            </div>
                                            <div class="box-body">
                                                <canvas id="compras" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        //Evolución reclamos diario
                                        $fechasc = substr($fechasc, 0, -1);
                                        $totalesc = substr($totalesc, 0, -1);
                                        $ventas12 = $consulta->ventasultimos_12meses($_POST["fecha"]);
                                        $fechasv = '';
                                        $totalesv = '';
                                        while ($regfechav = $ventas12->fetch_object()) {
                                            $fechasv = $fechasv . '"' . $regfechav->fecha . '",';
                                            $totalesv = $totalesv . $regfechav->total . ',';
                                        }
                                        //quitamos la ultima coma
                                        $fechasv = substr($fechasv, 0, -1);
                                        $totalesv = substr($totalesv, 0, -1);
                                    ?>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución reclamos diarios (mes)
                                            </div>
                                            <div class="box-body">
                                                <canvas id="ventas" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <?php
                                        // evolucion de ventas por  mes en $
                                        $ventas_mes = $consulta->ventas_x_Mes($_POST["year"]);
                                        $fechasMes = '';
                                        $totalesMes = '';
                                        while ($regfechac = $ventas_mes->fetch_object()) {
                                            $fechasMes .= '"' . $regfechac->fecha . '",';
                                            $totalesMes .= strval(number_format(round($regfechac->total), 0, "", "")) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución de ventas por mes
                                            </div>
                                            <div class="box-body">
                                                <canvas id="ventas_mes" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        // Evolución de pedidos por mes
                                        $pedidos_mes = $consulta->pedidos_x_Mes($_POST["year"]);
                                        $fechaPedidoMes = '';
                                        $pedidosMes = '';
                                        while ($regfechac = $pedidos_mes->fetch_object()) {
                                            $fechaPedidoMes = $fechaPedidoMes . '"' . $regfechac->fecha . '",';

                                            $pedidosMes = $pedidosMes . strval(number_format(round($regfechac->total), 0, "", ".")) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución de pedidos por mes
                                            </div>
                                            <div class="box-body">
                                                <canvas id="pedios_mes" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    // Evolución ticket por mes
                                        $ticket_mes = $consulta->ticket_promedio_Mes($_POST["year"]);
                                        $fechaTicketMes = '';
                                        $TickeMes = '';
                                        while ($regfechac = $ticket_mes->fetch_object()) {
                                            $fechaTicketMes = $fechaTicketMes . '"' . $regfechac->fecha . '",';

                                            $totalesMes = $totalesMes . strval(number_format(round($regfechac->total), 0, "", "")) . ',';
                                            $TickeMes = $TickeMes . strval(number_format(round($regfechac->total), 0, "", "")) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución ticket por mes
                                            </div>
                                            <div class="box-body">
                                                <canvas id="ticket_mes" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <?php
                                        $reclamos_mes = $consulta->reclamosMes($_POST["year"]);
                                        $fechaReclamotMes = '';
                                        $reclamoMes = '';
                                        while ($regfechac = $reclamos_mes->fetch_object()) {
                                            $fechaReclamotMes = $fechaReclamotMes . '"' . $regfechac->fecha . '",';
                                            //$reclamoMes=$reclamoMes.$regfechac->total.',';
                                            $reclamoMes = $reclamoMes . strval(number_format(round($regfechac->total), 0, "", ".")) . ',';
                                        }
                                    ?>

                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución de reclamos por mes
                                            </div>
                                            <div class="box-body">
                                                <canvas id="reclamo_mes" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        $reclamos_motivo = $consulta->reclamos_x_Motivos($_POST["year"]);
                                        $LreclamoMotivo = '';
                                        $reclamoMotivo = '';
                                        while ($regfechac = $reclamos_motivo->fetch_object()) {
                                            $LreclamoMotivo = $LreclamoMotivo . '"' . $regfechac->motivo . '",';
                                            $reclamoMotivo = $reclamoMotivo . strval(number_format($regfechac->cantidad, 0, "", ".")) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Cantidad de reclamos por motivo
                                            </div>
                                            <div class="box-body">
                                                <canvas id="reclamo_motivo" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        $reclamos_sector = $consulta->reclamos_x_Sector($_POST["year"]);
                                        $LreclamoSector = '';
                                        $reclamoSector = '';
                                        while ($regfechac = $reclamos_sector->fetch_object()) {
                                            $LreclamoSector = $LreclamoSector . '"' . $regfechac->sector . '",';
                                            $reclamoSector = $reclamoSector . strval(number_format($regfechac->cantidad, 0, "", ".")) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Cantidad de reclamos por sector
                                            </div>
                                            <div class="box-body">
                                                <canvas id="reclamo_sector" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php

                                        $solicitudesPorMesLine = $consulta->solicitudesPorMesBar($_POST['year']);
                                        $fechasSolicitudes = '';
                                        $totalesSolicitudes = '';
                                        $fechasSolicitudes = substr($fechasSolicitudes, 0, -1);
                                        $totalesSolicitudes = substr($totalesSolicitudes, 0, -1);
                                        while ($regfechac = $solicitudesPorMesLine->fetch_object()) {
                                            $fechasSolicitudes = $fechasSolicitudes . '"' . $regfechac->fecha . '",';
                                            $totalesSolicitudes = $totalesSolicitudes . strval(round($regfechac->total)) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución solicitudes diarios por mes
                                            </div>
                                            <div class="box-body">
                                                <canvas id="solicitudes" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        $consultasPorMesLine = $consulta->consultasPorMesLine($_POST["year"]);
                                        $fechasConsultas = '';
                                        $totalesConsultas = '';
                                        while ($regfechac = $consultasPorMesLine->fetch_object()) {
                                            $fechasConsultas = $fechasConsultas . '"' . $regfechac->fecha . '",';
                                            $totalesConsultas = $totalesConsultas . strval(round($regfechac->total)) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Evolución de consultas por mes
                                            </div>
                                            <div class="box-body">
                                                <canvas id="consulta_mes" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        $consultas_sector = $consulta->consultasPorSector($_POST['year']);
                                        $LconsultaSector = '';
                                        $consultaSector = '';
                                        while ($regfechac = $consultas_sector->fetch_object()) {
                                            $LconsultaSector = $LconsultaSector . '"' . $regfechac->sector . '",';
                                            $consultaSector = $consultaSector . strval(number_format($regfechac->cantidad, 0, "", ".")) . ',';
                                        }
                                    ?>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <div class="box box-primary-atiende">
                                            <div class="box-header with-border">
                                                Cantidad de consultas por sector
                                            </div>
                                            <div class="box-body">
                                                <canvas id="consulta_sector" width="400" height="300"></canvas>
                                            </div>
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
        require 'noacceso.php';
    }

    require 'footer.php';
    ?>
    <script>
        document.title = "Atiende | Panel de control";
    </script>
    <script src="../public/js/Chart.bundle.min.js"></script>
    <script src="../public/js/Chart.min.js"></script>
    <script>


        var ctx = document.getElementById("compras").getContext('2d');

        var compras = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo $fechasc ?>],
                datasets: [{
                    label: '# Evolución ventas diarias $',
                    data: [<?php echo $totalesc;  ?>],
                    backgroundColor: [
                        'rgba(255,255,255,0.5)'
                    ],
                    borderColor: [
                        '#3cd4ac'
                    ],
                    borderWidth: 5
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
        var ctx = document.getElementById("ventas").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo $fechasv ?>],
                datasets: [{
                    label: '# Evolución reclamos diarios',
                    data: [<?php echo $totalesv ?>],
                    backgroundColor: [
                        'rgba(255,255,255,0.5)'
                    ],
                    borderColor: [
                        '#f6aabf'
                    ],
                    borderWidth: 5
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("ventas_mes").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo $fechasMes ?>],
                datasets: [{
                    label: '# Evolución de Ventas por mes $',
                    data: [<?php echo $totalesMes ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });


        var ctx = document.getElementById("pedios_mes").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo $fechaPedidoMes ?>],
                datasets: [{
                    label: '# Evolución de Pedidos por mes',
                    data: [<?php echo $pedidosMes ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("ticket_mes").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo $fechaTicketMes ?>],
                datasets: [{
                    label: '# Evolución Ticket por Mes $ ',
                    data: [<?php echo $TickeMes ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("reclamo_mes").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo $fechaReclamotMes ?>],
                datasets: [{
                    label: '# Evolución de reclamos por mes',
                    data: [<?php echo $reclamoMes ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("reclamo_motivo").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: [<?php echo $LreclamoMotivo ?>],
                datasets: [{
                    label: '# Reclamos por motivo',
                    data: [<?php echo $reclamoMotivo ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("reclamo_sector").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: [<?php echo $LreclamoSector ?>],
                datasets: [{
                    label: '# Reclamos por sector',
                    data: [<?php echo $reclamoSector ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("consulta_mes").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo $fechasConsultas ?>],
                datasets: [{
                    label: '# Evolución de consultas por mes',
                    data: [<?php echo $totalesConsultas ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        var ctx = document.getElementById("consulta_sector").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: [<?php echo $LconsultaSector ?>],
                datasets: [{
                    label: '# Consulta por sector',
                    data: [<?php echo $consultaSector ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
        var ctx = document.getElementById("solicitudes").getContext('2d');
        var ventas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo $fechasSolicitudes ?>],
                datasets: [{
                    label: '# Evolución solicitudes diarios por mes',
                    data: [<?php echo $totalesSolicitudes ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    </script>
    <?php
}

ob_end_flush();
?>

