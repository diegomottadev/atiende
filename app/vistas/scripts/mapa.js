    var table = null;
    let totales = 0.00;
    let sim = "";
    var currentMarkers=[];
    mapboxgl.accessToken = 'pk.eyJ1IjoiY3VlbnRhbWV3YiIsImEiOiJja3hnaHBydGExd3BjMnVtZmZ4ZGh1cGcyIn0.eYSzd_GrUOiSDLQk6I6QfA';

        const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [-63.63,-40.51],
        zoom: 3
    });
    map.addControl(new mapboxgl.NavigationControl());
    map.on('load', function() { map.resize(); });
    window.addEventListener('resize', function() { map.resize(); });

    function mostrarMapa() {
        var formData = new FormData($("#formulario")[0]);
        
        if ($('input[name=optradio]:checked').val() == 'reclamos'){
            $("#tbllistado").addClass("reclamos");
            $("#tbllistado").removeClass("ventas");
        }else{
            $("#tbllistado").addClass("ventas");
            $("#tbllistado").removeClass("reclamos");
        }
        var data = {
            'vendedor': $("#vendedor").val(),
            'fecha_inicio': $("#fecha_inicio").val(),
            'fecha_fin': $("#fecha_fin").val(),
            'ramo': $("#ramo").val(),
            'zona': $("#zona").val(),
            'repartidor': $("#repartidor").val()
        }
        $.ajax({
            url: "../ajax/mapa.php?op=" + $('input[name=optradio]:checked').val(),
            type: "POST",
            data: data,
            success: function (datos) {
                var html = '';
                totales = 0.0;
                var dataSet = new Array();

                var array = JSON.parse(datos);

                if ($('input[name=optradio]:checked').val() === "ventas") {
                    sim = "$";
                }
                else {
                    sim = "";
                }

                for (var i = 0; i < array.length; i++) {
                    var d = new Array();
                    d.push(array[i].codigo);
                    d.push(array[i].razonSocial);
                    d.push( array[i].total_format);
                    dataSet.push(d);
                    totales = totales + parseFloat(array[i].cantidad);
                }

                if (sim.length > 0) {
                    $('#total').html(sim + " " + String(totales).replace(/\B(?=(\d{3})+(?!\d))/g, "."));
                } else {
                    $('#total').html(sim + " " + String(totales));
                }

                var esVentas = $('input[name=optradio]:checked').val() === "ventas";
                for (const feature of array) {
                    if (feature.longitud !== undefined && feature.latitud !== undefined){
                        if(!isNaN(parseFloat(feature.longitud)) && !isNaN(parseFloat(feature.latitud)) ){
                            var totalTxt = (feature.total_format !== undefined && feature.total_format !== null)
                                ? feature.total_format
                                : feature.cantidad;
                            var ramoHtml = (feature.ramo && String(feature.ramo).trim().length > 0)
                                ? `<div class="mp-ramo">${feature.ramo}</div>`
                                : '';
                            var popupHtml =
                                `<div class="map-popup">
                                    <div class="mp-name">${feature.razonSocial || ''}</div>
                                    <div class="mp-cod">Cód. ${feature.codigo || '-'}</div>
                                    ${ramoHtml}
                                    <span class="mp-total-label">${esVentas ? 'Total ventas' : 'Reclamos'}</span>
                                    <div class="mp-total">${sim} ${totalTxt}</div>
                                </div>`;
                            var oneMarker = new mapboxgl.Marker({color: '#6c63ff'})
                            .setLngLat([parseFloat(feature.longitud), parseFloat(feature.latitud)])
                            .setPopup(
                                new mapboxgl.Popup({offset: 28})
                                    .setHTML(popupHtml)
                            )
                            .addTo(map);
                            currentMarkers.push(oneMarker);
                        }
                    }
                }

                $('#tbllistado').DataTable({
                    "language": lenguajeTable,
                    "paging": false,
                    "searching": false,
                    "info": false,
                    "bDestroy": true,
                    "data": dataSet,
                    "order": [],
                    "columnDefs": [ {
                        "targets"  : 'no-sort',
                        "orderable": false,
                    }]
                });
            }
        });
    }
    $('input[type=radio][name=optradio]').change(function() {
        if (this.value == 'ventas') {      
            $(".repartidor").show();         
            $('#repartidor').val([])
            $('#repartidor').selectpicker('refresh');  
        }
        else if (this.value == 'reclamos') {
            $(".repartidor").hide();
        }
    });
    function limpiarDatos(){

        //limpia los datos q tiene la tabla
        $("#tbllistado").dataTable().fnClearTable();
        //total 
        $('#total').html('');
        //limpia todos los combos
        $('.select2').val('');
        $(".select2").trigger('change');          
        //remueve los marcadores del mapa
        if (currentMarkers!==null) {
            for (var i = currentMarkers.length - 1; i >= 0; i--) {
                currentMarkers[i].remove();
            }
        }
    }
