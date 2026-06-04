<footer class="main-footer">
<!--    <div class="pull-right hidden-xs">-->
<!--        <b>Version</b> 0.0.1-->
<!--    </div>-->
    <strong>Copyright &copy;<?php echo date("Y"); ?>  | <a target="_blank"href="atiende.lat/"> atiende.lat </a> | Todo los derechos reservados.</strong>
</footer>

<!-- jQuery 3 -->

<script src="../public/js/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<!-- Bootstrap 3.3.7 -->
<script src="../public/js/bootstrap.min.js"></script>
<!-- Morris.js charts -->
<!-- AdminLTE App -->
<script src="../public/js/adminlte.min.js"></script>
<script src="../public/datatables/buttons.colVis.min.js"></script>
<script src="../public/datatables/buttons.html5.min.js"></script>
<script src="../public/datatables/dataTables.buttons.min.js"></script>
<script src="../public/datatables/jquery.dataTables.min.js"></script>
<script src="../public/datatables/jszip.min.js"></script>
<script src="../public/datatables/pdfmake.min.js"></script>
<script src="../public/datatables/vfs_fonts.js"></script>
<script src="../public/datatables/datatables.min.js"></script>
<!--  -->
<!--  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>-->
<!--  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>-->
<!--  <script type="text/javascript" src="https://cdn.datatables.net/v/dt/jq-3.6.0/jszip-2.5.0/dt-1.11.3/b-2.0.1/b-colvis-2.0.1/b-html5-2.0.1/b-print-2.0.1/cr-1.5.4/r-2.2.9/sp-1.4.0/datatables.min.js"></script>-->
<script src="../public/js/bootbox.min.js"></script>
<script src="../public/js/bootstrap-select.min.js"></script>
<script>
    // Textos de bootstrap-select en español
    if (window.jQuery && jQuery.fn.selectpicker) {
        var spDefaults = jQuery.fn.selectpicker.Constructor.DEFAULTS;
        spDefaults.noneSelectedText   = 'Ninguno seleccionado';
        spDefaults.noneResultsText    = 'Sin coincidencias para {0}';
        spDefaults.selectAllText      = 'Seleccionar todo';
        spDefaults.deselectAllText    = 'Quitar selección';
        spDefaults.countSelectedText  = function (n, N) { return (n == 1) ? '{0} seleccionado' : '{0} seleccionados'; };
        spDefaults.liveSearchPlaceholder = 'Buscar…';
    }
</script>
<script src="../public/mapboxgl/mapbox-gl.js" type="text/javascript"></script>
</body>
</html>