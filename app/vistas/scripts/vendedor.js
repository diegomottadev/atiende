var tabla;
// Solapa "Clientes": código del vendedor en edición + las dos tablas server-side.
var vendedorActual = '';
var tablaDisp = null;   // clientes disponibles (de otros vendedores / sin vendedor)
var tablaAsig = null;   // clientes ya asignados a este vendedor

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   })

   // Inicializa las tablas de clientes la primera vez que se abre la solapa (evita el
   // bug de anchos a 0 de DataTables dentro de un tab oculto); si ya existen, reajusta.
   $('#tab-clientes-btn').on('shown.bs.tab', function(){
      if(!vendedorActual) return;
      if(!tablaDisp){ tablaDisp = initTablaClientes('#tblClientesDisponibles','disponibles'); }
      else { tablaDisp.columns.adjust(); }
      if(!tablaAsig){ tablaAsig = initTablaClientes('#tblClientesAsignados','asignados'); }
      else { tablaAsig.columns.adjust(); }
   });

   // Buscadores propios (estilo del proyecto), reemplazan al buscador nativo de DataTables.
   wireBuscadorCliente('#fBuscarDisp', function(){ return tablaDisp; });
   wireBuscadorCliente('#fBuscarAsig', function(){ return tablaAsig; });
}

// Conecta un input de búsqueda con su DataTable (resuelta en tiempo de evento) con debounce.
function wireBuscadorCliente(sel, getTabla){
	var t;
	$(sel).on('input', function(){
		var v = this.value;
		clearTimeout(t);
		t = setTimeout(function(){ var tb = getTabla(); if(tb){ tb.search(v).draw(); } }, 300);
	});
}

//funcion limpiar
function limpiar(){
	$("#idvendedor").val("");
	$("#codigo").val("");
	$("#nombre").val("");
	$("#telefono").val("");
	$("#idvendedor").prop('disabled',false);
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){
		// al abrir el form, arrancar siempre en la solapa "Editar Vendedor"
		$('#tab-datos-btn').addClass('active');
		$('#tab-clientes-btn').removeClass('active');
		$('#tab-datos').addClass('show active');
		$('#tab-clientes').removeClass('show active');

		$("#panelLista").hide();
		$("#listadoregistros").hide();
		$("#btnExportar").hide();
		$('#btnCancel').show();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",true);
		$('#formulario').off('input.gd change.gd').on('input.gd change.gd', 'input, select, textarea', function(){ $('#btnGuardar').prop('disabled', false); });
		$("#btnAgregar").hide();
		$('#ribbon-text').text('Nuevo Vendedor');
	}else{
		$('#btnCancel').hide();
		$("#panelLista").show();
		$("#listadoregistros").show();
		$("#formularioregistros").hide();
		$("#btnAgregar").show();
		$("#btnExportar").show();
	}
}

//nuevo registro
function nuevo(){
	mostrarform(true);
	limpiar();
	vendedorActual = '';
	habilitarSolapaClientes(false);   // sin código todavía: no se pueden asignar clientes
	$('#ribbon-text').text('Nuevo Vendedor');
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
		"aProcessing": true,
		"aServerSide": false,
		dom: 'Brtip',
		buttons: [],
		"ajax":
		{
			url:'../ajax/vendedor.php?op=listar',
			type: "get",
			dataType : "json",
			dataSrc: "aaData",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":10,
		"order":[[0,"desc"]]
	}).DataTable();

	// Filtro global con debounce
	var _debounceTimer;
	$("#fBuscar").on("input", function(){
		clearTimeout(_debounceTimer);
		var v = $(this).val();
		_debounceTimer = setTimeout(function(){
			tabla.search(v).draw();
		}, 300);
	});

	// Botón limpiar
	$("#fLimpiar").on("click", function(){
		$("#fBuscar").val("");
		tabla.search("").draw();
	});
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
     $("#btnGuardar").prop("disabled",true);
     var formData=new FormData();
	formData.append("idvendedor", $("#idvendedor").val());
	formData.append("codigo", $("#codigo").val());
	formData.append("telefono", $("#telefono").val());
	formData.append("nombre",  $("#nombre").val());

	$.ajax({
     	url: "../ajax/vendedor.php?op=guardaryeditar",
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

     limpiar();
}

function mostrar(idvendedor){
	$.post("../ajax/vendedor.php?op=mostrar",{idvendedor : idvendedor},
		function(data,status)
		{
			data=JSON.parse(data);
			// Si el backend no encontró el vendedor, avisar en vez de fallar en silencio (form vacío).
			if (!data || !data.codigo) {
				Swal.fire({ icon: 'error', title: 'No se pudo cargar el vendedor', text: 'No se encontró el vendedor (código: ' + idvendedor + ').' });
				return;
			}
			mostrarform(true);
			$('#ribbon-text').text('Editar Vendedor');
			$("#nombre").val(data.nombre);
			$("#telefono").val(data.telefono);
			$("#codigo").val(data.codigo);
			$("#idvendedor").val(data.codigo);
			$("#idvendedor").prop('disabled',true);
			// habilitar la solapa "Clientes" con el vendedor recién cargado
			vendedorActual = data.codigo;
			$('#clientesVendedorTexto').text(data.codigo + ' - ' + (data.nombre || ''));
			habilitarSolapaClientes(true);
		})
}

// ============================================================
//  Solapa "Clientes" — asignar/quitar clientes a este vendedor
// ============================================================

// Habilita/inhabilita la solapa Clientes según haya un vendedor guardado.
function habilitarSolapaClientes(habilitar){
	if(habilitar){
		$('#tab-clientes-btn').removeClass('disabled').prop('disabled', false);
		$('#clientesAvisoNuevo').addClass('d-none');
		$('#clientesVendedorInfo').removeClass('d-none');
		$('#clientesPanel').removeClass('d-none');
		// al cambiar de vendedor, limpiar el filtro previo y recargar las tablas existentes
		$('#fBuscarDisp,#fBuscarAsig').val('');
		if(tablaDisp){ tablaDisp.search('').ajax.reload(); }
		if(tablaAsig){ tablaAsig.search('').ajax.reload(); }
	} else {
		$('#tab-clientes-btn').addClass('disabled').prop('disabled', true);
		$('#clientesAvisoNuevo').removeClass('d-none');
		$('#clientesVendedorInfo').addClass('d-none');
		$('#clientesPanel').addClass('d-none');
	}
}

// Crea una DataTable server-side (paginada, con buscador) para un modo dado.
function initTablaClientes(sel, modo){
	// Ambas tablas ("Disponibles" y "Asignados") se comportan igual: buscador propio,
	// info "Mostrando X a Y de Z" y paginado.
	return $(sel).DataTable({
		"language": lenguajeTable,
		processing: true,
		serverSide: true,
		dom: 'rtip',
		ajax: {
			url: '../ajax/vendedor.php?op=listarClientesVendedor',
			type: 'GET',
			data: function(d){ d.vendedor = vendedorActual; d.modo = modo; },
			error: function(e){ console.log(e.responseText); }
		},
		columnDefs: [{ targets: 0, orderable: false, searchable: false }],
		order: [[1,'desc']],
		iDisplayLength: 5,
		destroy: true
	});
}

// Refresca ambas tablas conservando la página actual.
function refrescarTablasClientes(){
	if(tablaDisp){ tablaDisp.ajax.reload(null,false); }
	if(tablaAsig){ tablaAsig.ajax.reload(null,false); }
}

function asignarClienteVendedor(codigoCliente){
	$.post("../ajax/vendedor.php?op=asignarCliente", {codigoCliente: codigoCliente, codigo: vendedorActual}, function(e){
		refrescarTablasClientes();
		Swal.fire({ toast:true, position:'top-end', timer:1800, showConfirmButton:false, icon:'success', title: e });
	});
}

function quitarClienteVendedor(codigoCliente){
	$.post("../ajax/vendedor.php?op=quitarCliente", {codigoCliente: codigoCliente, codigo: vendedorActual}, function(e){
		refrescarTablasClientes();
		Swal.fire({ toast:true, position:'top-end', timer:1800, showConfirmButton:false, icon:'success', title: e });
	});
}


//funcion para desactivar
function desactivar(idvendedor){
	Swal.fire({
		title:'',
		text: '¿Esta seguro de desactivar este dato?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#727cf5',
		cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
		confirmButtonText: 'Aceptar'
	  }).then((result) => {	
            if (result.isConfirmed) {
                $.post("../ajax/vendedor.php?op=desactivar", {idvendedor : idvendedor}, function(e){
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})
}

function activar(idvendedor){
	Swal.fire({
		title:'',
		text: '¿Esta seguro de activar este dato?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#727cf5',
		cancelButtonColor: '#fa5c7c',
        cancelButtonText: 'Cancelar',
		confirmButtonText: 'Aceptar'
	  }).then((result) => {	
            if (result.isConfirmed) {
                $.post("../ajax/vendedor.php?op=activar" , {idvendedor : idvendedor}, function(e){
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})	
}

function exportarVendedores(){
	$(location).attr('href',"../ajax/exports/exportarVendedores.php");
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
				$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/vendedores-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
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
			$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/vendedores-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
		});
	});
});


init();