<?php
if (strlen(session_id()) < 1)
    session_start();
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/global.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

<!--    <title>Atiende | Panel de control</title>-->
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.7 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@200;400&family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../public/css/font-awesome.min.css">

    <link rel="stylesheet" href="../public/css/AdminLTE.css">
    <link rel="stylesheet" href="../public/css/_all-skins.min.css">
    <link href="../public/img/logo_arriba.png" rel="shortcut icon" type="image/x-icon">
    <!-- Morris chart --><!-- Daterange picker -->
    <!-- DATATABLES-->
    <link rel="stylesheet" href="../public/datatables/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../public/datatables/buttons.dataTables.min.css">
    <link rel="stylesheet" href="../public/datatables/responsive.dataTables.min.css">

    <link rel="stylesheet" href="../public/css/bootstrap-select.min.css">

    <link href="../public/mapboxgl/mapbox-gl.css" rel="stylesheet" />
    <script src="../public/js/jquery.min.js"></script>

    <style>
        body {
            font-family:'Nunito Sans', sans-serif !important;
            font-size: 13px !important;
        }

        .btn-success {
            background-color:  #3cd4ac !important;
        }
        .bg-green{
            background-color:  #3cd4ac !important;
        }
        .label-success{
            background-color:  #3cd4ac !important;
        }
        .treeview-menu > li > a {
            font-size: 12px !important;
        }

        .badge2 {
            position: absolute;
            top: 1px;
            right: 0px;
            padding: 2px 2px;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            padding: 4px;
            background: #FD763A;
            border: 2px solid #ffffff;
            color: #ffffff;
            text-align: center;
            font: 12px Arial, sans-serif;
        }

        /*
        .skin-blue .main-header .navbar {
            background-color: #3c8d;
        }
        /*
        .skin-blue .main-header .logo {
            background-color: #0700DD;
            color: #fff;

        }
        .skin-blue .wrapper, .skin-blue .main-sidebar, .skin-blue .left-side {
            background-color:  #0700DD;
        }
        */
        .skin-blue .main-header .navbar {
            background-color: #FFFFFF;
        }

        .skin-blue .main-header .logo {
            background-color: #222D32;
            color: #fff;
            border-bottom: 0 solid transparent;
        }

        .skin-blue .main-header .navbar .nav > li > a {
            color: #98A6AD;
        }


        .center {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 50%;
            padding: 5px;
        ;
        }
        /* ===== Ancho UNIFICADO para TODAS las tablas de listado =====
           Igual que en articulo.php: la tabla ocupa el 100% del ancho de su
           contenedor, sin importar su contenido ni el id. */
        #tbllistado,
        #tbllistado.dataTable,
        table.dataTable {
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        .dataTables_wrapper,
        #tbllistado_wrapper,
        .table-responsive {
            width: 100% !important;
        }


    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<script>
    var update = function () {
        $.post("../ajax/notificacion.php?", function (r) {
            if (r[0].length > 0) {
                $("#notificacion").html(r[0].length);
                document.getElementById("href").href = "venta.php?pedidoid=" + r[0][0].pedidoid;
                document.getElementById('notificacion').hidden = false;
            } else {
                $("#notificacion").html("0");
                document.getElementById('notificacion').hidden = true;

            }

            if (r[1].length > 0) {
                $("#notificacion2").html(r[1].length);
                document.getElementById("href2").href = "reclamo.php";
                document.getElementById('notificacion2').hidden = false;
            } else {
                $("#notificacion2").html("0");
                document.getElementById('notificacion2').hidden = true;
            }
        });
    };

</script>

<!-- Variables globales  -->

<script type="text/javascript">
    const globalNombreEmpresa = '<?php echo DB_NAME; ?>';
    const globalUrl = '<?php echo tenantUrl(); ?>';
</script>
<script>
$(document).ajaxError(function(event, xhr) {
    if (xhr.status === 401) {
        window.location.href = '../vistas/login.php';
    }
});
</script>


<div class="wrapper">

    <header class="main-header">
        <!-- Logo -->
        <a href="escritorio.php" class="logo testhover">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini"><img style="padding: 1.5px;" class="img-responsive" src="../public/img/logoLateral.png" ></span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg"><img style="padding: 1.5px;" class="img-responsive" src="../public/img/logoLateral.png" ></span>
            <?php echo '<input type="hidden" id="empresa" value="' . explode("/", $_SERVER["REQUEST_URI"])[1] . '">'; ?>

        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only">NAVEGACIÓM</span>
            </a>

            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">

<!--                    <li class="dropdown user user-menu">-->
<!--                        <a href="#" id="href"> Pedidos-->
<!--                            <i class="fa fa-bell" aria-hidden="true"> </i>-->
<!--                            <span class="badge2" hidden id="notificacion">0</span>-->
<!---->
<!--                        </a>-->
<!---->
<!--                    </li>-->
<!--                    <li class="dropdown user user-menu">-->
<!--                        <a href="#" id="href2">Reclamos-->
<!--                            <i class="fa fa-bell" aria-hidden="true"> </i>-->
<!--                            <span class="badge2" hidden id="notificacion2">0</span>-->
<!---->
<!--                        </a>-->
<!---->
<!---->
<!--                    </li>-->

                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <?php
                            if (file_exists('../files/usuarios/'.$_SESSION['imagen'])) {
                                $img ='../files/usuarios/'.$_SESSION['imagen'];
                            } else {
                                $img = '../files/usuarios/user.png';
                            }
                            ?>
                            <img src="<?php echo $img; ?>" class="user-image"
                                 alt="User Image">
                            <span class="hidden-xs"><?php echo $_SESSION['nombre']; ?></span>
                        </a>

                        <ul class="dropdown-menu">
                            <!-- User image -->
                            <li class="user-header">
                                <?php
                                if (file_exists('../files/usuarios/'.$_SESSION['imagen'])) {
                                    $img ='../files/usuarios/'.$_SESSION['imagen'];
                                } else {
                                    $img = '../files/usuarios/user.png';
                                }
                                ?>
                                <img src="<?php echo $img; ?>" class="img-circle"
                                     alt="User Image">

                                <p>
                                    <?php echo $_SESSION['nombre']; ?>
                                    <small><?php echo date("d-m-Y"); ?></small>
                                </p>
                            </li>
                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <div class="pull-left">
                                    <!--<a href="#" class="btn btn-default btn-flat">Perfil</a>-->
                                </div>
                                <div class="pull-right">
                                    <a href="../ajax/usuario.php?op=salir" class="btn btn-default btn-flat">Salir</a>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <!-- Control Sidebar Toggle Button -->

                </ul>
            </div>
        </nav>


    </header>
    <!-- Left side column. contains the logo and sidebar -->
    <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <!-- Sidebar user panel -->

            <!-- /.search form -->
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu" data-widget="tree">

                <br>
                <?php
                if ($_SESSION['escritorio'] == 1) {
                    echo ' <li  data-id="Panel de control"><a href="escritorio.php"><i class="fa  fa-dashboard (alias)"></i> <span>Panel de control</span></a>
                      </li>';
                }
                ?>
                <?php
                if ($_SESSION['ventas'] == 1) {
                    echo '<li class="treeview">
                      <a href="#">
                        <i class="fa fa-shopping-cart"></i> <span>Ventas</span>
                        <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li data-id="Pedidos"><a href="venta.php"><i class="fa fa-circle-o"></i>  Pedidos</a></li>
                        <li data-id="Consulta Ventas"><a href="ventasfechacliente.php"><i class="fa fa-circle-o"></i> Consulta de ventas</a></li>
                        <li data-id="Mapa"><a href="mapa.php"><i class="fa fa-circle-o"></i> Mapa de ventas</a></li>
                      </ul>
                    </li>';
                }
                ?>
                <?php
                if ($_SESSION['vendedores'] == 1) {
                    echo ' <li  data-id="Panel de control"><a href="vendedor.php"><i class="fa  fa-dashboard (alias)"></i> <span>Vendedores</span></a></li>';
                }
                ?>
                <?php
                if ($_SESSION['repartos'] == 1) {
                    echo '<li class="treeview">
                      <a href="#">
                        <i class="fa fa-shopping-cart"></i> <span>Repartos</span>
                        <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li data-id="Repartdores"><a href="repartidores.php"><i class="fa fa-circle-o"></i>  Repartidores</a></li>
                        <li data-id="Repartos"><a href="repartos.php"><i class="fa fa-circle-o"></i> Asignación de pedidos</a></li>
                      </ul>
                    </li>';
                }
                ?>

                ?>
                <?php

                if ($_SESSION['consultas'] == 1) {
                    echo '<li class="treeview">
                          <a href="#">
                            <i class="fa fa-th"></i> <span>Consultas</span>
                            <span class="pull-right-container">
                              <i class="fa fa-angle-left pull-right"></i>
                            </span>
                          </a>
                          <ul class="treeview-menu">
                                <li data-id="Consultas"><a href="consultas.php"><i class="fa fa-circle-o"></i> Consultas</a></li>
                                <li data-id="Motivos"><a href="motivoConsulta.php"><i class="fa fa-circle-o"></i> Motivos </a></li>
                                 <li data-id="Sector"><a href="areaConsulta.php"><i class="fa fa-circle-o"></i> Sectores</a></li>

                         </ul>
                     </li>';
                }

                if ($_SESSION['reclamos'] == 1) {

                    echo ' <li class="treeview">
                      <a href="#">
                        <i class="fa fa-th"></i> <span>Reclamos</span>
                        <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                       
                        <li data-id="Reclamos"><a href="reclamo.php"><i class="fa fa-circle-o"></i> Reclamos</a></li>            
                        <li data-id="Motivos de reclamos"><a href="motivo.php"><i class="fa fa-circle-o"></i> Motivos </a></li>
                        <li data-id="Sector"><a href="area.php"><i class="fa fa-circle-o"></i> Sectores</a></li>
                      </ul>
                    </li>';
                }
                ?>
                <?php
                if ($_SESSION['bd'] == 1) {
                    echo ' <li class="treeview">
                      <a href="#">
                        <i class="fa fa-laptop"></i> <span>Base de datos</span>
                        <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                         <li data-id="Solicitudes"><a href="solicitudes.php"><i class="fa fa-circle-o"></i> Solicitudes</a></li>
                         <li data-id="Clientes"><a href="cliente.php"><i class="fa fa-circle-o"></i> Clientes</a></li>
                        <li data-id="Articulos"><a href="articulo.php"><i class="fa fa-circle-o"></i> Articulos</a></li>            
                      </ul>
                    </li>';
                }
                ?>
                <?php
                    if ($_SESSION['seguridad'] == 1) {
                        echo '  <li class="treeview">
                      <a href="#">
                        <i class="fa fa-folder"></i> <span>Seguridad</span>
                        <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li data-id="Usuarios"><a href="usuario.php"><i class="fa fa-circle-o"></i> Usuarios</a></li>
                        <li data-id="Permisos"><a href="permiso.php"><i class="fa fa-circle-o"></i> Permisos</a></li>
                      </ul>
                    </li>';
                    }
                ?>
            </ul>
        </section>
    </aside>
