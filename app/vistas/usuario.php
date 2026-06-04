<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {

    require 'headerv1.php';
    if ($_SESSION['seguridad'] == 1) {?>
        <style>
#tbllistado thead th, table.dataTable thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
</style>
        <!-- start page title -->
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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Seguridad</a></li>
                            <li class="breadcrumb-item active"> Usuarios </li>
                        </ol>
                    </div>
                    <div class="float-start mt-3"><h4 class="page-title"> Usuarios</h4></div>
                </div>
            </div>
        </div>
        <div class="row mb-2 mt-n4">
            <div class="col-sm-12">            
                <div class="text-sm-end">
                    <button class="btn btn-success rounded-pill pull-right sombra-logo" id="btnagregar" onclick="mostrarform(true)">
                        <i class="mdi mdi-account-plus me-1"></i> Nuevo
                    </button>
                    <button class="btn btn-light rounded-pill sombra-logo" id="btnCancel" onclick="cancelarform()" type="button">
                            <i class="mdi mdi-arrow-left-circle me-1"></i> Volver
                    </button>    
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card sombra-panel" style="border-top:3px solid #727cf5;">
                    <div class="card-body p-0">
                        <div class="table-responsive" id="listadoregistros">
                            <table id="tbllistado" class="table table-sm table-striped table-centered mb-0 dt-responsive nowrap w-100">
                                <thead>
                                    <th>Opciones</th>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Numero Documento</th>
                                    <th>Telefono</th>
                                    <th>Email</th>
                                    <th>Login</th>
                                    <th>Foto</th>
                                    <th>Estado</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body px-3 pb-3" id="formularioregistros">
                                <h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><i class="mdi mdi-access-point me-1"></i> <span id="ribbon-text">Nuevo Usuario</span></h6>
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Nombre(*)</label>
                                                <input class="form-control" type="hidden" name="idusuario" id="idusuario">
                                                <input class="form-control" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Nombre" required>
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Tipo Documento(*)</label>
                                                <select class="form-control select2" data-toggle="select2" name="tipo_documento" id="tipo_documento" required>
                                                    <option value="DNI">DNI</option>
                                                    <option value="RUC">RUC</option>
                                                    <option value="CEDULA">CEDULA</option>
                                                </select>
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">N&deg; de Documento(*)</label>
                                                <input class="form-control" type="text" name="num_documento" id="num_documento" placeholder="Documento" maxlength="20">
                                            </div>                                           
                                        </div>
                                    </div>   
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Direccion</label>
                                                <input class="form-control" type="text" name="direccion" id="direccion" maxlength="70">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Telefono</label>
                                                <input class="form-control" type="text" name="telefono" id="telefono" maxlength="20" placeholder="N&uacute;mero de telefono">
                                            </div>                                           
                                        </div>                                    
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Email</label>
                                                <input class="form-control" type="email" name="email" id="email" maxlength="70" placeholder="email">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Cargo</label>
                                                <input class="form-control" type="text" name="cargo" id="cargo" maxlength="20" placeholder="Cargo">
                                            </div>                                           
                                        </div>  
                                    </div>
                                    <!--<span class="badge bg-info">login </span>-->
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Login(*)</label>
                                                <input class="form-control" type="text" name="login" id="login" maxlength="20" placeholder="nombre de usuario">
                                            </div>                                           
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label for="" class="form-label">Clave(*)</label>
                                                <div class="input-group input-group-merge">
                                                    <input class="form-control" type="password" name="clave" id="clave" maxlength="64" placeholder="Clave">
                                                    <div class="input-group-text" data-password="false">
                                                        <span class="password-eye"></span>
                                                    </div>
                                                </div>
                                            </div>                                           
                                        </div> 
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-2 position-relative">
                                                <label class="form-label">Permisos</label>
                                                <ul id="permisos" style="list-style: none;">

                                                </ul>                                                
                                            </div>                                                                     
                                        </div>
                                        <div class="col-lg-6">
                                            
                                            <div class="mb-2 position-relative">                                            
                                               <label for="imagen" class="form-label">Imagen de perfil</label>
                                                <input class="form-control" type="file" name="imagen" id="imagen" accept="image/png,image/jpeg">
                                                <input type="hidden" name="imagenactual" id="imagenactual">
                                                <small class="text-muted d-block mt-1"><i class="mdi mdi-information-outline"></i> Formatos: JPG o PNG · Tamaño máximo: <strong>2 MB</strong></small>
                                                <div class="mt-2">
                                                    <div style="position:relative;display:inline-block;">
                                                        <img src="" alt="" id="imagenmuestra" onerror="this.src='../files/usuarios/user.png'" style="width:96px;height:96px;border-radius:50%;object-fit:cover;display:none;border:2px solid #e2e0f0;box-shadow:0 2px 6px rgba(0,0,0,.08);">
                                                        <button type="button" id="btnQuitarImagen" title="Quitar" style="display:none;position:absolute;top:-2px;right:-2px;width:26px;height:26px;padding:0;border-radius:50%;background:#fa5c7c;color:#fff;border:2px solid #fff;font-size:15px;line-height:20px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.3);cursor:pointer;"><i class="mdi mdi-close"></i></button>
                                                    </div>
                                                </div>
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
    <?php }
    if ($_SESSION['seguridad'] == 10) {
        ?>
        <div class="content-wrapper">
            <!-- Main content -->
            <section class="content">

                <div class="row">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h1 class="box-title">Usuarios </h1>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-success" onclick="mostrarform(true)" id="btnagregar"><i
                                                class="fa fa-plus-circle"></i> Nuevo
                                    </button>
                                    <button class="btn btn-default" id="btnCancel" onclick="cancelarform()" type="button"><i
                                                class="fa fa-arrow-circle-left"></i> Volver
                                    </button>
                                </div>
                            </div>
                           <div class="box-body">
                                <div class="table-responsive" id="listadoregistros">
                                    <table id="tbllistado"
                                           class="table table-striped table-bordered table-condensed table-hover">
                                        <thead>
                                        <th>Opciones</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Numero Documento</th>
                                        <th>Telefono</th>
                                        <th>Email</th>
                                        <th>Login</th>
                                        <th>Foto</th>
                                        <th>Estado</th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                        <tfoot>
                                        <th>Opciones</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Numero Documento</th>
                                        <th>Telefono</th>
                                        <th>Email</th>
                                        <th>Login</th>
                                        <th>Foto</th>
                                        <th>Estado</th>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="" id="formularioregistros">
                                <form action="" name="formulario" id="formulario" method="POST">
                                    <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                        <label for="">Nombre(*):</label>
                                        <input class="form-control" type="hidden" name="idusuario" id="idusuario">
                                        <input class="form-control" type="text" name="nombre" id="nombre"
                                               maxlength="100" placeholder="Nombre" required>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Tipo Documento(*):</label>
                                        <select name="tipo_documento" id="tipo_documento"
                                                class="form-control select-picker" required>
                                            <option value="DNI">DNI</option>
                                            <option value="RUC">RUC</option>
                                            <option value="CEDULA">CEDULA</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Numero de Documento(*):</label>
                                        <input type="text" class="form-control" name="num_documento" id="num_documento"
                                               placeholder="Documento" maxlength="20">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Direccion</label>
                                        <input class="form-control" type="text" name="direccion" id="direccion"
                                               maxlength="70">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Telefono</label>
                                        <input class="form-control" type="text" name="telefono" id="telefono"
                                               maxlength="20" placeholder="Número de telefono">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Email: </label>
                                        <input class="form-control" type="email" name="email" id="email" maxlength="70"
                                               placeholder="email">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Cargo</label>
                                        <input class="form-control" type="text" name="cargo" id="cargo" maxlength="20"
                                               placeholder="Cargo">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Login(*):</label>
                                        <input class="form-control" type="text" name="login" id="login" maxlength="20"
                                               placeholder="nombre de usuario" required>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Clave(*):</label>
                                        <input class="form-control" type="password" name="clave" id="clave"
                                               maxlength="64" placeholder="Clave">
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label>Permisos</label>
                                        <ul id="permisos" style="list-style: none;">

                                        </ul>
                                    </div>
                                    <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                        <label for="">Imagen:</label>
                                        <input class="form-control" type="file" name="imagen" id="imagen">
                                        <input type="hidden" name="imagenactual" id="imagenactual">
                                        <img src="" alt="" width="150px" height="120" id="imagenmuestra">
                                    </div>
                                    <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <button class="btn btn-success pull-right" type="submit" id="btnGuardar"><i
                                                    class="fa fa-save"></i> Guardar
                                        </button>

                                    </div>
                                </form>
                            </div>
                           </div>
                        </div>
                    </div>
                </div>
                <!-- /.box -->

            </section>
            <!-- /.content -->
        </div>
        <?php
    } else {
        //require 'noacceso.php';
    }
  
    require 'footerv1.php';
    ?>
    <script>
        document.title = "Atiende | Usuarios";
    </script>
    <script src="scripts/usuario.js?t=<?php echo time(); ?>"></script>
    <?php
}

ob_end_flush();
?>
