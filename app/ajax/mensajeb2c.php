<?php
require_once '../config/auth.php';
require_once "../modelos/MensajeB2C.php";
$mensajeContactob2c=new MensajeB2C();

$id = isset($_GET["id"]) ? limpiarCadena($_GET["id"]) : "";
$mensajebtc_id=$_POST["mensajebtc_id"];
$id_eliminar = isset($_POST["id_eliminar"]) ? limpiarCadena($_POST["id_eliminar"]) : "";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['listarContactos', 'listarMensajes', 'showMessage', 'getContactsToSendMessage']);

switch ($_GET["op"]) {

    case 'listarContactos':

        $rspta= $mensajeContactob2c->listarContactos($mensajebtc_id);
        $data=Array();
        while ($reg=$rspta->fetch_object()) {
            $data[]=array(
                "0"=>$reg->telefono,
                "1"=>$reg->codigo,
                "2"=>$reg->razonSocial,
                "3"=>$reg->direccion,
                "4"=>$reg->localidad,
                "5"=>$reg->ramo,
                "6"=>$reg->zona
            );
        }
        $results=array(
            "sEcho"=>1,
            "iTotalRecords"=>count($data),
            "iTotalDisplayRecords"=>count($data),
            "aaData"=>$data);
        echo json_encode($results);
        break;
    case 'listarMensajes':
        $rspta=$mensajeContactob2c->listarMensajes();
        $data=Array();
        while ($reg=$rspta->fetch_object()) {

            $data[]=array(
                "0"=>$reg->id,
                "1"=>$reg->titulo,
                "2"=>$reg->mensaje,
                "3"=>$reg->cantidad,
                "4"=>$reg->fechaFormato,
//                "5"=>$reg->estado  ?  "<span class='label label-info'>Enviado</span>" : "<span class='label label-danger'>Pendiente</span>",
                "5"=>'
                      <div class="form-group" style="display: flex;">
                      <button class="btn btn-warning btn-xs" onclick="mostrar('.$reg->id.')"><i class="fa fa-pencil-square-o"> Editar</i></button>
                      <button class="btn btn-success btn-xs" onclick="enviar('.$reg->id.')"><i class="fa fa-paper-plane"> Enviar</i></button>
                      <button class="btn btn-primary btn-xs" onclick="exportar('.$reg->id.')"><i class="fa fa-arrow-circle-down"> Exportar</i></button>
                      <button class="btn btn-danger btn-xs" onclick="eliminar('.$reg->id.')"><i class="fa fa-trash"> Eliminar</i></button>
                      </div>',
            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

    case 'showMessage':
        $rspta=$mensajeContactob2c->show($id);
        echo json_encode($rspta);
        break;

    case 'deleteMessage':
        $rspta=$mensajeContactob2c->eliminar($id_eliminar);
        echo $rspta ? "Datos eliminado correctamente" : "No se pudo eliminar los datos";
        break;
    case 'getContactsToSendMessage':
        $data=$mensajeContactob2c->getContactsToSendMsg($id);
        echo json_encode($data);

}
?>