var tabla;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   })

   //cargamos los items al celect categoria
   $.post("../ajax/articulo.php?op=selectCategoria", function(r){
   	$("#idcategoria").html(r);
   	$("#idcategoria").selectpicker('refresh');
   });
   

   
   $("#imagenmuestra").hide();
}

//funcion limpiar
function limpiar(){
	$("#codigo").val("");
	$("#nombre").val("");
	$("#descripcion").val("");
	$("#stock").val("");
	$("#imagenmuestra").attr("src","");
	$("#imagenactual").val("");
	$("#print").hide();
	$("#idarticulo").val("");
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){
		$("#listadoregistros").hide();
		$("#subirarchivo").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
		$("#btnExportar").hide();
		$('#btnCancel').show();
	}else{
		$('#btnCancel').hide();
		$("#subirarchivo").show();
		$("#listadoregistros").show();
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
		"aServerSide": true,//paginacion y filrado realizados por el server
		dom: 'Bfrtip',//definimos los elementos del control de la tabla
		buttons: [
                  'copyHtml5',
                  'excelHtml5',
                  'csvHtml5',  
				  {
					extend: 'pdf',
					text: 'PDF',
					orientation: 'landscape'
			      }
		],
		"ajax":
		{
			url:'../ajax/articulo.php?op=listar',
			type: "get",
			dataType : "json",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":10,
		"order":[[0,"desc"]]//ordenar (columna, orden)
	}).DataTable();
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
     $("#btnGuardar").prop("disabled",true);
     var formData=new FormData($("#formulario")[0]);
     
     $.ajax({
     	url: "../ajax/articulo.php?op=guardaryeditar",
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

function mostrar(idarticulo){
/*
    "codigo": "623",
    "descripcion": "Melon blanco BRASIL (unidad)",
    "lista1": "200",
    "linea": "FRUTAS",
    "rubro": "A-FRUTAS",
    "capacidad": "1",
    "pack": "1",
    "impInt": "",
    "codBarra": "",
    "calibre": ""
*/

	   $.post("../ajax/articulo.php?op=mostrar",{idarticulo : idarticulo},
		function(data,status)
		{
			data=JSON.parse(data);
			mostrarform(true);

			$("#codigo").val(data.codigo);
			$("#nombre").val(data.descripcion);
			$("#rubro").val(data.rubro);
			$("#linea").val(data.linea);
			$("#calibre").val(data.calibre);
			$("#imagenmuestra").show();
			$("#imagenmuestra").attr("src","../files/articulos/"+data.codigo+".jpg");
			$("#imagenactual").val(data.imagen);
			$("#idarticulo").val(data.codigo);
			generarbarcode();
		})
}


//funcion para desactivar
function desactivar(idarticulo){
	bootbox.confirm("¿Esta seguro de desactivar este dato?", function(result){
		if (result) {
			$.post("../ajax/articulo.php?op=desactivar", {idarticulo : idarticulo}, function(e){
				Swal.fire({                    
					text: e
				});
				tabla.ajax.reload();
			});
		}
	})
}

function activar(idarticulo){
	bootbox.confirm("¿Esta seguro de activar este dato?" , function(result){
		if (result) {
			$.post("../ajax/articulo.php?op=activar" , {idarticulo : idarticulo}, function(e){
				Swal.fire({                    
					text: e
				});
				tabla.ajax.reload();
			});
		}
	})
}

function generarbarcode(){
	codigo=$("#codigo").val();
	JsBarcode("#barcode",codigo);
	$("#print").show();

}

function imprimir(){
	$("#print").printArea();
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
				$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/articulos-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
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
			$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/articulos-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
		});
	});
});

function exportarArticulos(){
	$(location).attr('href',"../ajax/exports/exportarArticulos.php");
}

init();