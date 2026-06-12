var tabla;
var _debounceMotivo;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   //cargamos los items al celect categoria
   $.post("../ajax/motivo.php?op=selectArea", function(r){
   		$("#idarea").html(r);
		$("#idarea").val("");
		$("#idarea").trigger('change');
   });


   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   });
   document.getElementById('bloquea').style.display='none';

   // buscador global con debounce
   $("#fBuscarMotivo").on("input", function(){
       clearTimeout(_debounceMotivo);
       var v = $(this).val();
       _debounceMotivo = setTimeout(function(){ tabla.search(v).draw(); }, 300);
   });

   // limpiar filtros
   $("#fLimpiarMotivo").on("click", function(){
       $("#fBuscarMotivo").val("");
       tabla.search("").draw();
   });
}

//funcion limpiar
function limpiar(){
	$("#id").val("");
	$("#codigo").val("");
	$("#motivo").val("");
	$("#idarea").val("");
	$("#idarea").trigger('change');
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){
		$("#btnCancel").show();
		$("#listadoregistros").hide();
		$("#filtrosMotivo").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",true);
		$('#formulario').off('input.gd change.gd').on('input.gd change.gd', 'input, select, textarea', function(){ $('#btnGuardar').prop('disabled', false); });
		$("#btnAgregar").hide();
		$('#ribbon-text').text('Nuevo Motivo');
	}else{
		$("#btnCancel").hide();

		$("#listadoregistros").show();
		$("#filtrosMotivo").show();
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
	tabla=$('#tbllistado').DataTable({
		drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
		"language": lenguajeTable,
		"processing": true,
		"serverSide": false,
		"dom": 'lrtip',
		"ajax":
		{
			url:'../ajax/motivo.php?op=listarp',
			type: "get",
			dataType : "json",
			dataSrc: "data",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"destroy": true,
		"deferRender": true,
		/*	"iDisplayLength":12,//paginacion
        "order":[[0,"desc"]]//ordenar (columna, orden)		*/
	});

}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
     $("#btnGuardar").prop("disabled",true);
     var formData=new FormData($("#formulario")[0]);	
     $.ajax({
     	url: "../ajax/motivo.php?op=guardaryeditar",
     	type: "POST",
     	data: formData,
     	contentType: false,
     	processData: false,

     	success: function(datos){
			Swal.fire({
				/*icon: 'error',*/
				html: datos
			});
     		//bootbox.alert(datos);
     		mostrarform(false);
     		tabla.ajax.reload();
     	}
     });

     limpiar();
}

function mostrar(id){
	
	$.post("../ajax/motivo.php?op=mostrar",{id : id},
		function(data,status)
		{
			data=JSON.parse(data);
			mostrarform(true);
			$('#ribbon-text').text('Editar Motivo');
            $("#id").val(data.id);
			$("#codigo").val(data.opcionId);
			$("#idmenu").val(data.opcionId);
			$("#motivo").val(data.opcion);
			$("#idarea").val(data.area);
			$("#idarea").trigger('change');
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
		  console.log(result);
		if (result.isConfirmed) {
			$.post("../ajax/motivo.php?op=eliminar", {id : id }, function(e){
				console.log(e);
				//bootbox.alert(e);
				Swal.fire({                    
                    text: e
                });
				tabla.ajax.reload();
			});
		 
		}
	  })
	
}


init();