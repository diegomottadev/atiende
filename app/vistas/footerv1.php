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
        <!-- Global: el botón de confirmación de SweetAlert dice "Aceptar" (no "OK") en toda la app -->
        <script type="text/javascript">
            (function () {
                if (window.Swal && typeof Swal.fire === 'function' && !Swal.__aceptarPatched) {
                    var _fire = Swal.fire.bind(Swal);
                    Swal.fire = function () {
                        var a = arguments;
                        if (a.length === 1 && a[0] && typeof a[0] === 'object') {
                            if (a[0].confirmButtonText === undefined) { a[0].confirmButtonText = 'Aceptar'; }
                            return _fire(a[0]);
                        }
                        if (typeof a[0] === 'string') {
                            // forma posicional: Swal.fire(title, html, icon)
                            return _fire({ title: a[0], html: a[1], icon: a[2], confirmButtonText: 'Aceptar' });
                        }
                        return _fire.apply(Swal, a);
                    };
                    Swal.__aceptarPatched = true;
                }
            })();
        </script>
        <script src="../public/assets/js/pages/demo.toastr.js"></script>
        
        <script src="../public/mapboxgl/mapbox-gl.js" type="text/javascript"></script>

        <!-- Protección CSRF (corre acá, ya cargados jQuery y SweetAlert) -->
        <script type="text/javascript">
            // Quitar el selector "Mostrar X registros" (length menu) de TODAS las tablas, global.
            if (window.jQuery && $.fn && $.fn.dataTable) { $.extend(true, $.fn.dataTable.defaults, { lengthChange: false }); }
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

            // ── Tooltips globales: solo HOVER, sin animación, y nunca quedan pegados ──
            // Problema: botones de fila con data-bs-toggle="tooltip" cuyo onclick
            // redibuja la DataTable. Al redibujar, DataTables borra el botón mientras
            // el tooltip está visible → el popup queda huérfano flotando en <body> y
            // nada lo cierra. Además, el trigger por defecto de Bootstrap es
            // 'hover focus': tras un click el botón queda con foco y el tooltip persiste.
            if (window.bootstrap && bootstrap.Tooltip) {
                // Todo tooltip nuevo usa hover (no 'hover focus') y sin animación.
                bootstrap.Tooltip.Default.trigger = 'hover';
                bootstrap.Tooltip.Default.animation = false;
            }
            // Captura (fase capture → corre ANTES del onclick inline que redibuja):
            // oculta el tooltip del elemento clickeado antes de que su botón desaparezca,
            // y limpia cualquier popup huérfano que haya quedado de un redibujado previo.
            document.addEventListener('click', function (e) {
                var trg = e.target && e.target.closest ? e.target.closest('[data-bs-toggle="tooltip"]') : null;
                if (trg && window.bootstrap && bootstrap.Tooltip) {
                    try { var t = bootstrap.Tooltip.getInstance(trg); if (t) { t.hide(); } } catch (err) {}
                }
                setTimeout(function () {
                    document.querySelectorAll('.tooltip').forEach(function (tp) {
                        // huérfano = ningún disparador lo referencia por aria-describedby
                        if (!tp.id || !document.querySelector('[aria-describedby="' + tp.id + '"]')) tp.remove();
                    });
                }, 0);
            }, true);

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