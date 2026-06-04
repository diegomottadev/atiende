var tabla;

//funcion que se ejecuta al inicio
function init() {
    mostrarform(false);
    listar();
    /*
       $("#formulario").on("submit",function(e){
           guardaryeditar(e);
       });
    */
    //cargamos los items al select cliente
    $.post("../ajax/venta.php?op=selectCliente", function (r) {
        $("#idcliente").html(r);
        //$('#idcliente').selectpicker('refresh');
    });


}

function enProceso(pedidoid, estado) {
    var aEntregado = (String(estado) !== '2'); // si no está Entregado, lo marca Entregado; si lo está, vuelve a Pendiente
    Swal.fire({
        title: aEntregado ? '¿Marcar como Entregado?' : '¿Volver a Pendiente?',
        text:  aEntregado ? 'El pedido pasará a Entregado.' : 'El pedido volverá a Pendiente.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#727cf5',
        cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, cambiar'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "../ajax/venta.php?op=editarEstado&pedidoid=" + pedidoid,
            type: "POST",
            contentType: false,
            processData: false,
            success: function (datos) {
                tabla.ajax.reload();
                Swal.fire({ icon: 'success', text: datos });
                mostrarform(false);
            }
        });
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
        $("#btnExportar").hide()


    } else {
        $("#btnCancelar").hide();
        $("#listadoregistros").show();
        $("#formularioregistros").hide();
        $("#btnagregar").show();
        $("#btnExportar").show()
        $("#formulariormsj").hide();

    }
}

//cancelar form
function cancelarform() {
    limpiar();
    mostrarform(false);
}

//funcion listar
function listar() {
    tabla = $('#tbllistado').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
            document.querySelectorAll('#tbllistado [data-bs-toggle="tooltip"]').forEach(function(el){
                var existing = bootstrap.Tooltip.getInstance(el);
                if (existing) existing.dispose();
                new bootstrap.Tooltip(el, {trigger: 'hover'});
            });
        },
        //responsive: true,
        //scrollX: true,
        "language": lenguajeTable,
        "columnDefs": [
            {
                "targets": [ 13 ],
                "visible": false,
                "searchable": false
            }
        ],
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Bfrtip',//definimos los elementos del control de la tabla
        buttons: [],
        "ajax":
            {
                url: '../ajax/venta.php?op=listar',
                type: "get",
                dataType: "json",
                error: function (e) {
                    console.log(e.responseText);
                }
            },
        "bDestroy": true,
        "iDisplayLength": 15,//paginacion

        "createdRow": function (row, data, dataIndex, cells) {
            console.log( data[13]);
            if ( data[13]>0 )
            {
                $(row).addClass('selected');
            }
        }
        //ordenar (columna, orden)

    }).DataTable();
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
                url: '../ajax/venta.php?op=listarArticulos',
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
                url: '../ajax/venta.php?op=listarMensajes?idventa=' + document.getElementById("idventa").value,
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
        url: "../ajax/venta.php?op=guardaryeditar",
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

    $.post("../ajax/venta.php?op=listarDetalle&id=" + idventa, function (r) {
        $("#detalles").html(r);
    });

    $.post("../ajax/venta.php?op=mostrar", {idventa: idventa},
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
            $("#imprimir").html("<a class='btn btn-info btn-sm btn-icon-line' target='_blank' href='../reportes/exTicket.php?id=" + data.pedidoid + "'> <i class='mdi mdi-printer m-n2'></i> </a>");
            $("#cambiarEstado").html('<button type="button" class="btn btn-secondary btn-sm btn-icon-line" onclick="enProceso(' + data.pedidoid + ')" ><i class="mdi mdi-cog m-n2"></i></button>');

            $.post("../ajax/venta.php?op=listarMensajes&idventa=" + data.pedidoid, function (r) {
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
            $.ajax({
                type: 'POST',
                url: '../pedidos/send_wa.php',
                success: function(res){ try{res=(typeof res==='string')?JSON.parse(res):res;}catch(e){res=null;} if(res&&res.ok===false){ Swal.fire({icon:'error',title:'No se pudo enviar el WhatsApp',text:res.error||'Error desconocido'}); } else { Swal.fire({icon:'success',title:'Mensaje enviado'}); } },
                error: function(xhr){ Swal.fire({icon:'error',title:'No se pudo enviar el WhatsApp',text:'Error HTTP '+xhr.status}); },
                data: { to: telefono, text: msj + ' 🙂' },
                complete: function() {
                    $('#sendMessage').modal('toggle');
                }
            });

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
            $.post("../ajax/venta.php?op=anular", {idventa: idventa}, function (e) {
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

//declaramos variables necesarias para trabajar con las compras y sus detalles
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
        Swal.fire({                    
            text: "error al ingresar el detalle, revisar las datos del articulo "
        });
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
        empresa = globalNombreEmpresa;
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
                                // mensaje += "*Responder :* "+ globalUrl +"/" + empresa + "/ws/m/msj.php?idventa=" + document.getElementById("idventa").value + "&id=" + document.getElementById("clienteid").value + "\n";
                                mensaje += "*Responder :* "+ globalUrl +"/ws/m/msj.php?idventa=" + document.getElementById("idventa").value + "&id=" + document.getElementById("clienteid").value + "\n";

                            }

                            var to      = document.getElementById("telefono").value;
                            var pedId   = document.getElementById("idventa").value;
                            var conBoton = !$("#finalizarCheck").is(':checked');

                            var sendData = { to: to, text: mensaje };
                            if (conBoton) {
                                sendData.idventa   = pedId;
                                sendData.clienteid = document.getElementById("clienteid").value;
                            }

                            Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
                            $.ajax({
                                type: 'POST',
                                url: '../pedidos/send_wa.php',
                                success: function(res){ try{res=(typeof res==='string')?JSON.parse(res):res;}catch(e){res=null;} if(res&&res.ok===false){ Swal.fire({icon:'error',title:'No se pudo enviar el WhatsApp',text:res.error||'Error desconocido'}); } else { Swal.fire({icon:'success',title:'Mensaje enviado'}); } },
                                error: function(xhr){ Swal.fire({icon:'error',title:'No se pudo enviar el WhatsApp',text:'Error HTTP '+xhr.status}); },
                                data: sendData,
                                complete: function() {
                                    $.post("../ajax/venta.php?op=listarMensajes&idventa=" + pedId, function(r) {
                                        $("#tbmensajes").html(r);
                                    });
                                }
                            });

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
                        $.ajax({
                            type: 'POST',
                            url: '../pedidos/send_wa.php',
                            data: { to: to, text: mensaje },
                            success: function(res){ try{res=(typeof res==='string')?JSON.parse(res):res;}catch(e){res=null;} if(res&&res.ok===false){ Swal.fire({icon:'error',title:'No se pudo enviar el WhatsApp',text:res.error||'Error desconocido'}); } else { Swal.fire({icon:'success',title:'Mensaje enviado'}); } },
                            error: function(xhr){ Swal.fire({icon:'error',title:'No se pudo enviar el WhatsApp',text:'Error HTTP '+xhr.status}); }
                        });
                    }
                });
            }
        }
    });
}


function openWSConnection(hostname, port, endpoint, mensaje) {

    var webSocketURL = hostname + endpoint;

    console.log("openWSConnection::Connecting to: " + webSocketURL);
    try {
        webSocket = new WebSocket(webSocketURL);
        webSocket.onopen = function (openEvent) {

            //console.log("WebSocket OPEN: " + JSON.stringify(openEvent, null, 4));
            //var obj = JSON.parse('{ "name":"John", "age":30, "city":"New York"}');
            webSocket.send(mensaje);
            webSocket.close();
        };
        webSocket.onclose = function (closeEvent) {
            //console.log("WebSocket CLOSE: " + JSON.stringify(closeEvent, null, 4));
            Swal.fire({
                text: "El mensaje fue enviado con exito!"
            });

            document.getElementById("mensaje").value = "";

            $.post("../ajax/venta.php?op=listarMensajes&idventa=" + document.getElementById("idventa").value, function (r) {

                $("#tbmensajes").html(r);
            });
        };
        webSocket.onerror = function (errorEvent) {
            //console.log("WebSocket ERROR: " + JSON.stringify(errorEvent, null, 4));
        };
        webSocket.onmessage = function (messageEvent) {
            var wsMsg = messageEvent.data;
            // if(wsMsg=="ok")

        };
    } catch (exception) {
        console.error(exception);
    }
}


init();