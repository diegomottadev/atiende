var tabla;
//funcion que se ejecuta al inicio
function init(){
    mostrarform(false);
    listar();
    $("#editSolicitudForm").on("submit",function(e){
        guardar(e);
    });

    // --- Filtros profesionales (server-side: la DB busca/filtra sobre TODO el dataset) ---
    function filtrarColumna(idx, val){ tabla.column(idx).search(val || '').draw(); } // exact match en el server
    var buscarTimer;
    $('#fBuscar').on('keyup input', function(){
        var v = this.value;
        clearTimeout(buscarTimer);
        buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 350); // debounce
    });
    $('#fLocalidad').on('change', function(){ filtrarColumna(4, this.value); });
    $('#fEstado').on('change',    function(){ filtrarColumna(7, this.value); });
    $('#fLimpiar').on('click', function(){
        $('#fBuscar').val('');
        $('#fLocalidad,#fEstado').val('');
        tabla.search('').columns([4,7]).search('').draw();
    });
    // Poblar dropdowns con los valores distintos (desde el server, no solo la página visible)
    $.get('../ajax/solicitud.php?op=filtros', function(r){
        try { if (typeof r === 'string') r = JSON.parse(r); } catch(e){ return; }
        var $loc = $('#fLocalidad');
        if ($loc.length) {
            $loc.find('option:not(:first)').remove();
            (r.localidad || []).forEach(function(v){ if (v !== null && String(v).trim() !== '') $loc.append($('<option>').attr('value', v).text(v)); });
        }
        var $est = $('#fEstado');
        if ($est.length) {
            $est.find('option:not(:first)').remove();
            (r.estado || []).forEach(function(o){ $est.append($('<option>').attr('value', o.v).text(o.t)); });
        }
    }, 'json');
}

//funcion limpiar
function limpiar(){
    //
    $("#nombre").val("");
    $("#direccion").val("");
    $("#localidad").val("");
    $("#codigo").val("");
    $("#ramo").val("");
    $("#telefono").val("");
    $("#zona").val("");
    $("#lista").val(99999999);
    $("#deposito").val(1);
    $("#vendedor").val(1);
    $("#latitud").val("");
    $("#longitud").val("");
    $("#codSolicitud").val("");
    $("#cuit").val("");
    $("#editSolicitudMain").val("");
}

//funcion mostrar formulario
function mostrarform(flag){
    limpiar();
    if(flag){
        $('#btnCancel').show();
        $("#filtrosSolicitud").hide();
        $("#tblSolicitudesMain").hide();
        $("#editSolicitudMain").show();
        $("#editSolicitudTitle").show();
    }else{
        $("#filtrosSolicitud").show();
        $("#tblSolicitudesMain").show();
        $("#editSolicitudMain").hide();
        $("#editSolicitudTitle").hide();
        $('#btnCancel').hide();
    }
}

//cancelar form
function cancelarform(){
    limpiar();
    mostrarform(false);
}

//funcion listar
function listar(){
    tabla=$('#tblSolicitudes').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
        "language": lenguajeTable,
        "columnDefs":[
            { "orderable": false, "targets": 0 }
        ],
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,// server-side: la DB hace búsqueda/orden/paginado → escala a millones de filas
        dom: 'Brtip',//sin 'f' (usamos buscador propio)
        buttons: [],
        "ajax":
            {
                url:'../ajax/solicitud.php?op=listar',
                type: "post",
                dataType : "json",
                error:function(e){
                    console.log(e.responseText);
                }
            },
        "bDestroy":true,
        "iDisplayLength":12,//paginacion
        "order":[[6,"desc"]]//ordenar por fecha (col 6) desc
    }).DataTable();
}
//funcion para guardaryeditar
function guardar(e){
    e.preventDefault();//no se activara la accion predeterminada

    var formData = new FormData();
    formData.append("id",$("#id").val());
    formData.append("nombre",$("#nombre").val());
    formData.append("direccion",$("#direccion").val());
    formData.append("localidad",$("#localidad").val());
    formData.append("codigo",$("#codigo").val());
    formData.append("ramo",$("#ramo").val());
    formData.append("zona",$("#zona").val());
    formData.append("lista",$("#lista").val());
    formData.append("vendedor",$("#vendedor").val());
    formData.append("latitud",$("#latitud").val());
    formData.append("longitud", $("#longitud").val());
    formData.append("telefono", $("#telefono").val());
    formData.append("cuit",$("#cuit").val());
    formData.append("deposito",$("#deposito").val());
    $.ajax({
        url: "../ajax/solicitud.php?op=guardarNuevoCliente",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos){
            var empresa= globalNombreEmpresa;
            var data=JSON.parse(datos);
            if (data.status === 200){
                var mensaje=mostrarSaludo()+" *"+$("#nombre").val()+"* , tenemos  novedades de su solicitud:\n";
                mensaje+="*Codigo cliente asignado:* "+ $("#codigo").val()+"\n\n" +
                    "*Pasos a seguir :)* \n" +
                    "1) Escribirnos nuevamente al chat \n"+
                    "2) Elija la opción *1* (Ya soy cliente) \n"+
                    "3) Ingresa el codigo del cliente asignado \n";

                Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
                $.post('../ajax/send_wa.php', { to: $("#telefono").val(), text: mensaje }, function(res){
                    try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) { res = null; }
                    if (res && res.ok === false) { Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text: res.error || 'Error desconocido' }); }
                    else { Swal.fire({ icon:'success', title:'Mensaje enviado' }); }
                }).fail(function(xhr){ Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:'Error HTTP '+xhr.status }); });

                limpiar();                
                                mostrarform(false);
                tabla.ajax.reload();
            }
            else{
                Swal.fire({                    
                    text: data.error
                });                
            }
            //openWSConnection('www.atiende.lat','8080','/'+empresa+'/ws',JSON.stringify(json));
        }
    });
}

function asignar(id){
    $.post("../ajax/solicitud.php?op=mostrar",{id : id},
        function(data,status)
        {
            data=JSON.parse(data);
            mostrarform(true);
            $("#id").val(data.id);
            $("#nombre").val(data.nombre);
            $("#direccion").val(data.direccion);
            $("#localidad").val(data.localidad);
            $("#whatsapp").val(data.telefono);
            $("#telefono").val(data.telefono);
            $("#latitud").val(data.latitud);
            $("#longitud").val(data.longitud);
            $("#cuit").val(data.cuit);
            $("#vendedor").val(1);
            $("#lista").val(1);
            $("#deposito").val(1);

            $.get("../ajax/solicitud.php?op=nextCodigo", function(resp){
                var r = JSON.parse(resp);
                $("#codigo").val(r.codigo);
            });
        })
}


//funcion para desactivar
function eliminar(id){
    Swal.fire({
		title:'',
		text: '¿Esta seguro de eliminar este dato?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#727cf5',
		cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
		confirmButtonText: 'Aceptar'
	  }).then((result) => {	
            if (result.isConfirmed) {
                $.post("../ajax/solicitud.php?op=eliminar", {id : id }, function(e){
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})    
}



function mostrarSaludo(){
    var	ahora=new Date();
    var hora=ahora.getHours();
    var texto="";
    if(hora<12){
        texto="Buenos Días";

    }
    if(hora>12 && hora<18){
        texto="Buenas Tardes";

    }
    if(hora>18 && hora<24){
        texto="Buenas Noches";
    }

    return texto;

}

init();