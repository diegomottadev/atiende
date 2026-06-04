var tabla;
var select = ' <select class="float-start form-control-sm" name="filter" id="filter" onchange="filtrar()">  <option value="0">--Seleccionar--</option> <option value="1" selected >Pendientes</option> <option value="2">Aprobados</option> <option value="3">Todos</option> </select>';
//funcion que se ejecuta al inicio
function init(){
    mostrarform(false);
    listar();
    $("#editSolicitudForm").on("submit",function(e){
        guardar(e);
    });
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
        $("#tblSolicitudesMain").hide();
        $("#editSolicitudMain").show();
        $("#editSolicitudTitle").show();
    }else{
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
        "language": {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",            
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",            
        },
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Bfr<"toolbar">tip',//definimos los elementos del control de la tabla
        buttons: [],
        "ajax":
            {
                url:'../ajax/solicitud.php?op=listar&filter=' + 1,
                type: "get",
                dataType : "json",
                error:function(e){
                    console.log(e.responseText);
                }
            },
        "bDestroy":true,
        "iDisplayLength":12,//paginacion
        //"order":[[5,"desc"]]//ordenar (columna, orden)
    }).DataTable();
    $( "#tblSolicitudes_filter" ).append(select);
}

function filtrar() {
    var filter = $('#filter').val(); 
    tabla = $('#tblSolicitudes').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
        "language": lenguajeTable,
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Bfr<"toolbar">tip',//definimos los elementos del control de la tabla
        buttons: [],
        "ajax":
            {
                url: '../ajax/solicitud.php?op=listar&filter=' + filter,
                type: "get",
                dataType: "json",
                error: function (e) {
                    console.log(e.responseText);
                }
            },
        "bDestroy": true,
        "iDisplayLength": 12,//paginacion
        //"order":[[5,"desc"]]//ordenar (columna, orden)
    }).DataTable();
    $( "#tblSolicitudes_filter" ).append(select);
    $("#filter").val(filter);   
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