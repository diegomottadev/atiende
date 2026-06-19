<?php
if (strlen(session_id()) < 1)
    session_start();
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/global.php');

// CSRF: garantizar el token al renderizar CUALQUIER vista. El login no pasa por
// auth.php (única ruta que lo generaba), así que sin esto la primera carga tras
// loguearse emite globalCsrfToken vacío y toda mutación AJAX devuelve 403.
require_once (__ROOT__.'/config/csrf.php');
ensureCsrfToken();

// Connection::rutaArticulos() para emitir globalArticulosDir (imágenes aisladas por tenant).
require_once (__ROOT__.'/config/Connection.php');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport">
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
        /* Botones deshabilitados (ej: Guardar antes de modificar): cursor de "no permitido" al pasar el puntero */
        .btn:disabled, .btn[disabled], button:disabled, button[disabled] {
            pointer-events: auto !important;
            cursor: not-allowed !important;
        }
        /* Topbar más fino (70px -> 56px) con el perfil (avatar + nombre) centrado en flex. */
        .navbar-custom { min-height: 56px !important; height: 56px !important; box-shadow: none !important; padding-right: 0 !important; }
        /* Quitar la sombra propia del tema en el topbar, el menú lateral y el dropdown del perfil (look plano) */
        .leftside-menu { box-shadow: none !important; }
        /* Dropdown de perfil: elevación sutil tipo Hyper (hairline + sombra mínima), no plano */
        .navbar-custom .profile-dropdown { box-shadow: 0 0 0 1px rgba(0,0,0,.05), 0 4px 16px rgba(0,0,0,.10) !important; border: 0 !important; border-radius: 8px !important; padding: 6px !important; min-width: 220px; overflow: hidden; }
        /* Encabezado de usuario dentro del dropdown */
        .profile-dropdown .dd-userbox { display: flex; align-items: center; gap: 10px; padding: 8px 10px; }
        .profile-dropdown .dd-userbox img { width: 40px; height: 40px; object-fit: cover; flex: 0 0 40px; }
        .profile-dropdown .dd-userbox .dd-name { font-weight: 600; font-size: .85rem; color: #313a46; line-height: 1.2; }
        .profile-dropdown .dd-userbox .dd-sub { font-size: .72rem; color: #98a6ad; line-height: 1.2; }
        .profile-dropdown .dropdown-divider { margin: 4px 0; }
        /* Opción Salir: alineada a la izquierda, tono rojo de marca, hover suave */
        .profile-dropdown .dd-logout { display: flex; align-items: center; gap: 8px; min-height: 36px; padding: 8px 10px; border-radius: 6px; color: #fa5c7c !important; font-weight: 500; transition: background-color .12s ease; }
        .profile-dropdown .dd-logout i { font-size: 1rem; }
        .profile-dropdown .dd-logout:hover, .profile-dropdown .dd-logout:focus { background-color: rgba(250,92,124,.10); color: #fa5c7c !important; }
        .navbar-custom .topbar-menu { height: 56px; }
        .navbar-custom .nav-user { display: inline-flex !important; align-items: center !important; height: 56px !important; min-height: 56px !important; margin-right: 0 !important; padding: 0 12px 0 16px !important; background-color: transparent !important; }
        .navbar-custom .account-user-avatar { position: static !important; display: inline-flex; align-items: center; margin-right: 8px; }
        .navbar-custom .account-user-avatar img { width: 34px !important; height: 34px !important; vertical-align: middle; }
        .navbar-custom .account-user-name { margin: 0 !important; line-height: 1.1; }
        .navbar-custom .button-menu-mobile { height: 56px !important; line-height: 56px !important; }
        .content-page { padding-top: 56px !important; }
        /* Breadcrumb más grande y en negrita */
        .breadcrumb { margin-bottom: .25rem; }
        .breadcrumb-item, .breadcrumb-item > a, .breadcrumb-item.active { font-size: .9rem !important; font-weight: 600 !important; }
        .breadcrumb-item + .breadcrumb-item::before { font-size: .9rem; }
        .sombra-logo {
            box-shadow: 1.5px 1.5px 1.5px rgba(0, 0, 0, 0.35) ;
        }
        .sombra {
            box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.35) ;
        }
        .sombra-panel{
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
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
        /* ===== Indicador de orden UNIFICADO para TODAS las DataTables =====
           DataTables (bootstrap5 css) dibuja las flechas como caracteres
           Unicode en los pseudo-elementos del th:
             :before = "↑"  (content)
             :after  = "↓"  (content)
           Para evitar dos flechas y los saltos verticales en tablas compactas,
           ocultamos todos los :before y usamos UN solo :after a la IZQUIERDA del
           label, con el glifo correcto por estado (sin ordenar ↓ tenue, asc ↑,
           desc ↓), siempre en la misma posición vertical, y la columna activa en
           negrita + color de marca. Aplica a toda tabla DataTable, sin importar id. */
        table.dataTable thead > tr > th.sorting,
        table.dataTable thead > tr > th.sorting_asc,
        table.dataTable thead > tr > th.sorting_desc {
            padding-left: 24px !important;
            padding-right: 12px !important;
        }
        /* Un SOLO indicador (:after) a la izquierda del label; ocultamos todos
           los :before del tema para que no haya dos flechas ni saltos. */
        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before { display: none !important; }

        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            position: absolute !important;
            left: .5em !important;
            right: auto !important;
            top: 50% !important;
            bottom: auto !important;
            transform: translateY(-50%);
            display: block !important;
            font-size: .7rem;
        }
        /* sin ordenar: flecha ↓ tenue (indicador de columna ordenable) */
        table.dataTable thead .sorting:after      { content: "↓" !important; opacity: .35 !important; }
        /* ASCENDENTE: flecha ↑ sólida */
        table.dataTable thead .sorting_asc:after  { content: "↑" !important; opacity: 1 !important; }
        /* DESCENDENTE: flecha ↓ sólida */
        table.dataTable thead .sorting_desc:after { content: "↓" !important; opacity: 1 !important; }
        /* columna activa: header en negrita + color de marca (tiñe la flecha) */
        table.dataTable thead > tr > th.sorting_asc,
        table.dataTable thead > tr > th.sorting_desc {
            font-weight: 700 !important;
            color: #6650EA !important;
        }
        /* ===== Ancho UNIFICADO para TODAS las tablas de listado =====
           Igual que en articulo.php: la tabla ocupa el 100% del ancho de su
           contenedor (la card). DataTables, con autoWidth activo, fija un
           ancho en px segun el contenido -> tablas con pocas columnas o texto
           corto quedaban mas angostas. Forzamos 100% en la tabla y su wrapper
           para que TODAS midan lo mismo, sin importar su contenido ni el id. */
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
        /* ===== Alto de fila UNIFICADO para TODAS las tablas de listado =====
           Igual que articulo.php (table-sm): mismo padding vertical y contenido
           centrado, tengan o no la clase table-sm. Así todas las filas miden
           lo mismo en todas las vistas. */
        #tbllistado > thead > tr > th,
        #tbllistado > tbody > tr > td,
        table.dataTable > thead > tr > th,
        table.dataTable > tbody > tr > td {
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            vertical-align: middle !important;
        }
        /* Imágenes/avatars en celdas: alto acotado para no inflar la fila */
        #tbllistado td img,
        table.dataTable td img {
            max-height: 38px;
        }
        /* ===== Cursor "manito" en TODOS los botones del sistema al hacer hover ===== */
        .btn:not(:disabled),
        button:not(:disabled),
        [role="button"]:not(:disabled),
        a[onclick]:not(:disabled),
        .page-link,
        .dropdown-item,
        .dropdown-toggle { cursor: pointer !important; }

        /* ===== Botones de filtro "Limpiar": borde del mismo gris que la línea de
           los inputs (--ct-input-border-color: #dee2e6) para integrarlos con la
           barra de filtros en TODAS las vistas. ===== */
        .btn[id^="fLimpiar"] {
            border: 1px solid var(--ct-input-border-color, #dee2e6) !important;
        }
        /* ===== Menú mobile (off-canvas) — pulido pro (≤ 991.98px) ===== */
        @media (max-width: 991.98px) {
            /* Hamburguesa: botón redondeado, target táctil amplio, color de marca */
            .button-menu-mobile {
                width: 42px; height: 42px;
                display: inline-flex; align-items: center; justify-content: center;
                margin: 7px 6px;
                border: 0; border-radius: 12px;
                background: #f0edff; color: #6650EA;
                font-size: 1.4rem; line-height: 1;
                transition: background .15s ease, transform .1s ease;
            }
            .button-menu-mobile:active { transform: scale(.94); background: #e3ddff; }
            /* Sidebar deslizante: por encima del backdrop, sombra y transición suave */
            .leftside-menu {
                z-index: 1045 !important;
                box-shadow: 8px 0 40px rgba(20,18,40,.35) !important;
                transition: transform .26s cubic-bezier(.4,0,.2,1), margin-left .26s cubic-bezier(.4,0,.2,1) !important;
            }
            /* Backdrop oscuro al abrir el menú (Hyper agrega .sidebar-enable al body) */
            body.sidebar-enable::after {
                content: ""; position: fixed; inset: 0;
                background: rgba(15,14,30,.5);
                z-index: 1040; animation: vfcMenuFade .2s ease;
            }
            @keyframes vfcMenuFade { from { opacity: 0 } to { opacity: 1 } }
            /* Items del menú: más alto para el dedo */
            .side-nav .side-nav-link { padding-top: 12px !important; padding-bottom: 12px !important; font-size: .95rem; }
            .side-nav-second-level li > a { padding-top: 10px !important; padding-bottom: 10px !important; }
        }
        /* Fix mobile: el topbar es position:fixed → dar aire arriba para que NO tape el
           contenido (breadcrumb + botones de acción), y soltar el tirón negativo mt-n4. */
        @media (max-width: 767.98px) {
            /* 64px arriba: despeja el topbar fijo (56px). 12px a los lados: el tema deja
               el padding horizontal en 0 en mobile y overflow:hidden recorta botones/sombras. */
            .content-page { padding: 64px 12px 60px !important; }
            .row.mt-n4 { margin-top: .25rem !important; }
            /* Barra de acciones (Nuevo/Exportar/Volver): sin floats que desbordan;
               flex que envuelve y alinea a la derecha, sin cortar botones. */
            .row.mt-n4 .text-sm-end {
                display: flex; flex-wrap: wrap; justify-content: flex-end;
                gap: .4rem;
            }
            .row.mt-n4 .text-sm-end .btn {
                float: none !important;
                margin-left: 0 !important; margin-right: 0 !important;
            }
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
    // Dir de imágenes de artículos del tenant activo (aislado): ../files/articulos/{seg}/
    const globalArticulosDir = '<?php echo Connection::rutaArticulos(); ?>';
</script>


<div class="wrapper">
    <!-- ========== Left Sidebar Start ========== -->
    <div class="leftside-menu">
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
                <?php
                    $imgMob = (strlen($_SESSION['imagen'] ?? '') > 0 && file_exists('../files/usuarios/'.$_SESSION['imagen']))
                        ? '../files/usuarios/'.$_SESSION['imagen']
                        : '../files/usuarios/user.png';
                ?>
                <!-- Mobile: ficha del usuario dentro del menú (en desktop está en el topbar) -->
                <li class="side-nav-item d-lg-none">
                    <div class="d-flex align-items-center gap-2 px-3 py-2 mb-2" style="border-bottom:1px solid rgba(255,255,255,.08);">
                        <img src="<?php echo $imgMob; ?>" onerror="this.src='../files/usuarios/user.png'" class="rounded-circle" style="width:42px;height:42px;object-fit:cover;flex:0 0 42px;">
                        <div class="overflow-hidden">
                            <div class="text-truncate" style="font-weight:600;font-size:.9rem;line-height:1.2;color:#fff;"><?php echo htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size:.72rem;color:#8a93a5;">Sesión activa</div>
                        </div>
                    </div>
                </li>
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
                <!-- Mobile: cerrar sesión dentro del menú (en desktop está en el dropdown del topbar) -->
                <li class="side-nav-item d-lg-none mt-1" style="border-top:1px solid rgba(255,255,255,.08);">
                    <a href="../ajax/usuario.php?op=salir" class="side-nav-link" style="color:#fa5c7c;">
                        <i class="mdi mdi-logout"></i>
                        <span> Salir </span>
                    </a>
                </li>
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
            <div class="navbar-custom">

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
                                <?php echo htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated topbar-dropdown-menu profile-dropdown">
                            <!-- Encabezado de usuario -->
                            <div class="dd-userbox">
                                <img src="<?php echo $img; ?>" alt="user" onerror="this.src='../files/usuarios/user.png'" class="rounded-circle">
                                <div class="overflow-hidden">
                                    <div class="dd-name text-truncate"><?php echo htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="dd-sub">Sesión activa</div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <!-- Acción: cerrar sesión -->
                            <a href="../ajax/usuario.php?op=salir" class="dropdown-item notify-item dd-logout">
                                <i class="mdi mdi-logout"></i>
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
          
            