            </div>
            <!-- end div contenido -fluid-->
        </div>
        <!-- content -->
        <!-- Footer Start -->
       
        <!-- end Footer -->
    </div>
    <!-- end content-page -->

</div>
<!-- END wrapper -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <strong>Copyright &copy;<?php echo date("Y"); ?>  | Atiende | Todo los derechos reservados.</strong>
                </div>                    
            </div>
        </div>
    </footer>

        <!-- bundle -->
        <script src="../public/assets/js/vendor.min.js"></script>
        <script src="../public/assets/js/app.min.js"></script>

        <!-- third party js -->
        <script src="../public/assets/js/vendor/jquery.dataTables.min.js"></script>
        <script src="../public/assets/js/vendor/dataTables.bootstrap5.js"></script>
        <script src="../public/assets/js/vendor/dataTables.responsive.min.js"></script>
        <script src="../public/assets/js/vendor/responsive.bootstrap5.min.js"></script>
        <script src="../public/assets/js/vendor/dataTables.buttons.min.js"></script>
        <script src="../public/assets/js/vendor/buttons.bootstrap5.min.js"></script>
        <script src="../public/assets/js/vendor/buttons.html5.min.js"></script>
        <script src="../public/assets/js/vendor/buttons.flash.min.js"></script>
        <script src="../public/assets/js/vendor/buttons.print.min.js"></script>
        <script src="../public/assets/js/vendor/dataTables.keyTable.min.js"></script>
        <script src="../public/assets/js/vendor/dataTables.select.min.js"></script>
        <script src="../public/assets/js/vendor/fixedColumns.bootstrap5.min.js"></script>
        <script src="../public/assets/js/vendor/fixedHeader.bootstrap5.min.js"></script>
        <script src="../public/datatables/jszip.min.js"></script>
        <script src="../public/datatables/pdfmake.min.js"></script>
        <script src="../public/datatables/vfs_fonts.js"></script>
        <!-- third party js ends -->

        <!-- demo app -->
        <script src="../public/assets/js/pages/demo.datatable-init.js"></script>
        <!-- end demo js-->
        <script src="../public/sweetAlert2/sweetalert2.all.min.js"></script>
        <script src="../public/assets/js/pages/demo.toastr.js"></script>
        
        <script src="../public/mapboxgl/mapbox-gl.js" type="text/javascript"></script>

        <!-- Protección CSRF (corre acá, ya cargados jQuery y SweetAlert) -->
        <script type="text/javascript">
            // Adjuntar el token CSRF en TODA petición same-origin (GET incluido):
            // algunas mutaciones legacy de venta/reparto (editarEstado, guardarMensaje,
            // asignar) leen 'op' por GET. En endpoints de solo lectura el server lo
            // ignora; al ir como header (no en la URL) no se loguea. Se omite solo en
            // URLs externas absolutas para no filtrar el token a terceros.
            $.ajaxSetup({
                beforeSend: function (xhr, s) {
                    var url = s.url || '';
                    var external = /^https?:\/\//i.test(url) && url.indexOf(window.location.origin) !== 0;
                    if (!external && typeof globalCsrfToken !== 'undefined' && globalCsrfToken) {
                        xhr.setRequestHeader('X-CSRF-Token', globalCsrfToken);
                    }
                }
            });

            // Manejo global de errores AJAX: un 403/401 no debe dejar la UI colgada.
            $(document).ajaxError(function (e, xhr) {
                if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión de seguridad expirada',
                        text: 'Recargá la página e intentá de nuevo.'
                    });
                } else if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión expirada',
                        text: 'Volvé a iniciar sesión.'
                    }).then(function () {
                        window.location.href = (typeof globalUrl !== 'undefined' ? globalUrl : '../') + 'index.php';
                    });
                }
            });
        </script>
    </body>
</html>