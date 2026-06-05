var tabla;
var _debounceMotCons;

//funcion que se ejecuta al inicio
function init(){
    mostrarform(false);
    listar();

    //cargamos los items al celect categoria
    $.post("../ajax/motivoConsulta.php?op=selectArea", function(r){
        $("#idarea").html(r);
        $("#idarea").val("");
		$("#idarea").trigger('change');
    });


    $("#formulario").on("submit",function(e){
        guardaryeditar(e);
    });
    document.getElementById('bloquea').style.display='none';

    // buscador global con debounce
    $("#fBuscarMotivoConsulta").on("input", function(){
        clearTimeout(_debounceMotCons);
        var v = $(this).val();
        _debounceMotCons = setTimeout(function(){ tabla.search(v).draw(); }, 300);
    });

    // limpiar filtros
    $("#fLimpiarMotivoConsulta").on("click", function(){
        $("#fBuscarMotivoConsulta").val("");
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
        $('#btnCancelar').show();
        $("#listadoregistros").hide();
        $("#filtrosMotivoConsulta").hide();
        $("#formularioregistros").show();
        $("#btnGuardar").prop("disabled",false);
        $("#btnAgregar").hide();
        $('#ribbon-text').text('Nuevo Motivo');

    }else{

        $('#btnCancelar').hide();
        $("#listadoregistros").show();
        $("#filtrosMotivoConsulta").show();
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
                url:'../ajax/motivoConsulta.php?op=listarp',
                type: "get",
                dataType : "json",
                dataSrc: "aaData",
                error:function(e){
                    console.log(e.responseText);
                }
            },
        "destroy": true,
        //"iDisplayLength":12,//paginacion
        //"order":[[0,"desc"]]//ordenar (columna, orden)
    });
}
//funcion para guardaryeditar
function guardaryeditar(e){
    e.preventDefault();//no se activara la accion predeterminada
    $("#btnGuardar").prop("disabled",true);
    var formData=new FormData($("#formulario")[0]);

    $.ajax({
        url: "../ajax/motivoConsulta.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,

        success: function(datos){
            Swal.fire({
				/*icon: 'error',*/
				html: datos
			});
            mostrarform(false);
            $("#idarea").val("");
	        $("#idarea").trigger('change');
            tabla.ajax.reload();
        }
    });

    limpiar();
}

function mostrar(id){
    $.post("../ajax/motivoConsulta.php?op=mostrar",{id : id},
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
            if (result.isConfirmed) {
                $.post("../ajax/motivoConsulta.php?op=eliminar", {id : id }, function(e){				
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})
    /*bootbox.confirm("¿Esta seguro de eliminar este dato?", function(result){
        if (result) {

            $.post("../ajax/motivoConsulta.php?op=eliminar", {id : id }, function(e){
                bootbox.alert(e);
                tabla.ajax.reload();
            });
        }
    })*/
}


init();