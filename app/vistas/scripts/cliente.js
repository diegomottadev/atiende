var tabla;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   });

   // --- Filtros profesionales (server-side: la DB busca/filtra sobre TODO el dataset) ---
   function filtrarColumna(idx, val){ tabla.column(idx).search(val || '').draw(); } // exact match en el server
   var buscarTimer;
   $('#fBuscar').on('keyup input', function(){
      var v = this.value;
      clearTimeout(buscarTimer);
      buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 350); // debounce
   });
   $('#fVendedor').on('change', function(){ filtrarColumna(2, this.value); });
   $('#fRamo').on('change',     function(){ filtrarColumna(7, this.value); });
   $('#fZona').on('change',     function(){ filtrarColumna(8, this.value); });
   $('#fLista').on('change',    function(){ filtrarColumna(9, this.value); });
   $('#fLimpiar').on('click', function(){
      $('#fBuscar').val('');
      $('#fVendedor,#fRamo,#fZona,#fLista').val('');
      tabla.search('').columns([2,7,8,9]).search('').draw();
   });
   // Poblar los dropdowns con los valores distintos (del server, no solo la página visible)
   $.get('../ajax/persona.php?op=filtros', function(r){
      try { if (typeof r === 'string') r = JSON.parse(r); } catch(e){ return; }
      function fill(sel, arr){
         var $s = $(sel); if (!$s.length) return;
         $s.find('option:not(:first)').remove();
         (arr || []).forEach(function(v){ if (v !== null && String(v).trim() !== '') $s.append($('<option>').attr('value', v).text(v)); });
      }
      fill('#fVendedor', r.vendedor); fill('#fRamo', r.ramo); fill('#fZona', r.zona); fill('#fLista', r.lista);
   }, 'json');
}

//funcion limpiar
function limpiar(){

	$("#codigo").val("");
	$("#vendedor").val("");
	$("#nombre").val("");
	$("#num_documento").val("");
	$("#direccion").val("");
	$("#telefono").val("");
	$("#localidad").val("");
	$("#ramo").val("");
	$("#email").val("");
	$("#lista").val("");
	$("#zona").val("");
	$("#deposito").val("");
	$("#latitud").val("");
	$("#longitud").val("");
	$("#cuil").val("");
	$("#dni").val("");
	$("#idpersona").val("");
}

//funcion mostrar formulario 
function mostrarform(flag){
	limpiar();
	if(flag){

		$('#btnCancel').show();
		$("#listadoregistros").hide();
		$("#panelLista").hide();
		$("#formularioregistros").show();
		
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
		$('#btnExportar').hide();
	}else{
		$('#btnCancel').hide();

		$("#listadoregistros").show();
		$("#panelLista").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
		$('#btnExportar').show();

	}
}

//cancelar form
function cancelarform(){
	limpiar();
	mostrarform(false);
}

// alta de cliente nuevo: form en blanco, código editable
function nuevoCliente(){
	mostrarform(true);                 // limpia y muestra el formulario
	$("#modo").val("nuevo");
	$("#codigo").prop("readonly", false).val("");
	$("#codigoHint").hide();
	$("#ribbon-text").text("Nuevo Cliente");
	$("#codigo").focus();
}

//funcion listar
function listar(){
	tabla=$('#tbllistado').dataTable({
		drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
		"language": lenguajeTable,
		"aProcessing": true,//activamos el procedimiento del datatable
		"aServerSide": true,// server-side real: la DB hace búsqueda/orden/paginado → escala a millones
		dom: 'rtip',//sin 'f' (usamos buscador propio)
		"columnDefs":[{ "orderable": false, "targets": 0 }],//la columna de acciones no se ordena
		"ajax":
		{
			url:'../ajax/persona.php?op=listarc',
			type: "post",// POST: los params de DataTables no entran cómodos en la URL
			dataType : "json",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":10,//paginacion
		"order":[[1,"desc"]]//ordenar por código (col 0 = acciones)
	}).DataTable();
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada
     // En alta, confirmar que los datos son correctos antes de guardar.
     if ($("#modo").val() === 'nuevo') {
         Swal.fire({
             title: 'Confirmar alta',
             text: '¿Estás seguro de que los datos ingresados del cliente son correctos?',
             icon: 'question',
             showCancelButton: true,
             confirmButtonColor: '#727cf5',
             cancelButtonColor: '#fa5c7c',
             cancelButtonText: 'Revisar',
             confirmButtonText: 'Guardar'
         }).then(function (r) { if (r.isConfirmed) { _guardarCliente(); } });
     } else {
         _guardarCliente();
     }
}

function _guardarCliente(){
     $("#btnGuardar").prop("disabled",true);
     var formData=new FormData($("#formulario")[0]);

     $.ajax({
     	url: "../ajax/persona.php?op=guardaryeditar",
     	type: "POST",
     	data: formData,
     	contentType: false,
     	processData: false,

     	success: function(datos){
     		Swal.fire({
				text: datos
			});
     		mostrarform(false);
     		tabla.ajax.reload();
     	}
     });
}

    /*   
    "codigo": "117",
    "vendedor": "GODOY CRUZ",
    "supervisor": "1",
    "razonSocial": "PINI ARMANDO ",
    "direccion": "B° Laprida Alfonsina Storni  N° 758",
    "localidad": "2616406953",
    "ramo": "1",
    "zona": "117",
    "lista": "VERDULERIA"
	*/

function mostrar(idpersona){
	$('#btnExportar').hide();
	$.post("../ajax/persona.php?op=mostrar",{codigo : idpersona},
		function(data,status)
		{
			//alert (data);
			data=JSON.parse(data);
			// Si el backend no encontró el cliente, avisar en vez de fallar en silencio (form vacío).
			if (!data || !data.codigo) {
				Swal.fire({ icon: 'error', title: 'No se pudo cargar el cliente', text: 'No se encontró el cliente (código: ' + idpersona + ').' });
				return;
			}
			mostrarform(true);
			$("#nombre").val(data.razonSocial);
			$("#direccion").val(data.direccion);
			$("#localidad").val(data.localidad);
			$("#ramo").val(data.ramo);
			$("#telefono").val(data.telefono);
			$("#zona").val(data.zona);
			$("#lista").val(data.lista);
			$("#vendedor").val(data.vendedor);
			$("#codigo").val(data.codigo);
			$("#deposito").val(data.deposito);
			$("#latitud").val(data.latitud);
			$("#longitud").val(data.longitud);
			$("#cuil").val(data.cuil);
			$("#dni").val(data.dni);
			// modo edición: el código es la clave del cliente → no se puede cambiar.
			$("#modo").val("editar");
			$("#codigo").prop("readonly", true);
			$("#codigoHint").show();
			$("#ribbon-text").text("Editar Cliente");

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
                $.post("../ajax/persona.php?op=eliminarCliente", {codigo : idpersona }, function(e){				
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})	
}


$(function(){
	$("#formUp").on("submit", function(e){
		e.preventDefault();
		var f = $(this);
		var formData = new FormData(document.getElementById("formUp"));
		formData.append("dato", "valor");
		//formData.append(f.attr("name"), $(this)[0].files[0]);
		$.ajax({
			url: "../ajax/subirarchivo.php",
			type: "post",
			dataType: "html",
			data: formData,
			cache: false,
			contentType: false,
	 		processData: false
		}).then(function(res){

			if(res.includes("Fatal error")){
				$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/clientes-demotracion-exportacion.xlsx" target="_blank"> clientes-demotracion-exportacion.xlsx </a></div>');
				return;
			}
			json = (JSON.parse(res));

			if(json.status == 404){
				$("#mensaje").html('<div class="alert alert-danger" role="alert">'+ json.error +'</div>');
				return;
			}

			$("#mensaje").html('<div class="alert alert-success" role="alert">'+ json.msj +'</div>');
			listar();
		}).catch(function (res) {
			$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/clientes-demotracion-exportacion.xlsx" target="_blank"> clientes-demotracion-exportacion.xlsx </a></div>');
		});
	});
});

function exportarClientes(){
	$(location).attr('href',"../ajax/exports/exportarClientes.php");

}
init();