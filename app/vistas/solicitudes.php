<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
}else{
    define('__ROOT__', dirname(dirname(__FILE__)));
    require (__ROOT__.'/vistas/headerv1.php');
    if ($_SESSION['bd']==1) {?>
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
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item" style="margin-top: -0.7em">
                                <a href="javascript: void(0);">                            
                                    <img src="../public/img/logo30x30.png" alt="" class="icono-ruta-ClubPedido" >            
                                    Atiende
                                </a>
                            </li>
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Base de Datos</a></li>
                            <li class="breadcrumb-item active"> Solicitudes </li>
                        </ol>
                    </div>
                   <!--<div class="float-start mt-3"><h4 class="page-title"> &nbsp;&nbsp;&nbsp;&nbsp;Mis Reclamos </h4></div>-->
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n2">
            <div class="col-sm-12"><div class="float-start "><h4 class="page-title"> &nbsp;&nbsp;&nbsp;&nbsp;Solicitudes </h4></div>            
                <div class="text-sm-end">                    
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
                    <div class="card-body p-0">
                            <div class="table-responsive" id="tblSolicitudesMain"> <!-- dt-responsive -->
                                
                                <table id="tblSolicitudes" class="table table-sm table-striped table-centered dt-responsive mb-0  nowrap w-100">
                                    <thead >
                                        <th width="60">Acciones</th>
                                        <th>Nombre</th>
                                        <th>CUIT/DNI</th>
                                        <th>Dirección</th>
                                        <th>Localidad</th>
                                        <th>Telefono</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="editSolicitudMain">
                                <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Alta del Cliente</span></h6>
                                <form action="" name="editSolicitudForm" id="editSolicitudForm" method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Nombre</label>
                                                <input class="form-control" type="hidden" name="id" id="id" >
                                                <input class="form-control" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Nombre del cliente" required>
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Codigo de cliente</label>
                                                <input class="form-control" type="text" name="codigo" id="codigo" placeholder="Codigo del cliente" required>
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">CUIT/DNI</label>
                                                <input class="form-control" type="text" name="cuit" id="cuit" maxlength="100"   placeholder="CUIT o DNI">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                            <label for="" class="form-label">Direcci&oacute;n</label>
                                                <input class="form-control" type="text" name="direccion" id="direccion" maxlength="100" placeholder="Direccion" required>
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Localidad</label>                                                
                                                <input class="form-control" type="text" name="localidad" id="localidad" maxlength="20" placeholder="Localidad">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Ramo</label>
                                                <input class="form-control" type="text" name="ramo" id="ramo" maxlength="70" placeholder="Ramo">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Telefono</label>
                                                <input class="form-control" type="hidden" name="whatsapp" id="whatsapp" >
                                                <input class="form-control" type="text" name="telefono" id="telefono" maxlength="20" placeholder="Número de Telefono">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Zona</label>
                                                <input class="form-control" type="text" name="zona" id="zona" placeholder="Zona">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Lista de Precio</label>                                                
                                                <input class="form-control" type="text" name="lista" id="lista" value="1" maxlength="20" placeholder="Lista de precio">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Dep&oacute;sito</label>                                                
                                                <input class="form-control" type="text" name="deposito" id="deposito" value="1"  maxlength="20" placeholder="Dep&oacute;sito">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Vendedor</label>
                                                <input class="form-control" type="text" name="vendedor" id="vendedor" maxlength="50" placeholder="Vendedor">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Latitud</label>
                                                <input class="form-control" type="text" name="latitud" id="latitud" maxlength="50" placeholder="Latitud">
                                            </div>                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">Longitud</label>                                                
                                                <input class="form-control" type="text" name="longitud" id="longitud" maxlength="50" placeholder="Longitud">
                                            </div>                                           
                                        </div>                                        
                                    </div> 
                                    <div class="row">
                                        <div class="col-md-12 text-sm-end">
                                            <button class="btn btn-danger  rounded-pill sombra-logo" onclick="cancelarform()" type="button">
                                                <i class="mdi mdi-arrow-left-circle me-1"></i> Cancelar
                                            </button>
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
    <?php
    }else{
        require 'noacceso.php';
    }
    require (__ROOT__.'/vistas/footerv1.php');
    ?>
    <script>
        document.title = "Atiende | Reclamos | Solicitudes";
    </script>
    <script src="scripts/solicitud.js?t=<?php echo time(); ?>"></script>
    <?php
}

ob_end_flush();
?>
