
$("#formMensajeByExcel").on("submit", function(e) {
    e.preventDefault();
    var formData = new FormData();
    formData.append('titulo', $('#_titulo').val());
    formData.append('mensaje', $('#_mensaje').val());
    formData.append('mensajes',  $('#mensajes')[0].files[0]);
    formData.append('_id',  $('#_id').val());

    $.ajax({
        url: "../ajax/subirarchivo.php",
        type: "post",
        dataType: "html",
        data: formData,
        cache: false,
        contentType: false,
        processData: false
    })
        .done(function(res){
            alert("Mensaje Creado con Exito");
            window.location.reload();
        });
});



var tabla;

//funcion que se ejecuta al inicio
function init(){
    $("#tbclientesByExcel").hide()
    mostrarform(false);
    listar();

    $("#formulario").on("submit",function(e){
        guardaryeditar(e);
    })
}

//funcion mostrar formulario
function mostrarform(flag){
    limpiar();
    if(flag){
        $("#listadoregistros").hide();

        if($('#_titulo').val() === ""){
            $("#exportarContactosB2B").hide();
        }else{
            $("#exportarContactosB2B").show();

        }
        $("#formularioMensajes").show();
        $("#btnGuardar").prop("disabled",false);
        $("#btnagregar").hide();
        $("#btnAgregarForm").show();
        $("#btnCancelarForm").show();

    }else{
        $("#exportarContactosB2B").hide();
        $("#listadoregistros").show();
        $("#formularioMensajes").hide();
        $("#btnagregar").show();
        $("#btnAgregarForm").hide();
        $("#btnCancelarForm").hide();
    }
}

//funcion limpiar
function limpiar(){

    $("#_titulo").val("");
    $("#_mensaje").val("");
    $("#_codigo").val("");

}

//cancelar form
function cancelarform(){

    var table = $('#tbclientesByExcel').DataTable();
    table.clear().draw();
    $("#tbclientesByExcel_wrapper").hide();
    $("#tbclientesByExcel").hide();
    mostrarform(false);
}

//funcion listar
function listar(){
    tabla=$('#tbllistado').dataTable({
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: '',//definimos los elementos del control de la tabla
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdf'
        ],
        "ajax":
            {
                url:'../ajax/mensajeb2b.php?op=listar',
                type: "get",
                dataType : "json",
                error:function(e){
                    console.log(e.responseText);
                }
            },
        "bDestroy":true,
        "iDisplayLength":5,//paginacion
        "order":[[0,"desc"]]//ordenar (columna, orden)
    }).DataTable();
}

function nuevo(){

    $('#bt').html("Guardar");
    document.getElementById("id").disabled = true;
    document.getElementById('titulo').value="";

    document.getElementById('editar').value="";
    document.getElementById('eliminar').value="";
    document.getElementById('id').value="";
    document.getElementById('mensaje').value="";
    mostrarform(true);

}

function mostrar(id){
    $.ajax({
        url: 'editar.php?id='+id,
        type: 'get',
        dataType : "json",
        async: false,
        error: function(X){
            alert("ha ocurrido un error");
        },
        success: function(respuesta){
            document.getElementById("id").disabled = true;
            document.getElementById('editar').value=1;
            document.getElementById('eliminar').value="";
            document.getElementById('id').value=respuesta[0].id;
           $('#titulo').val(respuesta[0].titulo);
            document.getElementById('mensaje').value=respuesta[0].mensaje;
            $("#tbclientesByExcel").show();
            listarClientes(respuesta[0].id);
            mostrarform(true);
            $('#bt').html("Editar");

        }
    });
}

function listarClientes(id){

    tabla=$('#tbclientesByExcel').dataTable({
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Bfrtip',//definimos los elementos del control de la tabla
        buttons: [

        ],
        "ajax":
            {

                url:'../ajax/mensajeb2b.php?op=listarClientes',
                type: "POST",
                data: {"id" :id},

                error:function(e){
                    console.log(e.responseText);
                }
            },
        "bDestroy":true,
        "iDisplayLength":50,//paginacion
        "order":[[0,"desc"]]//ordenar (columna, orden)
    }).DataTable();


}

function eliminar(id){
    bootbox.confirm("¿Esta seguro de eliminar el mensaje?" , function(result){
        if (result) {
            $.post("../ajax/mensajeb2b.php?op=delete" , {$id : id}, function(e){
                bootbox.alert(e);
                window.location.reload();
            });
        }
    })
}


function exportar(id){
    $(location).attr('href',"../ajax/exports/exportarContactosDeMensajeB2B.php?id="+id);
}

function enviar(id) {
    var r = confirm("Desea enviar los mensajes?");
    if (r === true) {
        $.ajax({
            type: "GET",
            url: 'destinatarios.php?id=' + id,
            success: function(response) {
                wsSendMsjMassiveB2B(globalWS, '8080', '/'+globalNombreEmpresa+'/ws',response,$("#myModal"));
            }

        });
    }
}

init();