<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Atiende</title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
         <!-- App css -->
         <link href="../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
        <link href="../public/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="../public/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style"/>
        <link href="../public/sweetAlert2/sweetalert2.min.css" rel="stylesheet" type="text/css" id="app-style"/>
        <style>
            body{
                background-image: linear-gradient(180deg, #6650EA 0%, #3AEDCB 100%);
                opacity: 1;
                transition: background 0.3s, border-radius 0.3s, opacity 0.3s;
            }
            .sombra {
                box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.35) ;
            }
            .card-heard-degradee{
                background-image: linear-gradient(-33deg, #6650EA 0%, #3AEDCB 100%);
                opacity: 1;
                transition: background 0.3s, border-radius 0.3s, opacity 0.3s;
            }
            /*.swal2-popup.swal2-modal.swal2-show */
            .swal2-popup.swal2-modal.swal2-show {
                font-size: 11px!important;
            }
            .swal2-html-container{
                font-size: 15px!important;
            }
        </style>
    </head>
    <body>
        <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-4 col-lg-5">
                        <div class="card sombra">

                            <!-- Logo -->
                            <div class="card-header py-2 card-heard-degradee" style="padding-left: 4.2em;">                                
                                <span>
                                     <img src="../public/img/logo_lateral.png" alt="" height="80">
                                </span>
                            </div>

                            <div class="card-body p-3">     
                        <!--    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <strong>Error - </strong>  Usuario y/o Password incorrectos
                                </div>   -->                        
                                <div class="text-center w-75 m-auto">
                                    <h4 class="text-dark-50 text-center pb-0 fw-bold">Iniciar sesión</h4>
                                    <p class="text-muted mb-1">Ingrese sus datos de Acceso</p>
                                </div>
                               <!-- <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <strong>Error - </strong>  Usuario y/o Password incorrectos
                                </div>-->
                                <form  method="post" id="frmAcceso">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="uil-building"></i> Empresa</label>
                                        <input class="form-control" type="text" id="empresa" name="empresa" placeholder="Slug de la empresa (ej: mi_empresa)">
                                        <small class="text-muted">Dejá vacío si usás la cuenta principal</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="emailaddress" class="form-label"> <i class="uil-user"></i>   Usuario</label>
                                        <input class="form-control" type="text"  id="logina" name="logina" required="" placeholder="Ingrese su usuario">
                                    </div>
                                    <div class="mb-3">                                      
                                        <label for="password" class="form-label"> <i class="uil-padlock"></i> Contraseña</label>
                                        <div class="input-group input-group-merge">
                                            <input type="password" id="clavea" name="clavea" class="form-control" placeholder="Ingrese su contraseña">
                                            <div class="input-group-text" data-password="false">
                                                <span class="password-eye"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="checkbox-signin" checked>
                                            <label class="form-check-label" for="checkbox-signin">Recuerdame</label>
                                        </div>
                                    </div>
                                    <div class="mb-3 mb-0 text-center">
                                        <button class="btn btn-primary" type="submit"> 
                                            <i class="mdi mdi-login"></i>  Ingresar 
                                        </button>
                                    </div>
                                </form>
                            </div> <!-- end card-body -->
                        </div>
                        <!-- end card -->
                    </div> <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end page -->

        <footer class="footer footer-alt">
        <strong>Copyright &copy;<?php echo date("Y"); ?>  | Atiende | Todo los derechos reservados.</strong>
        </footer>

        <!-- bundle -->
        <script src="../public/assets/js/vendor.min.js"></script>
        <script src="../public/assets/js/app.min.js"></script>
        <script src="../public/sweetAlert2/sweetalert2.all.min.js"></script>
        <script src="scripts/login.js?t=<?php echo time(); ?>"></script>        
    </body>
</html>