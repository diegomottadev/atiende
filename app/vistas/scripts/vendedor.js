var tabla;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   })
}

//funcion limpiar
function limpiar(){
	$("#idvendedor").val("");
	$("#nombre").val("");
	$("#telefono").val("");
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){

		$("#subirarchivo").hide();
		$("#listadoregistros").hide();
		$("#filtrosVendedor").hide();
		$("#btnExportar").hide();
		$('#btnCancel').show();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnAgregar").hide();
	}else{
		$('#btnCancel').hide();
		$("#subirarchivo").show();
		$("#listadoregistros").show();
		$("#filtrosVendedor").show();
		$("#formularioregistros").hide();
		$("#btnAgregar").show();
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
			mostrarform(true);
			$("#nombre").val(data.nombre);
			$("#telefono").val(data.telefono);
			$("#idvendedor").val(data.codigo);
			$("#idvendedor").prop('disabled',true)
		})
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