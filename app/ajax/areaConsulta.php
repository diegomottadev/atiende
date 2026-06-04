<?php
require_once '../config/auth.php';
require_once "../modelos/AreaConsulta.php";

$areaConsulta=new AreaConsulta();

$idpersona=isset($_POST["idpersona"])? limpiarCadena($_POST["idpersona"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";


$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp']);

switch ($_GET["op"]) {
    case 'guardaryeditar':
        if (empty($idpersona)) {
            $rspta=$areaConsulta->insertar($nombre,$telefono);
            echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
        }else{
            $rspta=$areaConsulta->editar($idpersona,$nombre,$telefono);
            echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
        }
        break;


    case 'eliminar':
        $rspta=$areaConsulta->eliminar($idpersona);
        echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
        break;

    case 'mostrar':
        $rspta=$areaConsulta->mostrar($idpersona);
        echo json_encode($rspta);
        break;

    case 'listarp':
        $rspta=$areaConsulta->listarp();
        $data=Array();
        ///SELECT `id`, `area`, `telefono`, `activo` FROM `areas` WHERE 1
        while ($reg=$rspta->fetch_object()) {
            $data[]=array(
                "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar('.$reg->id.')"><i class="mdi mdi-lead-pencil m-n2"></i></button>'.' '.'<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar('.$reg->id.')"><i class="mdi mdi-delete m-n2"></i></button>',
                "1"=>$reg->id,
                "2"=>$reg->area,
                "3"=>$reg->telefono
            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

}
?>