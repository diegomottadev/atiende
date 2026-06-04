<?php
require_once '../config/auth.php';
require_once "../modelos/Repartidor.php";

$repartidor=new Repartidor();

$id=isset($_POST["id"])? limpiarCadena($_POST["id"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['listar', 'mostrar']);

switch ($_GET["op"]) {
    case 'guardaryeditar':
        if (empty($id)) {
            $result = $repartidor->insertar($nombre, $telefono);
            echo $result ? "Datos del repartidor registrados correctamente" : "No se pudo registrar el repartidor";
        } else {
            $result = $repartidor->editar($id, $nombre, $telefono);
            echo $result ? "Datos del repartidor actualizados correctamente" : "No se pudo actualizar los datos del repartidor";
        }
        break;
    case 'listar':
        $results=$repartidor->listar();
        $data=Array();
        while ($repartidorQ=$results->fetch_object()) {
            $data[]=array(
                "0"=>'<div style="display:flex;gap:3px;"><button class="btn btn-warning btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Editar" onclick="mostrar('.$repartidorQ->id.')"><i class="mdi mdi-lead-pencil m-n2"></i></button><button class="btn btn-danger btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Eliminar" onclick="eliminar('.$repartidorQ->id.')"><i class="mdi mdi-delete m-n2"></i></button></div>',
                "1"=>"<div class='tbdato'>".$repartidorQ->id."</div>",
                "2"=>"<div class='tbdato'>".$repartidorQ->nombre."</div>",
                "3"=>"<div class='tbdato'>".$repartidorQ->telefono."</div>",                

            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;
    case 'eliminar':
        $results=$repartidor->eliminar($id);
        echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
        break;

    case 'mostrar':
        $results=$repartidor->mostrar($id);
        echo json_encode($results);
        break;
}

?>