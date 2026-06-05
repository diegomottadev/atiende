var tabla;

function init(){
    mostrarform(false);
    listar();

    $("#formRepartidor").on("submit",function(e){
        guardaryeditar(e);
    });

}

function guardaryeditar(e){
    e.preventDefault();//no se activara la accion predeterminada
    //$("#btnGuardar").prop("disabled",true);
    var formData=new FormData($("#formRepartidor")[0]);

    $.ajax({
        url: "../ajax/repartidor.php?op=guardaryeditar",
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



function mostrarform(flag){
    limpiar();

    if(flag){
        $('#btnCancel').show();
        $("#panelRepartidores").hide();
        $("#filtrosRepartidor").hide();
        $("#panelFormRepartidor").show();
        $("#btnGuardar").show();
        $("#btnExportar").hide();
        $("#import-repartidores").hide();
        $("#btnAgregar").hide();
        $('#ribbon-text').text('Nuevo Repartidor');
    }else{
        $('#btnCancel').hide();
        $("#panelRepartidores").show();
        $("#filtrosRepartidor").show();
        $("#panelFormRepartidor").hide();
        $("#btnGuardar").hide();
        $("#btnExportar").show();
        $("#btnAgregar").show();
        $("#import-repartidores").show();
    }
}

function nuevo() {
    mostrarform(true);
    limpiar();

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
                $.post("../ajax/repartidor.php?op=eliminar", {id : id }, function(e){
                    Swal.fire({                    
                        text: e
                    });
                    tabla.ajax.reload();
                });		 
            }
	})    
}

function cancelarform(){
    limpiar();
    mostrarform(false);
}

function mostrar(id){
    $.post("../ajax/repartidor.php?op=mostrar",{id : id},
        function(data,status)
        {
            data=JSON.parse(data);
            mostrarform(true);
            $('#ribbon-text').text('Editar Repartidor');
            $("#id").val(data.id);
            $("#nombre").val(data.nombre);
            $("#telefono").val(data.telefono);
        });
}

function listar(){
    tabla=$('#tblRepartidores').dataTable({
        drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            document.querySelectorAll('#tblRepartidores [data-bs-toggle="tooltip"]').forEach(function(el){
                var existing = bootstrap.Tooltip.getInstance(el);
                if (existing) { existing.hide(); existing.dispose(); }
                new bootstrap.Tooltip(el, {trigger: 'hover'});
            });
        },
        "language": lenguajeTable,
        "aProcessing": true,
        "aServerSide": false,
        dom: 'Brtip',
        buttons: [],
        "ajax":
            {
                url:'../ajax/repartidor.php?op=listar',
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

    // ── Filtro global con debounce ──
    var _debounceTimer = null;
    $('#fBuscar').on('input', function(){
        clearTimeout(_debounceTimer);
        var v = $(this).val();
        _debounceTimer = setTimeout(function(){
            tabla.search(v).draw();
        }, 300);
    });

    $('#fLimpiar').on('click', function(){
        $('#fBuscar').val('');
        tabla.search('').draw();
    });
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
                $("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/repartidores-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
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
            $("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/repartidores-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
        });
    });
});


function exportar(){
    $(location).attr('href',"../ajax/exports/exportarRepartidores.php");
}

function limpiar(){
    $("#id").val("");
    $("#telefono").val("");
    $("#nombre").val("");
}

init();