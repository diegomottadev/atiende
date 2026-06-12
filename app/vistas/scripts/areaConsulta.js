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
        $("#filtrosAreaConsulta").hide();
        $("#listadoregistros").hide();
        $("#btnCancel").show();
        $("#formularioregistros").show();
        $("#btnGuardar").prop("disabled",true);
        $('#formulario').off('input.gd change.gd').on('input.gd change.gd', 'input, select, textarea', function(){ $('#btnGuardar').prop('disabled', false); });
        $("#btnAgregar").hide();
        $('#ribbon-text').text('Nuevo Sector');
    }else{
        $("#btnCancel").hide();
        $("#filtrosAreaConsulta").show();
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
        "aProcessing": true,
        "aServerSide": false,
        dom: 'Brtip',
        buttons: [],
        "ajax":
            {
                url:'../ajax/areaConsulta.php?op=listarp',
                type: "get",
                dataType : "json",
                dataSrc: "aaData",
                error:function(e){
                    console.log(e.responseText);
                }
            },
        "bDestroy":true,
        "iDisplayLength":10,
        "order":[[1,"asc"]]
    }).DataTable();

    // Filtro global con debounce 300ms
    var buscarTimer;
    $('#fBuscar').on('keyup input', function(){
        var v = this.value;
        clearTimeout(buscarTimer);
        buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 300);
    });

    // Limpiar filtros
    $('#fLimpiar').on('click', function(){
        $('#fBuscar').val('');
        tabla.search('').draw();
    });
}
//funcion para guardaryeditar
function guardaryeditar(e){
    e.preventDefault();//no se activara la accion predeterminada
    $("#btnGuardar").prop("disabled",true);
    var formData=new FormData($("#formulario")[0]);

    $.ajax({
        url: "../ajax/areaConsulta.php?op=guardaryeditar",
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

    $.post("../ajax/areaConsulta.php?op=mostrar",{idpersona : idpersona},
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
                $.post("../ajax/areaConsulta.php?op=eliminar", {idpersona : idpersona }, function(e){
					Swal.fire({                    
						text: e
					});
					tabla.ajax.reload();
				});	 
            }
	})
}


init();