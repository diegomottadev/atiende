var tablaRepartos;
var select = '<div class="d-flex align-items-center flex-nowrap gap-2"> <div class="input-group input-group-sm" style="max-width:230px;"><span class="input-group-text bg-white text-muted"><i class="mdi mdi-magnify"></i></span><input type="text" id="fBuscarRep" class="form-control" placeholder="Buscar pedido..."></div> <label class="text-muted fw-bold mb-0" style="font-size:.72rem; letter-spacing:.3px; white-space:nowrap;">Filtrar por estado:</label> <select class="form-select form-select-sm" name="filter" id="filter" style="max-width:170px;" onchange="filtrar()" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Mostrá solo los pedidos según su estado de reparto"> <option value="0">--Seleccionar--</option>';
select += '<option value="3">Desasignados</option>  <option value="2">Asignados</option>';
select += '<option value="4">Enviados</option><option value="1">Todos</option></select>';
select += '<button class="btn btn-sm btn-info" id="btnEnviarMsj" onclick="enviarMsj()" type="button" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Enviar mensaje de notificación a los clientes de los pedidos tildados">';
select += ' <i class=" uil-envelope"></i> Enviar mensaje</button></div>';
//funcion que se ejecuta al inicio
function init() {
    mostrarform(false);
    listar();
    // Aplicar filtro "Desasignados" por defecto al cargar
    $('#filter').val('3');
    filtrar();
    // Buscador del listado (client-side). El slot lo recrea filtrar(), por eso el handler va delegado.
    $(document).off('input.repbuscar').on('input.repbuscar', '#fBuscarRep', function(){
        var v = this.value;
        clearTimeout(window._repBuscarTimer);
        window._repBuscarTimer = setTimeout(function(){ if (tablaRepartos) tablaRepartos.search(v).draw(); }, 300);
    });
    /*
       $("#formulario").on("submit",function(e){
           guardaryeditar(e);
       });
    */
    //cargamos los items al select cliente
    $.post("../ajax/reparto.php?op=selectCliente", function (r) {
        $("#idcliente").html(r);
        /*$('#idcliente').selectpicker('refresh');*/
    });

    $.ajax({
        url: "../ajax/reparto.php?op=repartidores",
        type: "GET",
        contentType: false,
        processData: false,
        success: function ( datos) {
            $("#repartidor").empty();
            $("#repartidor").append('<option value="">-- Seleccionar  --</option>');
            $("#repartidor").append(JSON.parse(datos));

        }
    });

}

function asignar() {
    var repartidor = $('#repartidor').val();
    var checkboxes = document.getElementsByName('ckxPedido[]');
    var vals = "";
    for (var i=0, n=checkboxes.length;i<n;i++)
    {
        if (checkboxes[i].checked)
        {
            vals += ","+checkboxes[i].value;
        }
    }
    if (vals) vals = vals.substring(1);
    var formData = new FormData();
    formData.append("repartidor",repartidor);
    formData.append("pedidosId",vals);
    $.ajax({
        url: "../ajax/reparto.php?op=asignar",
        type: "POST",
        contentType: false,
        processData: false,
        data : formData,
        success: function ( datos) {
            Swal.fire({                    
                text: datos
            });
            mostrarform(false);
            listar();
        }
    });

}

function enProceso(pedidoid) {

    $.ajax({
        url: "../ajax/reparto.php?op=editarEstado&pedidoid=" + pedidoid,
        type: "POST",
        contentType: false,
        processData: false,

        success: function (datos) {
            var d;
            try { d = (typeof datos === 'string') ? JSON.parse(datos) : datos; } catch (e) { d = null; }

            if (d && d.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Estado del pedido modificado',
                    html: '<div style="text-align:left;font-size:.9rem;line-height:1.6;">' +
                              '<b>N° Pedido:</b> ' + (d.pedidoid || pedidoid) + '<br>' +
                              '<b>Fecha:</b> ' + (d.fecha   || '-') + '<br>' +
                              '<b>Cliente:</b> ' + (d.cliente || '-') + '<br>' +
                              '<b>Total:</b> $' + (d.total   || '-') +
                          '</div>',
                    confirmButtonColor: '#727cf5'
                });
            } else {
                Swal.fire({ icon: 'error', title: 'No se pudo modificar el estado del pedido' });
            }
            mostrarform(false);
            listar();
        }
    });
}


function aExcel() {

    $(location).attr('href', "../ajax/aExcelVentas.php?");

}

//funcion limpiar
function limpiar() {

    $("#idcliente").val("");
    $("#num_comprobante").val("");
    $("#fecha_hora").val("");
    $("#domicilio").val("");
    $("#idventa").val("");
    $("#total").val("");
    $("#telefono").val("");
}

//funcion mostrar formulario
function mostrarform(flag) {
    limpiar();
    if (flag) {

        $("#btnCancelar").show();
        $("#listadoregistros").hide();
        $("#formularioregistros").show();
        $("#formulariormsj").show();
        //$("#btnGuardar").prop("disabled",false);
        $("#btnagregar").hide();
        //listarArticulos();

        $("#btnGuardar").hide();
        $("#btnCancelar").show();
        detalles = 0;
        $("#btnAgregarArt").show();
        $("#btnExportar").hide();
        $("#asignarRepartidor").hide();
        
    } else {
        $("#btnCancelar").hide();
        $("#listadoregistros").show();
        $("#formularioregistros").hide();
        $("#btnagregar").show();
        $("#btnExportar").show()
        $("#formulariormsj").hide();
        $("#asignarRepartidor").show();
    }
}

//cancelar form
function cancelarform() {
    limpiar();
    mostrarform(false);
}

//funcion listar
function listar() {
    $('#btnEnviarMsj').hide();

    tablaRepartos = $('#tbllistadoRepartos').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            document.querySelectorAll('#tbllistadoRepartos [data-bs-toggle="tooltip"]').forEach(function(el){
                try {
                    var existing = bootstrap.Tooltip.getInstance(el);
                    if (existing) { existing.hide(); existing.dispose(); }
                    new bootstrap.Tooltip(el, {trigger: 'hover', animation: false});
                } catch(e) {}
            });
        },
        "language": lenguajeTable,
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Br<"toolbar">tip ',//definimos los elementos del control de la tabla
        responsive: window.matchMedia('(max-width: 991.98px)').matches,//solo en mobile (<992px, incluye tablets en vertical); en desktop, todas las columnas. Originalmente: colapsa columnas que no entran en una fila expandible (+)

        buttons: [],
        "ajax":
            {
                url: '../ajax/reparto.php?op=listar',
                type: "get",
                dataType: "json",
                error: function (e) {
                    console.log(e.responseText);
                }
            },
        "bDestroy": true,
        "iDisplayLength": 15,//paginacion
        "columnDefs": [
            { "targets": [8],     "visible": false, "searchable": false },
            { "targets": [11],    "visible": false, "searchable": false },
            { "targets": [9],     "visible": false, "searchable": false },
            { "targets": [12,13], "visible": false, "searchable": false },
        ],


    }).DataTable();
    //$("div.toolbar").html
    // Disponer tooltips antes de vaciar el slot para que no queden popups flotando
    document.querySelectorAll('#filtroRepartoSlot [data-bs-toggle="tooltip"]').forEach(function(el){
        var t = bootstrap.Tooltip.getInstance(el);
        if (t) { t.hide(); t.dispose(); }
    });
    $( "#filtroRepartoSlot" ).empty().append(select);
    $('#btnEnviarMsj').hide();
    // inicializa los tooltips de los controles fijos (asignar / filtrar / enviar mensaje)
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
        try {
            var existing = bootstrap.Tooltip.getInstance(el);
            if (existing) { existing.hide(); existing.dispose(); }
            new bootstrap.Tooltip(el, {trigger: 'hover', animation: false});
        } catch(e) {}
    });
    /*
    $( "#tbllistadoRepartos_filter" ).append('  <select name="filter" id="filter" onchange="filtrar()">' +
        '                                                <option value="0">--Seleccionar--</option>' +
        '                                                <option value="3">Desasignados</option>' +
        '                                                <option value="2">Asignados</option>' +
        '                                                <option value="4">Enviados</option>' +
        '                                                <option value="1">Todos</option>' +
        '                                            </select>');*/
}

function listarArticulos() {
    tabla = $('#tblarticulos').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Bfrtip',//definimos los elementos del control de la tabla
        buttons: [],
        "ajax":
            {
                url: '../ajax/reparto.php?op=listarArticulos',
                type: "get",
                dataType: "json",
                error: function (e) {
                    console.log(e.responseText);
                }
            },
        "bDestroy": true,
        "iDisplayLength": 5,//paginacion
        "order": [[0, "desc"]]//ordenar (columna, orden)
    }).DataTable();
}

function listarMensajes() {
    tabla = $('#tbmensajes').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": false,//paginacion y filrado realizados por el server
        dom: 'Bfrtip',//definimos los elementos del control de la tabla
        buttons: [],
        "ajax":
            {
                url: '../ajax/reparto.php?op=listarMensajes?idventa=' + document.getElementById("idventa").value,
                type: "get",
                dataType: "json",
                error: function (e) {
                    console.log(e.responseText);
                }
            },
        "bDestroy": true,
        "iDisplayLength": 5,//paginacion
        "order": [[0, "asc"]],
        "fnRowCallback": function (nRow, aaData, iDisplayIndex, iDisplayIndexFull) {
            if (aaData[4] === 0) {
                $('td', nRow).css('background-color', '#8E9FF4');
            }
        },//ordenar (columna, orden)
    });
}

//funcion para guardaryeditar
function guardaryeditar(e) {
    e.preventDefault();//no se activara la accion predeterminada
    //$("#btnGuardar").prop("disabled",true);
    var formData = new FormData($("#formulario")[0]);

    $.ajax({
        url: "../ajax/reparto.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,

        success: function (datos) {
            Swal.fire({                    
                text: datos
            });
            mostrarform(false);
            listar();
        }
    });

    limpiar();
}

function mostrar(idventa) {

    $.post("../ajax/reparto.php?op=listarDetalle&id=" + idventa, function (r) {
        $("#detalles").html(r);
    });

    $.post("../ajax/reparto.php?op=mostrar", {idventa: idventa},
        function (data, status) {
            data = JSON.parse(data);
            mostrarform(true);
            // {"pedidoid":"1274","fecha":"2020-09-14","clienteId":"1","razonSocial":"C. FINAL R","estado":"-1","total":"1640"}
            $("#idcliente").val(data.clienteId + "-" + data.razonSocial);
            $("#clienteid").val(data.clienteId);

            $("#num_comprobante").val(data.pedidoid);
            $("#fecha_hora").val(data.fecha);
            $("#domicilio").val(data.direccion);
            $("#telefono").val(data.telefono);


            $("#idventa").val(data.pedidoid);
            $("#total_input").val(data.total);
            $("#total_input").val(data.total);
            //ocultar y mostrar los botones
            $("#btnGuardar").hide();
            $("#btnCancelar").show();
            $("#btnAgregarArt").hide();
            //------------------------------
            $("#imprimir").html("<a class='btn btn-info btn-sm btn-icon-line' target='_blank' href='/ticket/" + data.pedidoid + "'> <i class='mdi mdi-printer m-n2'></i> </a>");
            $("#cambiarEstado").html('<button type="button" class="btn btn-secondary btn-sm btn-icon-line" onclick="enProceso(' + data.pedidoid + ')" ><i class="mdi mdi-cog m-n2"></i></button>');
            /*
            $("#imprimir").html("<a target='_blank' href='../reportes/exTicket.php?id=" + data.pedidoid + "'> <button class='btn btn-info btn-xs'><i class='fa fa-print'></i></button> </a>");
            $("#cambiarEstado").html('<button class="btn btn-default btn-xs" onclick="enProceso(' + data.pedidoid + ')" ><i class="fa fa-cog"></i></button>');
*/
            $.post("../ajax/reparto.php?op=listarMensajes&idventa=" + data.pedidoid, function (r) {
                $("#tbmensajes").html(r);
            });
            //---------------------------------------------------------------
        });

}

function enviarMsjACliente() {
    const telefono = $(".modal-body #telefono").val();
    const razonSocial = $(".modal-body #razonSocial").val();
    const msj = $(".modal-body #mensaje").val();

    Swal.fire({
        title:'',
        text: `Desesa enviar el mensaje al numero ${telefono} de ${razonSocial}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#727cf5',
        cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Aceptar'
    }).then((result) => {
        console.log(result);
        if (result.isConfirmed) {
            Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
            $.post('../ajax/send_wa.php', { to: telefono, text: msj + ' 🙂' }, function(res) {
                try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) { res = null; }
                if (res && res.ok === false) { Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text: res.error || 'Error desconocido' }); }
                else { Swal.fire({ icon:'success', title:'Mensaje enviado con Éxito!' }); }
            }).fail(function(xhr){ Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:'Error HTTP '+xhr.status }); });
            $('#sendMessage').modal('toggle');

        }
    })
}

function anular(idventa) {
    Swal.fire({
		title:'',
		text: '¿Esta seguro de anular este pedido',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#727cf5',
		cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
		confirmButtonText: 'Aceptar'
	  }).then((result) => {
		  console.log(result);
		if (result.isConfirmed) {
			$.post("../ajax/reparto.php?op=anular", {idventa: idventa}, function (e) {
				console.log(e);
				//bootbox.alert(e);
				Swal.fire({                    
                    text: e
                });
				tabla.ajax.reload();
			});
		 
		}
	  })   
}

var impuesto = 18;
var cont = 0;
var detalles = 0;

$("#btnGuardar").hide();
$("#tipo_comprobante").change(marcarImpuesto);

function marcarImpuesto() {
    var tipo_comprobante = $("#tipo_comprobante option:selected").text();
    if (tipo_comprobante === 'Factura') {
        $("#impuesto").val(impuesto);
    } else {
        $("#impuesto").val("0");
    }
}

function agregarDetalle(idarticulo, articulo, precio_venta) {
    var cantidad = 1;
    var descuento = 0;

    if (idarticulo !== "") {
        var subtotal = cantidad * precio_venta;
        var fila = '<tr class="filas" id="fila' + cont + '">' +
            '<td><button type="button" class="btn btn-danger" onclick="eliminarDetalle(' + cont + ')">X</button></td>' +
            '<td><input type="hidden" name="idarticulo[]" value="' + idarticulo + '">' + articulo + '</td>' +
            '<td><input type="number" name="cantidad[]" id="cantidad[]" value="' + cantidad + '"></td>' +
            '<td><input type="number" name="precio_venta[]" id="precio_venta[]" value="' + precio_venta + '"></td>' +
            '<td><input type="number" name="descuento[]" value="' + descuento + '"></td>' +
            '<td><span id="subtotal' + cont + '" name="subtotal">' + subtotal + '</span></td>' +
            '<td><button type="button" onclick="modificarSubtotales()" class="btn btn-info"><i class="fa fa-refresh"></i></button></td>' +
            '</tr>';
        cont++;
        detalles++;
        $('#detalles').append(fila);
        modificarSubtotales();

    } else {
        alert("error al ingresar el detalle, revisar las datos del articulo ");
    }
}

function modificarSubtotales() {
    var cant = document.getElementsByName("cantidad[]");
    var prev = document.getElementsByName("precio_venta[]");
    var desc = document.getElementsByName("descuento[]");
    var sub = document.getElementsByName("subtotal");


    for (var i = 0; i < cant.length; i++) {
        var inpV = cant[i];
        var inpP = prev[i];
        var inpS = sub[i];
        var des = desc[i];

        inpS.value = (inpV.value * inpP.value) - des.value;
        document.getElementsByName("subtotal")[i].innerHTML = inpS.value;
    }

    calcularTotales();
}

function calcularTotales() {
    var sub = document.getElementsByName("subtotal");
    var total = 0.0;

    for (var i = 0; i < sub.length; i++) {
        total += document.getElementsByName("subtotal")[i].value;
    }
    $("#total").html("$ " + total);
    $("#total_venta").val(total);
    evaluar();
}

function evaluar() {

    if (detalles > 0) {
        $("#btnGuardar").show();
    } else {
        $("#btnGuardar").hide();
        cont = 0;
    }
}

function eliminarDetalle(indice) {
    $("#fila" + indice).remove();
    calcularTotales();
    detalles = detalles - 1;

}

function enviarMensaje(empresa, ws) {

    if (document.getElementById("mensajeD").value.length > 0) {
        Swal.fire({
            title:'',
            text: "Desesa enviar el mensaje al numero " + document.getElementById("telefono").value + " ?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#727cf5',
            cancelButtonColor: '#fa5c7c',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `../ajax/venta.php?op=guardarMensaje&idventa=${document.getElementById("idventa").value}&mensaje=${document.getElementById("mensajeD").value}&clienteid=${document.getElementById("clienteid").value}&tipo=${document.getElementById("tipo").value}`,
                    type: "POST",
                    success: function (datos) {
                        var mensaje = "*Respuesta sobre el pedido N°*: " + document.getElementById("idventa").value + "\n";
                        mensaje += "*Fecha de Pedido*:" + document.getElementById("fecha_hora").value + " hs\n";
                        mensaje += "*Importe:* $" + $("#total_input").val() + "\n";
                        mensaje += "*Respuesta:* " + document.getElementById("mensajeD").value + " \n\n";

                        if (!$("#finalizarCheck").is(':checked')){
                            //mensaje += "*Responder :* "+ globalUrl +"/" + empresa + "/ws/m/msj.php?idventa=" + document.getElementById("idventa").value + "&id=" + document.getElementById("clienteid").value + "\n";
                            mensaje += "*Responder :* "+ globalUrl +"/ws/m/msj.php?idventa=" + document.getElementById("idventa").value + "&id=" + document.getElementById("clienteid").value + "\n";

                        }

                        var to = document.getElementById("telefono").value;

                        Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
                        $.post('../ajax/send_wa.php', { to: to, text: mensaje }, function(res){
                            try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) { res = null; }
                            if (res && res.ok === false) { Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text: res.error || 'Error desconocido' }); }
                            else { Swal.fire({ icon:'success', title:'Mensaje enviado' }); }
                        }).fail(function(xhr){ Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:'Error HTTP '+xhr.status }); });

                    }
                });
            }
        })
    } else {
        Swal.fire({
            text: "El campo de mensaje no esta completo!"
        });
    }

}

function enviarLink(empresa) {
    Swal.fire({
        title:'',
        text:"Desesa enviar el link de pago al número " + document.getElementById("telefono").value + " ?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#727cf5',
        cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Aceptar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById("mensaje").value = globalUrl + "/" + empresa + "/pedidos/pago.php?ped=" + document.getElementById("idventa").value + "&monto=" + $("#total_input").val() + "\n\n";
            var formData = new FormData($("#formulario")[0]);
            if (document.getElementById("mensaje").value.length > 0) {
                $.ajax({
                    url: "../ajax/venta.php?op=guardarMensaje",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,

                    success: function (datos) {

                        data = JSON.parse(datos);

                        var mensaje = "Para realizar el pago ingrese aquí 👇\n\n";
                        mensaje += document.getElementById("mensaje").value + " \n\n";

                        var to = document.getElementById("telefono").value;

                        Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
                        $.post('../ajax/send_wa.php', { to: to, text: mensaje }, function(res){
                            try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) { res = null; }
                            if (res && res.ok === false) { Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text: res.error || 'Error desconocido' }); }
                            else { Swal.fire({ icon:'success', title:'Mensaje enviado' }); }
                        }).fail(function(xhr){ Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:'Error HTTP '+xhr.status }); });
                    }
                });
            }
        }
    });

}



 function filtrar(){
     var filter = $('#filter').val();
     tablaRepartos = $('#tbllistadoRepartos').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            document.querySelectorAll('#tbllistadoRepartos [data-bs-toggle="tooltip"]').forEach(function(el){
                try {
                    var existing = bootstrap.Tooltip.getInstance(el);
                    if (existing) { existing.hide(); existing.dispose(); }
                    new bootstrap.Tooltip(el, {trigger: 'hover', animation: false});
                } catch(e) {}
            });
        },
        "language": lenguajeTable,
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": false,//paginacion y filrado realizados por el server
        dom: 'Br<"toolbar">tip',//definimos los elementos del control de la tabla
        responsive: window.matchMedia('(max-width: 991.98px)').matches,//solo en mobile (<992px, incluye tablets en vertical); en desktop, todas las columnas. Originalmente: colapsa columnas que no entran en una fila expandible (+)
        buttons: [],
        "ajax":{
            url: '../ajax/reparto.php?op=listar&filter='+filter,
            type: "get",
            dataType: "json",
            error: function (e) {
                console.log(e.responseText);
            }
        },
        "bDestroy": true,
        "iDisplayLength": 15,//paginacion
        "columnDefs": [
            { "targets": [0],  "visible": filter == 2 || filter == 3 || filter == 1, "searchable": false },
            { "targets": [8],  "visible": filter == 2 || filter == 1,                "searchable": true  },
            { "targets": [12], "visible": false,                                      "searchable": false },
            { "targets": [13], "visible": filter == 4,                                "searchable": false },
            { "targets": [9],  "visible": filter == 4,                                "searchable": false },
            { "targets": [11], "visible": filter == 4 || filter == 2,                 "searchable": false },
            { "targets": [3],  "visible": filter == 1,                                "searchable": true  },
        ],

     });
     // Disponer tooltips antes de vaciar el slot para que no queden popups flotando
     document.querySelectorAll('#filtroRepartoSlot [data-bs-toggle="tooltip"]').forEach(function(el){
         var t = bootstrap.Tooltip.getInstance(el);
         if (t) { t.hide(); t.dispose(); }
     });
     $( "#filtroRepartoSlot" ).empty().append(select);
     // Re-inicializar tooltips del slot
     document.querySelectorAll('#filtroRepartoSlot [data-bs-toggle="tooltip"], #asignarRepartidor [data-bs-toggle="tooltip"]').forEach(function(el){
         var t = bootstrap.Tooltip.getInstance(el);
         if (t) t.dispose();
         new bootstrap.Tooltip(el, {trigger: 'hover'});
     });

     $("#filter").val(filter);
     if(filter == 2) {
         $('#btnEnviarMsj').show();
         $('#asignarRepartidor').hide();

     }else{
         $('#btnEnviarMsj').hide();
         $('#asignarRepartidor').show();

     }
 }



function checkArrayEqualElements(_array)
{
    if(typeof _array !== 'undefined')
    {
        return _array.filter((e , i ,a)=> e===a[0]).length === _array.length;
    }
    return "Array is Undefined";
}

function enviarMsj() {
    var checkboxes = document.getElementsByName('ckxMsj[]');
    var vals = "";
    var checkRepartidores = [];

    for (var i=0, n=checkboxes.length;i<n;i++)
    {
        if (checkboxes[i].checked)
        {
            checkRepartidores.push($('#'+checkboxes[i].id).attr('data-id'));
        }
    }

    if ( checkRepartidores.length===0 ){
        Swal.fire({                    
            text: "Debe seleccionar un pedido para enviar los mensajes!"
        });
        return false;
    }
    if(checkArrayEqualElements(checkRepartidores)){

        Swal.fire({
            title:'',
            text:`¿Deseas enviar los pedidos seleccionados al repartidor asignado?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#727cf5',
            cancelButtonColor: '#fa5c7c',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                for (var i=0, n=checkboxes.length;i<n;i++)
                {
                    if (checkboxes[i].checked)
                    {
                        vals += ","+checkboxes[i].value;
                    }
                }
                if (vals) vals = vals.substring(1);
                Swal.fire({
                    title: 'Preparando envío...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: function() { Swal.showLoading(); }
                });
                var formData = new FormData();
                formData.append("pedidosIdToSendMsj",vals);
                $.ajax({
                    url: "../ajax/reparto.php?op=obtenerPedidos",
                    type: "POST",
                    contentType: false,
                    processData: false,
                    data: formData,
                    success: function (datos) {
                        var repartos = JSON.parse(datos);
                        var payload = _buildRepartoPayload(repartos);
                        _enviarRepartoWA(payload, repartos[0].telefono, function() {
                            for (var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = false; }
                            listar();
                        });
                    }
                });
            }
        });
    }else{
        Swal.fire({                    
            text: "Solo es posible enviar mensajes a un repartidor por vez"
        });
    }
}


function _buildRepartoPayload(repartos) {
    var hoy = new Date();
    var fechaYHora = hoy.getDate() + '/' + (hoy.getMonth()+1) + '/' + hoy.getFullYear()
                  + ' ' + hoy.getHours() + ':' + hoy.getMinutes() + ':' + hoy.getSeconds();
    var mensaje = "*Tienes un reparto*:\n*Fecha*: " + fechaYHora + "\n";
    var ubicaciones = [];
    repartos.forEach(function(v) {
        var detalle = "\n*Detalle productos:*\n\n";
        var productosPinLines = [];
        var n = 0;
        v.productos.forEach(function(p) {
            if (p.codProd === '.001') return;
            n++;
            detalle += n + "# - " + p.producto + " (" + p.cantidad + ")\n";
            productosPinLines.push(n + "# " + p.producto + " (" + p.cantidad + ")");
        });
        mensaje += "\n*N°:* " + v.pedido + "\n"
                 + "*Cliente:* " + v.cliente + "\n"
                 + "*Teléfono:* " + v.telCliente + "\n"
                 + detalle + "\n"
                 + "*Total:* $" + v.total + "\n"
                 + "*Dirección:* " + v.direccion + "\n"
                 + ((!isNaN(parseFloat(v.latitud)) && !isNaN(parseFloat(v.longitud)) && parseFloat(v.latitud) !== 0)
                    ? "*Ubicación:* https://maps.google.com/maps?q=" + v.latitud + "," + v.longitud + "\n"
                    : "");
        var lat = parseFloat(v.latitud), lng = parseFloat(v.longitud);
        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
            ubicaciones.push({ to: v.telefono, lat: lat, lng: lng, name: '', address: '' });
        }
    });
    return { mensaje: mensaje, ubicaciones: ubicaciones };
}

function _enviarRepartoWA(payload, telefono, onSuccess) {
    Swal.fire({
        title: 'Enviando mensaje...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    $.post('../ajax/send_wa.php', { to: telefono, text: payload.mensaje }, function(resp) {
        var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if (r.ok) {
            payload.ubicaciones.forEach(function(u) {
                $.post('../ajax/send_wa.php', {
                    type: 'location', to: u.to,
                    lat: u.lat, lng: u.lng, name: u.name, address: u.address
                });
            });
            Swal.fire({ icon: 'success', text: 'Mensaje' + (payload.ubicaciones.length ? ' y ubicación enviados' : ' enviado') + ' al repartidor.' });
            if (onSuccess) onSuccess();
        } else {
            Swal.fire({ icon: 'error', title: 'No se pudo enviar el mensaje', text: r.error || 'Error desconocido' });
        }
    });
}

function reenviarMensaje(pedidoid) {
    Swal.fire({
        title: 'Preparando envío...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    var formData = new FormData();
    formData.append("pedidosIdToSendMsj", pedidoid);
    $.ajax({
        url: "../ajax/reparto.php?op=obtenerPedidos",
        type: "POST",
        contentType: false,
        processData: false,
        data: formData,
        success: function(datos) {
            var repartos = JSON.parse(datos);
            if (!repartos || repartos.length === 0) {
                Swal.fire({ icon: 'error', text: 'No se encontraron datos del pedido.' });
                return;
            }
            var payload = _buildRepartoPayload(repartos);
            _enviarRepartoWA(payload, repartos[0].telefono, null);
        }
    });
}

function desasignar(pedido,repartidor){
    var formData = new FormData();
    formData.append("pedidosId",pedido);
    formData.append("repartidor",repartidor);
    $.ajax({
        url: "../ajax/reparto.php?op=desasignar",
        type: "POST",
        contentType: false,
        processData: false,
        data : formData,
        success: function ( datos) {

            const repartidorQ = JSON.parse(datos);
            const mensaje = "\n" + "*N° de pedido:* " + pedido + " a sido anulado";

            Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
            $.post('../ajax/send_wa.php', { to: repartidorQ.telefono, text: mensaje }, function(res){
                try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) { res = null; }
                if (res && res.ok === false) { Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text: res.error || 'Error desconocido' }); }
                else { Swal.fire({ icon:'success', title:'Mensaje enviado' }); }
            }).fail(function(xhr){ Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:'Error HTTP '+xhr.status }); });

            Swal.fire({                    
                text: "Se ha desasignado el repartidor al pedido seleccionado"
            });
            mostrarform(false);
            listar();
        }
    });
}

init();