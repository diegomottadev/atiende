var tabla;
var tabla1;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   });
   document.getElementById('bloquea').style.display='none';

   // --- Filtros profesionales (server-side: la DB busca/filtra sobre TODO el dataset) ---
   function filtrarColumna(idx, val){ tabla.column(idx).search(val || '').draw(); } // exact match en el server
   var buscarTimer;
   $('#fBuscar').on('keyup input', function(){
      var v = this.value;
      clearTimeout(buscarTimer);
      buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 350); // debounce: no pegar al server por cada tecla
   });
   $('#fEstado').on('change', function(){ filtrarColumna(1, this.value); }); // col 1 → estado
   $('#fArea').on('change',   function(){ filtrarColumna(8, this.value); }); // col 8 → area (id)
   $('#fMotivo').on('change', function(){ filtrarColumna(7, this.value); }); // col 7 → motivo
   $('#fLimpiar').on('click', function(){
      $('#fBuscar').val('');
      $('#fEstado,#fArea,#fMotivo').val('');
      tabla.search('').columns([1,7,8]).search('').draw();
   });
   // Poblar los dropdowns con los valores distintos (desde el server, no solo la página visible)
   $.get('../ajax/reclamo.php?op=filtros', function(r){
      try { if (typeof r === 'string') r = JSON.parse(r); } catch(e){ return; }
      function fill(sel, arr){
         var $s = $(sel); if (!$s.length) return;
         $s.find('option:not(:first)').remove();
         (arr || []).forEach(function(v){ if (v !== null && String(v).trim() !== '') $s.append($('<option>').attr('value', v).text(v)); });
      }
      // Area: pares {id, area} → value=id (lo que filtra), texto=nombre
      function fillObj(sel, arr, valKey, txtKey){
         var $s = $(sel); if (!$s.length) return;
         $s.find('option:not(:first)').remove();
         (arr || []).forEach(function(o){ if (o && o[valKey] !== null && String(o[valKey]).trim() !== '') $s.append($('<option>').attr('value', o[valKey]).text(o[txtKey])); });
      }
      fill('#fEstado', r.estado);
      fillObj('#fArea', r.area, 'id', 'area');
      fill('#fMotivo', r.motivo);
   }, 'json');
}



function aExcel(){

$(location).attr('href',"../ajax/aExcel.php?estado=");
 //location.href = "../ajax/excel.php?estado=");
}

//funcion limpiar
function limpiar(){

	$("#resolucion").val("")
	$("#idreclamo").val("");
	$("#fechaHora").text("");
	$("#cliente").text("");
	$("#telefono").text("");
	$("#nick").text("");
	$("#motivo").text("");
	$("#detalleMotivo").text("");
	//$("#area").val("");
			
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();

	if(flag){
		$('#btnCancel').show();
		$("#listadoregistros").hide();
		$("#filtrosReclamo").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
		$("#btnExportar").hide();

	}else{
		$('#btnCancel').hide();

		$("#listadoregistros").show();
		$("#filtrosReclamo").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
		$("#btnExportar").show();

	}
}

//cancelar form
function cancelarform(){
	limpiar();
	mostrarform(false);
}

//funcion listar
function listar(){
	tabla=$('#tbllistado').dataTable({
		drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
		"language": lenguajeTable,
		"aProcessing": true,//activamos el procedimiento del datatable
		"aServerSide": true,// server-side: la DB hace búsqueda/orden/paginado → escala a millones de filas
		buttons: [],//paginacion y filrado realizados por el server
		dom: 'Brtip',//sin 'f' (usamos buscador propio)
		"columnDefs": [
            { "orderable": false, "targets": 0 }, // botón editar, no ordenable
            {
                "targets": [ 10 ],
                "visible": false,
                "searchable": false,
                "orderable": false
            }
        ],
		"ajax":
		{
			url:'../ajax/reclamo.php?op=listarp',
			type: "post",// POST: los params de DataTables (columns[]) no entran cómodos en la URL
			dataType : "json",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":10,//paginacion
		"bAutoWidth": false,
		"order":[[2,"desc"]],//ordenar por N° Rec (reclamoId) desc — columna real
		"createdRow": function (row, data, dataIndex, cells) {
			if ( data[10]>0 )
			{
				$(row).addClass('selected');
			}
		}
  
		
	}).DataTable();

	$('#tbllistado tr').css('height', '10px'); 
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
     $("#btnGuardar").prop("disabled",true);
     var formData=new FormData($("#formulario")[0]);

     $.ajax({
     	url: "../ajax/reclamo.php?op=guardaryeditar",
     	type: "POST",
     	data: formData,
     	contentType: false,
     	processData: false,

     	success: function(datos){
			var empresa=globalNombreEmpresa;
			//-----------------------------------------------------------------
		    var rates = document.getElementsByName('estado');
			var _estado;
			for(var i = 0; i < rates.length; i++){
				if(rates[i].checked){
					_estado = rates[i].value;
				}
			}
			// Reclamo activo sin estado marcado → por defecto "En analisis"
			if (!_estado) { _estado = 'En analisis'; }

		  var mensaje=mostrarSaludo()+" *"+ $("#nick").text() +"* , tenemos  novedades de su reclamo:\n";
		  mensaje+="*Reclamo N°:* "+document.getElementById('idreclamo').value+"\n"+
		 "*Motivo:* "+ $("#motivo").text() +"\n"+
		 "*Fecha:* "+ $("#fechaHora").text() +"\n"+
		 "*Estado:* "+_estado+"\n"+
		 "*Resolucion:* "+document.getElementById('resolucion').value+"\n\n";		 
		 
		 if(_estado !== "Finalizado"){
			 //mensaje+="Para responder a la empresa hace clic aquí: "+ globalUrl+"/"+empresa+"/ws/m/resp.php?idreclamo="+document.getElementById('idreclamo').value;
			 mensaje+="Para responder a la empresa hace clic aquí: "+ globalUrl+"/ws/m/resp.php?idreclamo="+document.getElementById('idreclamo').value;
		 }

		  var to = $("#telefono").text();
			var postData = { to: to, text: mensaje };
			if (_estado === 'En analisis') postData.interactive = '1';
			Swal.fire({
				title: 'Enviando mensaje...',
				allowOutsideClick: false,
				showConfirmButton: false,
				didOpen: function() { Swal.showLoading(); }
			});
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
			//------------------------------------------------------------------
			limpiar();
     		mostrarform(false);
     		tabla.ajax.reload();
     	}
     });

     
}

function mostrar(idreclamo){
	$.post("../ajax/reclamo.php?op=mostrar",{idreclamo : idreclamo},
		function(data,status)
		{
			data=JSON.parse(data);
			mostrarform(true);
			
           	$("#idreclamo").val(data.reclamoId);
			$("#fechaHora").text(data.fecha_ingreso);
			$("#cliente").text(data.clienteId);
			$("#telefono").text(data.telefono);
			$("#nick").text(data.nick);
			$("#motivo").text(data.motivo);
			$("#detalleMotivo").text(data.detalle);
			//$("#area").text(data._area);
			// $("#resolucion").val(data.resolucion);
            
			if(data.estado=="Finalizado")
			 $("#btnGuardar").prop("disabled",true);

			// Si el reclamo no tiene estado válido (ej: recién creado por el bot),
			// marcar "En analisis" por defecto para que el operador lo vea.
			var estadoActual = (data.estado === 'En analisis' || data.estado === 'Finalizado') ? data.estado : 'En analisis';
			var rates = document.getElementsByName('estado');
			for(var i = 0; i < rates.length; i++){
				rates[i].checked = (rates[i].value == estadoActual);
			}

			$.post("../ajax/reclamo.php?op=listarMensajes&idreclamo="+data.reclamoId,function(r){
				$("#tbmensajes").html(r);
			});
		})
}
//funcion para desactivar
function eliminar(idpersona){
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
				$.post("../ajax/reclamo.php?op=eliminar", {idpersona : idpersona }, function(e){				
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