var tabla;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   });
}

//funcion limpiar
function limpiar(){

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
	$("#idpersona").val("");
}

//funcion mostrar formulario 
function mostrarform(flag){
	limpiar();
	if(flag){

		$('#btnCancel').show();
		$("#listadoregistros").hide();
		$("#subirarchivo").hide();
		$("#formularioregistros").show();
		
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
		$('#btnExportar').hide();
	}else{
		$('#btnCancel').hide();

		$("#listadoregistros").show();
		$("#subirarchivo").show();
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

//funcion listar
function listar(){
	tabla=$('#tbllistado').dataTable({
		drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
		"language": lenguajeTable,
		"aProcessing": true,//activamos el procedimiento del datatable
		"aServerSide": true,//paginacion y filrado realizados por el server
		dom: 'Bfrtip',//definimos los elementos del control de la tabla
		buttons: [
                  'copyHtml5',
                  'excelHtml5',
                  'csvHtml5',
                  'pdf'
		],
		"ajax":
		{
			url:'../ajax/persona.php?op=listarc',
			type: "get",
			dataType : "json",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":10,//paginacion
		"order":[[0,"desc"]]//ordenar (columna, orden)
	}).DataTable();
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
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

     limpiar();
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