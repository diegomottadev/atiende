var tabla;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   })

   //$("#imagenmuestra").hide();
//mostramos los permisos
	$.post("../ajax/usuario.php?op=permisos&id=", function(r){
		$("#permisos").html(r);
	});
	$("#imagen").change(function() { //Cuando el input cambie (se cargue un nuevo archivo) se va a ejecutar de nuevo el cambio de imagen y se verá reflejado.		
		var f = this.files && this.files[0];
		if (!f) { return; }
		if (['image/png','image/jpeg','image/jpg'].indexOf(f.type) === -1) {
			Swal.fire({ icon:'error', title:'Formato no permitido', text:'Solo se aceptan imágenes JPG o PNG.' });
			limpiarImagen();
			return;
		}
		if (f.size > 2 * 1024 * 1024) {
			Swal.fire({ icon:'error', title:'Imagen demasiado grande', text:'El máximo permitido es 2 MB. Elegí una imagen más liviana.' });
			limpiarImagen();
			return;
		}
		readURL(this);
  	});
}

//funcion limpiar
function limpiar(){
	$("#nombre").val("");
    $("#num_documento").val("");
	$("#direccion").val("");
	$("#telefono").val("");
	$("#email").val("");
	$("#cargo").val("");
	$("#login").val("");
	$("#clave").val("");
	$("#imagenmuestra").attr("src","../files/usuarios/user.png").show();
	$("#btnQuitarImagen").hide();
	$("#imagenactual").val("");
	$("#imagen").val("");
	$("#idusuario").val("");
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){
		$("#btnCancel").show();
		$("#listadoregistros").hide();
		$("#filtrosUsuario").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
		$('#ribbon-text').text('Nuevo Usuario');
	}else{
		$("#btnCancel").hide();
		$("#listadoregistros").show();
		$("#filtrosUsuario").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
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
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            document.querySelectorAll('#tbllistado [data-bs-toggle="tooltip"]').forEach(function(el){
                try {
                    var existing = bootstrap.Tooltip.getInstance(el);
                    if (existing) { existing.hide(); existing.dispose(); }
                    new bootstrap.Tooltip(el, {trigger: 'hover', animation: false});
                } catch(e) {}
            });
        },
		"language":lenguajeTable,
		"aProcessing": true,
		"aServerSide": false,// client-side: filtra sobre todo el dataset
		dom: 'Brtip',// sin 'f': buscador nativo reemplazado por #fBuscar
		buttons: [],
		"ajax":
		{
			url:'../ajax/usuario.php?op=listar',
			type: "get",
			dataType : "json",
			dataSrc: "aaData",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":5,
		"order":[[0,"desc"]]
	}).DataTable();

	// --- Filtros client-side ---
	var _buscarTimer = null;
	$("#fBuscar").off("input.usr").on("input.usr", function(){
		var v = $(this).val();
		clearTimeout(_buscarTimer);
		_buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 300);
	});

	$("#fEstado").off("change.usr").on("change.usr", function(){
		// Búsqueda exacta sobre el texto del badge en columna 8
		tabla.column(8).search(this.value, false, false).draw();
	});

	$("#fLimpiar").off("click.usr").on("click.usr", function(){
		$("#fBuscar").val("");
		$("#fEstado").val("");
		tabla.search("").columns().search("").draw();
	});
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
     $("#btnGuardar").prop("disabled",true);
     var editId = $("#idusuario").val();
     var nuevoAvatar = $("#imagenmuestra").attr("src");
     var formData=new FormData($("#formulario")[0]);

     $.ajax({
     	url: "../ajax/usuario.php?op=guardaryeditar",
     	type: "POST",
     	data: formData,
     	contentType: false,
     	processData: false,

     	success: function(datos){
     		// Si edité mi propio perfil, reflejar el avatar nuevo en el topbar al instante
     		if (globalIdUsuario && editId == globalIdUsuario && nuevoAvatar) {
     			$("#topbarAvatar").attr("src", nuevoAvatar);
     		}
     		Swal.fire({
				text: datos
			});
     		mostrarform(false);
     		tabla.ajax.reload();
     	}
     });

     limpiar();
}

function mostrar(idusuario){
	$.post("../ajax/usuario.php?op=mostrar",{idusuario : idusuario},
		function(data,status)
		{
			data=JSON.parse(data);
			mostrarform(true);
			console.log(data);
			$("#nombre").val(data.nombre);
            $("#tipo_documento").val(data.tipo_documento);
            //$("#tipo_documento").selectpicker('refresh');
            $("#num_documento").val(data.num_documento);
            $("#direccion").val(data.direccion);
            $("#telefono").val(data.telefono);
            $("#email").val(data.email);
            $("#cargo").val(data.cargo);
            $("#login").val(data.login);
            $("#clave").val("");  // vacío: solo se actualiza si se escribe una clave nueva
            $("#clave").attr("placeholder", "Dejar vacío para no cambiarla");
			$('#ribbon-text').text('Editar Usuario');
			if (data.imagen != '') {
				$("#imagenmuestra").attr("src","../files/usuarios/"+data.imagen).show();
				$("#imagenactual").val(data.imagen);
			} else {
				$("#imagenmuestra").attr("src","../files/usuarios/user.png").show();
				$("#imagenactual").val("");
			}
			$("#btnQuitarImagen").hide();
            $("#idusuario").val(data.idusuario);


		});
	$.post("../ajax/usuario.php?op=permisos&id="+idusuario, function(r){
	$("#permisos").html(r);
});
}


//funcion para desactivar
function desactivar(idusuario){
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
                $.post("../ajax/usuario.php?op=desactivar", {idusuario : idusuario}, function(e){			
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})
	/*bootbox.confirm("¿Esta seguro de desactivar este dato?", function(result){
		if (result) {
			$.post("../ajax/usuario.php?op=desactivar", {idusuario : idusuario}, function(e){
				bootbox.alert(e);
				tabla.ajax.reload();
			});
		}
	})*/
}

function activar(idusuario){
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
                $.post("../ajax/usuario.php?op=activar", {idusuario : idusuario}, function(e){			
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})
	/*bootbox.confirm("¿Esta seguro de activar este dato?" , function(result){
		if (result) {
			$.post("../ajax/usuario.php?op=activar", {idusuario : idusuario}, function(e){
				bootbox.alert(e);
				tabla.ajax.reload();
			});
		}
	})*/
}

/* conf de la imagen seleccionada */
function readURL(input) {
	if (input.files && input.files[0]) { //Revisamos que el input tenga contenido
	  var reader = new FileReader(); //Leemos el contenido
	  
	  reader.onload = function(e) { //Al cargar el contenido lo pasamos como atributo de la imagen de arriba
		$('#imagenmuestra').attr('src', e.target.result).show();
		$('#btnQuitarImagen').show();
		try { bootstrap.Tooltip.getOrCreateInstance(document.getElementById('btnQuitarImagen'), {trigger:'hover', animation:false}); } catch(err){}
	  }
	  
	  reader.readAsDataURL(input.files[0]);
	}
  }

/* Quitar la imagen seleccionada */
function limpiarImagen(){
	var t = bootstrap.Tooltip.getInstance(document.getElementById('btnQuitarImagen')); if(t){ t.hide(); }
	$("#imagen").val("");
	var actual = $("#imagenactual").val();
	$("#imagenmuestra").attr("src", actual ? "../files/usuarios/"+actual : "../files/usuarios/user.png").show();
	$("#btnQuitarImagen").hide();
}
$(document).on("click", "#btnQuitarImagen", limpiarImagen);

init();