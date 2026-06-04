<?php
require_once '../config/auth.php';
require_once "../modelos/MensajeB2B.php";

$mensaje=new MensajeB2B();

$id=isset($_POST["id"])? limpiarCadena($_POST["id"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$descripcion=isset($_POST["descripcion"])? limpiarCadena($_POST["descripcion"]):"";
$json=$_POST["json"];

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listar', 'listarClientes']);

switch ($_GET["op"]) {

    case 'mostrar':
        $rspta=$mensaje->show($id);
        echo json_encode($rspta);
        break;

    case 'listar':
        $rspta=$mensaje->listar();
        $data=Array();
        while ($reg=$rspta->fetch_object()) {

            $obj = json_decode($reg->destino, TRUE);

            $data[]=array(
                "0"=>$reg->id,
                "1"=>$reg->titulo,
                "2"=>$reg->mensaje,
                "3"=>count($obj[4]),
                "4"=>$reg->fechaFormato,
                "5"=>'<div class="form-group" style="display: flex;">
                         <button class="btn btn-warning btn-xs" onclick="mostrar('.$reg->id.')"><i class="fa fa-pencil-square-o"> Editar</i></button>
                          <button class="btn btn-success btn-xs" onclick="enviar('.$reg->id.')"><i class="fa fa-paper-plane"> Enviar</i></button>
                          <button class="btn btn-primary btn-xs" onclick="exportar('.$reg->id.')"><i class="fa fa-arrow-circle-down"> Exportar</i></button>
                          <button class="btn btn-danger btn-xs" onclick="eliminar('.$reg->id.')"><i class="fa fa-trash"> Eliminar</i></button>
                    </div>
                        ',
            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

    case 'listarClientes':


        $rspta=$mensaje->listarContactos($id);
        $data=Array();
        $obj = json_decode($json, TRUE);

        while ($reg=$rspta->fetch_object()) {
            $data[]=array(
                "0"=>$reg->telefono,
                "1"=>$reg->codigo,
                "2"=>$reg->razonSocial,
                "3"=>$reg->direccion,
                "4"=>$reg->vendedor,
                "5"=>$reg->localidad,
                "6"=>$reg->ramo,
                "7"=>$reg->zona
            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

    case 'delete':
        $rspta=$mensaje->delete($id);
        echo $rspta ? "Datos eliminado correctamente" : "No se pudo eliminar los datos";
        break;
}
?>