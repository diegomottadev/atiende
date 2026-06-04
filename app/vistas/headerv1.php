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
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link href="../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
   
    <link href="../public/mapboxgl/mapbox-gl.css" rel="stylesheet" />
     <!-- third party css -->
    <link href="../public/assets/css/vendor/dataTables.bootstrap5.css" rel="stylesheet" type="text/css" />
    <link href="../public/assets/css/vendor/responsive.bootstrap5.css" rel="stylesheet" type="text/css" />
    <link href="../public/assets/css/vendor/buttons.bootstrap5.css" rel="stylesheet" type="text/css" />
    <link href="../public/assets/css/vendor/select.bootstrap5.css" rel="stylesheet" type="text/css" />
    <link href="../public/assets/css/vendor/fixedHeader.bootstrap5.css" rel="stylesheet" type="text/css" />
    <link href="../public/assets/css/vendor/fixedColumns.bootstrap5.css" rel="stylesheet" type="text/css" />
    <!-- third party css end -->

    <!-- App css -->
    <link href="../public/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="../public/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    <!-- sweet alert -->
    <link href="../public/sweetAlert2/sweetalert2.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    
    <link href="../public/assets/css/vendor/jstree.min.css" rel="stylesheet" type="text/css">

    <style>
        /* unifica el tamaño de fuente de todos los badges del sistema */
        .badge {
            font-size: .75rem !important;
            font-weight: 600;
            padding: .35em .55em;
        }
        .sombra-logo {
            box-shadow: 1.5px 1.5px 1.5px rgba(0, 0, 0, 0.35) ;
        }
        .sombra {
            box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.35) ;
        }
        .sombra-panel{
            box-shadow: 2px 0px 15px rgba(0, 0, 0, 0.25);
        }
        #tbllistado_wrapper > div.dt-buttons.btn-group.flex-wrap{
            margin-bottom: -1.5em;
        }
        .btn-icon-line{
            line-height: 1 !important;
        }
        .bs-callout {
            padding: 9px;
            padding-bottom: 0!important;
            margin: 15px 0;
            border: 1px solid #9a7bfb24;
            border-left-width: 5px;
            border-radius: 3px;
            /*font-size: 10px;*/
        }
        .bs-callout-violeta {
            border-left-color: #9A7BFB;
        }
        .bs-callout-violeta li strong{
            color:#6650EA;
        }
        .bs-callout h5 {
            margin-top: 0;
            margin-bottom: 5px;
        }
        .cargando {
            width: 100%;height: 100%;
            overflow: hidden; 
            top: 0px;
            left: 0px;
            z-index: 10000;
            text-align: center;
            position:absolute; 
            background-color: #DFF0D8;
            opacity:0.6;
            filter:alpha(opacity=40);
        }
        .icono-ruta-ClubPedido{
            height: 2em;
            margin-bottom: 1em;
        }
    </style>
</head>
<body class="show" data-layout-color="light" data-leftbar-theme="dark" data-layout-mode="fluid" data-rightbar-onstart="true">
<script>
    var lenguajeTable = {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",            
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "previous":"<i class='mdi mdi-chevron-left'>",
                "next":"<i class='mdi mdi-chevron-right'>"
            },            
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",            
        };
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
    const globalIdUsuario = '<?php echo $_SESSION['idusuario'] ?? ''; ?>';
    // Token CSRF per-sesión (lo generó config/auth.php). El $.ajaxSetup que lo
    // adjunta y el manejador global $(document).ajaxError viven en footerv1.php,
    // porque acá jQuery/SweetAlert todavía no están cargados (se cargan al pie).
    const globalCsrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
</script>


<div class="wrapper">
    <!-- ========== Left Sidebar Start ========== -->
    <div class="leftside-menu sombra">    
        <!-- LOGO -->
        <a href="escritorio.php" class="logo  logo-light">
            <span class="logo-lg"  style = "margin-left: 2em;" >
            <!-- <img src="assets/images/logo.png" alt="" height="16">-->
                <img style="margin-top: 1.3em;" class="img-responsive" 
                src="../public/img/logo_lateral.png" height="55" alt="Logo " >
            </span>
            <span class="logo-sm text-center">
                <img src="../public/img/logo30x30.png" alt="" height="30">
            </span>
        </a>
        <div class="h-100 mt-0" id="leftside-menu-container" data-simplebar>

            <!--- Sidemenu -->
            <ul class="side-nav">

                <li class="side-nav-title side-nav-item"> </li>            
                <?php if ($_SESSION['escritorio'] == 1) { ?>
                    <li class="side-nav-item" data-id="Panel de control">
                        <a href="escritorio.php" class="side-nav-link">
                            <i class=" mdi mdi-monitor-dashboard"></i><!-- uil-dashboard  // mdi mdi-monitor-dashboard -->
                            <span> Panel de Control </span>
                        </a>
                    </li>                   
                <?php  } 
                if ($_SESSION['ventas'] == 1) {
                ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarVentas" aria-expanded="false" aria-controls="sidebarVentas" class="side-nav-link">
                            <i class="uil-shopping-cart-alt"></i>
                            <span> Ventas </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarVentas">
                            <ul class="side-nav-second-level">
                                <li>                            
                                    <a href="venta.php">
                                        <i class="uil-circle"></i> Pedidos
                                    </a> 
                                </li>
                                <li>
                                    <a href="ventasfechacliente.php">
                                        <i class="uil-circle"></i> Consulta de ventas
                                    </a>
                                </li>
                                <li>
                                    <a href="mapa.php">
                                        <i class="uil-circle"></i> Mapa de ventas
                                    </a>
                                </li>                                                       
                            </ul>
                        </div>
                    </li>
                <?php } 
                if ($_SESSION['vendedores'] == 1) { ?>
                    <li class="side-nav-item" data-id="Panel de control">
                        <a href="vendedor.php" class="side-nav-link">
                            <i class="mdi mdi-monitor-dashboard"></i>
                            <span> Vendedores </span>
                        </a>
                    </li>
                <?php  } 
                if ($_SESSION['repartos'] == 1) {?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarRepartos" aria-expanded="false" aria-controls="sidebarRepartos" class="side-nav-link">
                            <i class="uil-shopping-cart-alt"></i>
                            <span> Repartos </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarRepartos">
                            <ul class="side-nav-second-level">
                                <li>                            
                                    <a href="repartidores.php">
                                        <i class="uil-circle"></i> Repartidores
                                    </a> 
                                </li>
                                <li>
                                    <a href="repartos.php">
                                        <i class="uil-circle"></i> Asignación de pedidos
                                    </a>
                                </li>                                                                                 
                            </ul>
                        </div>
                    </li>
                <?php } 
                if ($_SESSION['mensajes'] == 1) {?>
                    <!--<li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarMensajesMasivoa" aria-expanded="false" aria-controls="sidebarMensajesMasivoa" class="side-nav-link">
                            <i class="uil-comment"></i>
                            <span> Mensajes Masivo </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarMensajesMasivoa">
                            <ul class="side-nav-second-level">
                                <li>                            
                                    <a href="mensajesB2B.php">
                                        <i class="uil-circle"></i> Mensajes a clientes
                                    </a> 
                                </li>
                                <li>
                                    <a href="mensajesB2C.php">
                                        <i class="uil-circle"></i> Mensajes a prospectos
                                    </a>
                                </li>                                                                                 
                            </ul>
                        </div>
                    </li> -->
                <?php }
                if ($_SESSION['consultas'] == 1) {?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarConsultas" aria-expanded="false" aria-controls="sidebarConsultas" class="side-nav-link">
                            <i class="mdi mdi-apps"></i>
                            <span> Consultas </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarConsultas">
                            <ul class="side-nav-second-level">
                                <li data-id="Consultas">                            
                                    <a href="consultas.php">
                                        <i class="uil-circle"></i> Consultas
                                    </a> 
                                </li>
                                <li data-id="Motivos">
                                    <a href="motivoConsulta.php">
                                        <i class="uil-circle"></i> Motivos
                                    </a>
                                </li>
                                <li data-id="Sector">
                                    <a href="areaConsulta.php">
                                        <i class="uil-circle"></i> Sectores
                                    </a>
                                </li>  
                            </ul>
                        </div>
                    </li>
                    
                <?php }
                if ($_SESSION['reclamos'] == 1) {?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarReclamo" aria-expanded="false" aria-controls="sidebarReclamo" class="side-nav-link">
                            <i class="mdi mdi-apps"></i>
                            <span> Reclamos </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarReclamo">
                            <ul class="side-nav-second-level">
                                <li data-id="Reclamo">                            
                                    <a href="reclamo.php">
                                        <i class="uil-circle"></i> Reclamos
                                    </a> 
                                </li>
                                <li data-id="ReclMotivos">
                                    <a href="motivo.php">
                                        <i class="uil-circle"></i> Motivos
                                    </a>
                                </li>
                                <li data-id="ReclSector">
                                    <a href="area.php">
                                        <i class="uil-circle"></i> Sectores
                                    </a>
                                </li>  
                            </ul>
                        </div>
                    </li>                
                <?php }
                if ($_SESSION['bd'] == 1) { ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarBD" aria-expanded="false" aria-controls="sidebarBD" class="side-nav-link">
                            <i class="mdi mdi-laptop"></i>
                            <span> Base de datos </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarBD">
                            <ul class="side-nav-second-level">
                                <li data-id="Solicitudes">                            
                                    <a href="solicitudes.php">
                                        <i class="uil-circle"></i> Solicitudes
                                    </a> 
                                </li>
                                <li data-id="Cliente">
                                    <a href="cliente.php">
                                        <i class="uil-circle"></i> Clientes
                                    </a>
                                </li>
                                <li data-id="Articulo">
                                    <a href="articulo.php">
                                        <i class="uil-circle"></i> Articulos
                                    </a>
                                </li>  
                            </ul>
                        </div>
                    </li>                  
                <?php }
                if ($_SESSION['seguridad'] == 1) { ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSeguridad" aria-expanded="false" aria-controls="sidebarSeguridad" class="side-nav-link">
                            <i class="mdi mdi-folder"></i>
                            <span> Seguridad</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSeguridad">
                            <ul class="side-nav-second-level">                            
                                <li data-id="Usuarios">
                                    <a href="usuario.php">
                                        <i class="uil-circle"></i> Usuarios
                                    </a>
                                </li>
                                <!--<li data-id="Permisos">
                                    <a href="permiso.php">
                                        <i class="uil-circle"></i> Permisos
                                    </a>
                                </li>-->
                            </ul>
                        </div>
                    </li>
                <?php }
                ?>
                <?php if (($_SESSION['configuracion'] ?? 0) == 1) { ?>
                    <li class="side-nav-item">
                        <a href="configuracion.php" class="side-nav-link">
                            <i class="mdi mdi-cog-outline"></i>
                            <span> Configuración</span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
            <!-- End Sidebar -->

            <div class="clearfix"></div>

        </div>
        <!-- Sidebar -left -->
    </div>
    <!-- Left Sidebar End -->
                     

    <!-- start content-page -->
    <div class="content-page">
        <div class="content">
            <!-- Topbar Start -->
            <div class="navbar-custom sombra">

                <ul class="list-unstyled topbar-menu float-end mb-0">
                    <li class="dropdown notification-list">
                        <a class="nav-link dropdown-toggle nav-user arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false"
                            aria-expanded="false">
                            <span class="account-user-avatar">
                                <?php
                                if (strlen($_SESSION['imagen']) > 0 && file_exists('../files/usuarios/'.$_SESSION['imagen'])) {
                                    $img ='../files/usuarios/'.$_SESSION['imagen'];
                                } else {
                                    $img = '../files/usuarios/user.png';
                                }
                                ?>
                                <img src="<?php echo $img; ?>" id="topbarAvatar" alt="user" onerror="this.src='../files/usuarios/user.png'" class="rounded-circle sombra-logo" style="width:36px;height:36px;object-fit:cover;">
                            </span>
                            <span  class="account-user-name" style="margin-top: 0.9em">
                                <?php echo $_SESSION['nombre']; ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated topbar-dropdown-menu profile-dropdown sombra ">                            
                            <!-- item-->
<!--                            <a href="javascript:void(0);" class="dropdown-item notify-item  text-center text-muted" >-->
<!--                                <span class="account-user-avatar"> -->
<!--                                    <img src="../files/usuarios/--><?php //echo $_SESSION['imagen']; ?><!--"  alt="user" class="img-thumbnail rounded-circle sombra-logo" style="height: 70px!important" >-->
<!--                                </span> <br>-->
<!--                                <span>-->
<!--                                    <span class="account-user-name">--><?php //echo $_SESSION['nombre']; ?><!--</span> <br> -->
<!--                                    <span class="account-position"><small>--><?php //echo date("d-m-Y"); ?><!--</small></span>-->
<!--                                </span>-->
<!--                            </a>-->
                            <a href="../ajax/usuario.php?op=salir"  class="dropdown-item text-center text-primary notify-item border-top border-light py-2">
                                <i class="mdi mdi-logout me-1"></i>
                                <span>Salir</span>
                            </a> 
                        </div>
                    </li>
                </ul>
                <button class="button-menu-mobile open-left">
                    <i class="mdi mdi-menu"></i>
                </button>
                
            </div>

            <!-- end Topbar -->
            <!-- Start Content-->
            <div class="container-fluid">
          
            