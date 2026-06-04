var tabla;

//funcion que se ejecuta al inicio
function init(){

   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   });
   document.getElementById('bloquea').style.display='none';
}

//funcion limpiar
function limpiar(){

	$("#nombre").val("");
	$("#telefono").val("");
	$("#idpersona").val("");
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){
		$("#btnCancel").show();
		$("#listadoregistros").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnAgregar").hide();
		$('#ribbon-text').text('Nuevo Sector');
	}else{
		$("#btnCancel").hide();
		$("#listadoregistros").show();
		$("#formularioregistros").hide();
		$("#btnAgregar").show();
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
			url:'../ajax/area.php?op=listarp',
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
     	url: "../ajax/area.php?op=guardaryeditar",
     	type: "POST",
     	data: formData,
     	contentType: false,
     	processData: false,

     	success: function(datos){
			Swal.fire({				
				html: datos
			});
     		mostrarform(false);
     		tabla.ajax.reload();
     	}
     });

     limpiar();
}

function mostrar(idpersona){
	
  $.post("../ajax/area.php?op=mostrar",{idpersona : idpersona},
		function(data,status)
		{
		
			data=JSON.parse(data);
			 mostrarform(true);
			$('#ribbon-text').text('Editar Sector');
			$("#nombre").val(data.area);
			$("#telefono").val(data.telefono);
			$("#idpersona").val(data.id);
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
                $.post("../ajax/area.php?op=eliminar", {idpersona : idpersona }, function(e){
					Swal.fire({                    
						text: e
					});
					tabla.ajax.reload();
				});	 
            }
	})
	/*bootbox.confirm("¿Esta seguro de eliminar este dato?", function(result){
		if (result) {

			$.post("../ajax/area.php?op=eliminar", {idpersona : idpersona }, function(e){
				Swal.fire({                    
					text: e
				});
				tabla.ajax.reload();
			});
		}
	})*/
}


init();