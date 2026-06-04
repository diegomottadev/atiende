
var tabla;

function mostrar(id){

    $.ajax({
        url: '../ajax/mensajeb2c.php?op=showMessage&id='+id,
        type: 'get',
        dataType : "json",
        async: false,
        error: function(err){
            console.log(err);
        },

        success: function(data){
            document.getElementById("_id").disabled = true;
            document.getElementById('editar').value=1;
            document.getElementById('eliminar').value="";
            document.getElementById('_id').value=data.id;
            document.getElementById('_titulo').value=data.titulo;
            document.getElementById('_mensaje').value=data.mensaje;
            $("#tbclientesByExcel").show();
            listarClientes(data.id);
            mostrarform(true);
            $('#bt').html("Editar");

        }
    });
}


$("#formMensajeByExcel").on("submit", function(e) {
    e.preventDefault();
    var formData = new FormData();
    formData.append('titulo', $('#_titulo').val());
    formData.append('mensaje', $('#_mensaje').val());
    formData.append('contactos',  $('#contactos')[0].files[0]);
    formData.append('_id',  $('#_id').val());

    $.ajax({
            url: "../ajax/telefonosUpload.php",
            type: "post",
            dataType: "html",
            data: formData,
            cache: false,
            contentType: false,
            processData: false
        })
        .done(function(res){
            const data = JSON.parse(res);
            if (data.error) {
                // handle the error
                alert(data.error.msg);
            }else{
                alert(data.result);
                window.location.reload();
            }


        });
});

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
    console.log(flag);
    if(flag){

        $("#listadoregistros").hide();
        $("#formularioMensajes").show();
        $("#btnGuardar").prop("disabled",false);
        $("#btnagregar").hide();
        $("#btnAgregarForm").show();
        $("#btnCancelarForm").show();
        if ($("#_id").val()==="") {$("#_id").hide();}
        $('#exportarContactos').show();
        if($('#_titulo').val() === ""){
            $("#exportarContactos").hide();
        }else{
            $("#exportarContactos").show();

        }
    }else{
        $("#_id").show();
        $("#exportarContactos").hide();
        $('#exportarContactos').hide();
        $("#listadoregistros").show();
        $("#formularioMensajes").hide();
        $("#btnagregar").show();
        $("#btnAgregarForm").hide();
        $("#btnCancelarForm").hide();
    }
}

function cancelarform(){

    var table = $('#tbclientesByExcel').DataTable();
    table.clear().draw();
    $("#tbclientesByExcel_wrapper").hide();
    $("#tbclientesByExcel").hide();
    mostrarform(false);
}

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
                url:'../ajax/mensajeb2c.php?op=listarMensajes',
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


function nuevo(){

    $('#bt').html("Guardar");
    document.getElementById("_id").disabled = true;

    document.getElementById('_titulo').value="";
    document.getElementById('editar').value="";
    document.getElementById('eliminar').value="";
    document.getElementById('_id').value="";
    document.getElementById('_mensaje').value="";
    mostrarform(true);
}



function listarClientes(id){
    //console.log("json="+json);
    tabla=$('#tbclientesByExcel').dataTable({
        "aProcessing": true,//activamos el procedimiento del datatable
        "aServerSide": true,//paginacion y filrado realizados por el server
        dom: 'Bfrtip',//definimos los elementos del control de la tabla
        buttons: [
        ],
        "ajax":
            {
                url:'../ajax/mensajeb2c.php?op=listarContactos',
                type: "POST",
                data: {"mensajebtc_id" :id},
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
            $.post("../ajax/mensajeb2c.php?op=deleteMessage" , {id_eliminar : id}, function(e){
                bootbox.alert(e);
                 window.location.reload();
            });
        }
    })
}

function exportar(id){
    $(location).attr('href',"../ajax/exports/exportarContactos.php?id="+id);
}

function enviar(id) {
    var r = confirm("Desea enviar los mensajes?");
    if (r === true) {
        $.ajax({
            type: "GET",
            url: '../ajax/mensajeb2c.php?op=getContactsToSendMessage&id=' + id,
            success: function(data) {
                const response = JSON.parse(data);
                wsSendMsjMassive(globalWS, '8080', '/'+globalNombreEmpresa+'/ws',response,$("#myModal"));
            }
        });
    }
}

init();