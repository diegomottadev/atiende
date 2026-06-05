var tabla;

//funcion que se ejecuta al inicio
function init(){
    mostrarConsultaFormulario(false);
    listar();

    $("#formRespuestaConsulta").on("submit",function(e){
        guardarEditarConsulta(e);
    });
    document.getElementById('bloquea').style.display='none';
}

function exportarConsultas(){

    $(location).attr('href',"../ajax/aExcel.php?estado=");
}

//funcion limpiar
function limpiar(){

    $("#resolucion").val("");
    $("#idconsulta").val("");
    $("#fechaHora").text("");
    $("#cliente").text("");
    $("#telefono").text("");
    $("#nick").text("");
    $("#motivo").text("");
    $("#detalleMotivo").text("");
    //$("#area").val("");

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
}

//funcion mostrar formulario
function mostrarConsultaFormulario(flag){
    limpiar();
    if(flag){
        $("#tablaConsultas").hide();
        $("#filtrosConsulta").hide();
        $("#btnCancel").show();
        $("#formRespuestasConsultas").show();
        $("#btnGuardar").prop("disabled",false);
        $("#btnagregar").hide();
        $("#btnExportar").hide()
    }else{
        $("#btnCancel").hide();
        $("#tablaConsultas").show();
        $("#filtrosConsulta").show();
        $("#formRespuestasConsultas").hide();
        $("#btnagregar").show();
        $("#btnExportar").show();
    }
}

//cancelar form
function cancelarform(){
    limpiar();
    mostrarConsultaFormulario(false);
}

//funcion listar
function listar(){
    tabla=$('#tblConsultas').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
        "language": lenguajeTable,
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,// server-side: la DB hace búsqueda/orden/paginado → escala a millones de filas
        buttons: [],//paginacion y filrado realizados por el server
        dom: 'Brtip',//sin 'f' (usamos buscador propio)
        "columnDefs": [
            { "orderable": false, "targets": 0 },// col 0 = acciones, no ordenable
            {
                "targets": [ 10 ],
                "visible": false,
                "searchable": false
            }
        ],
        "ajax":
            {
                url:'../ajax/consulta.php?op=listarp',
                type: "post",// POST: los params de DataTables van por body (no en la URL)
                dataType : "json",
                error:function(e){

                }
            },
        "bDestroy":true,
        "iDisplayLength":10,//paginacion
        "bAutoWidth": false,
        "order":[[2,"desc"]],//ordenar por N° Con (consultaId) descendente,
        "createdRow": function (row, data, dataIndex, cells) {
            if ( data[10]>0 )
            {
                $(row).addClass('selected');
            }
        }

    }).DataTable();

    $('#tblConsultas tr').css('height', '10px');

    // --- Filtros profesionales (server-side: la DB busca/filtra sobre TODO el dataset) ---
    function filtrarColumna(idx, val){ tabla.column(idx).search(val || '').draw(); } // exact match en el server
    var buscarTimer;
    $('#fBuscar').off('keyup input').on('keyup input', function(){
        var v = this.value;
        clearTimeout(buscarTimer);
        buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 350); // debounce: no pegar al server por cada tecla
    });
    $('#fEstado').off('change').on('change', function(){ filtrarColumna(1, this.value); });
    $('#fArea').off('change').on('change',   function(){ filtrarColumna(8, this.value); });
    $('#fLimpiar').off('click').on('click', function(){
        $('#fBuscar').val('');
        $('#fEstado,#fArea').val('');
        tabla.search('').columns([1,8]).search('').draw();
    });
    // Poblar los dropdowns con los valores distintos (desde el server, no solo la página visible)
    $.get('../ajax/consulta.php?op=filtros', function(r){
        try { if (typeof r === 'string') r = JSON.parse(r); } catch(e){ return; }
        var $e = $('#fEstado');
        if ($e.length) {
            $e.find('option:not(:first)').remove();
            (r.estado || []).forEach(function(v){ if (v !== null && String(v).trim() !== '') $e.append($('<option>').attr('value', v).text(v)); });
        }
        var $a = $('#fArea');
        if ($a.length) {
            $a.find('option:not(:first)').remove();
            (r.area || []).forEach(function(o){ if (o && o.id != null) $a.append($('<option>').attr('value', o.id).text(o.nombre)); });
        }
    }, 'json');
}
//funcion para guardaryeditar
function guardarEditarConsulta(e){
    e.preventDefault();//no se activara la accion predeterminada
    //$("#btnGuardar").prop("disabled",true);
    var formData=new FormData($("#formRespuestaConsulta")[0]);
    $.ajax({
        url: "../ajax/consulta.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,

        success: function(datos){ console.log(datos);
            //
            var empresa= globalNombreEmpresa;
            //-----------------------------------------------------------------
            var rates = document.getElementsByName('estado');
            var _estado;
            for(var i = 0; i < rates.length; i++){
                if(rates[i].checked){
                    _estado = rates[i].value;
                }
            }

            var mensaje=mostrarSaludo()+" *"+ $("#nick").text() +"* , tenemos  novedades de su consulta:\n";
            mensaje+="*Consulta N°:* "+document.getElementById('idconsulta').value+"\n"+
                "*Motivo:* "+ $("#motivo").text() +"\n"+
                "*Fecha:* "+ $("#fechaHora").text()  +"\n"+
                "*Estado:* "+_estado+"\n"+
                "*Resolucion:* "+document.getElementById('resolucion').value+"\n\n";

            if(_estado !== "Finalizado")
                // mensaje+=`Para responder a la empresa hace clic aquí: ${globalUrl}/${globalNombreEmpresa}/ws/m/respc.php?idconsulta=${document.getElementById('idconsulta').value}`;
                mensaje+=`Para responder a la empresa hace clic aquí: ${globalUrl}/ws/m/respc.php?idconsulta=${document.getElementById('idconsulta').value}`;


            var to = $("#telefono").text();
            var postData = { to: to, text: mensaje };
            if (_estado === 'En analisis') { postData.interactive = '1'; postData.subtype = 'consulta'; }
            Swal.fire({ title:'Enviando mensaje...', allowOutsideClick:false, showConfirmButton:false, didOpen:function(){ Swal.showLoading(); } });
            $.post('../ajax/send_wa.php', postData, function(res){
                try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) { res = null; }
                if (!res || res.ok !== true) {
                    Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:(res && res.error) ? res.error : 'Error desconocido al enviar el mensaje.' });
                } else {
                    Swal.fire({ icon:'success', title:'Mensaje enviado', text: datos });
                }
            }).fail(function(xhr){
                Swal.fire({ icon:'error', title:'No se pudo enviar el WhatsApp', text:'Error HTTP '+xhr.status+' al llamar send_wa.php' });
            });
            limpiar();
            mostrarConsultaFormulario(false);
            tabla.ajax.reload();
        }
    });


}

function mostrarConsulta(idConsulta){
    $("#btnExportar").hide();
    $.post("../ajax/consulta.php?op=mostrar",{idconsulta : idConsulta},
        function(data,status)
        {
            data=JSON.parse(data);
            mostrarConsultaFormulario(true);

            $("#idconsulta").val(data.consultaId);
            $("#fechaHora").text(data.fecha_ingreso);
            $("#cliente").text(data.clienteId);
            $("#telefono").text(data.telefono);
            $("#nick").text(data.nick);
            $("#motivo").text(data.motivo);
            $("#detalleMotivo").text(data.detalle);
            //$("#area").val(data._area);
            //$("#resolucion").val(data.resolucion);

	        if(data.estado==="Finalizado")
                $("#btnGuardar").prop("disabled",true);

            var rates = document.getElementsByName('estado');
            for(var i = 0; i < rates.length; i++){

                if(rates[i].value==data.estado){
                    rates[i].checked=true;
                }else{
                    rates[i].checked=false;
                }
            }

            $.post("../ajax/consulta.php?op=listarMensajes&idconsulta="+data.consultaId,function(r){
                $("#tblMensajeConsulta").html(r);
            });
        })
}

function eliminarConsulta(idpersona){
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
				$.post("../ajax/consulta.php?op=eliminar", {idpersona : idpersona }, function(e){				
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

function PadLeft(value, length) {
    return (value.toString().length < length) ? PadLeft("0" + value, length) :
        value;
}

init();