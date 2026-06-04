<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require (__ROOT__.'/modelos/Solicitud.php');

$solicitud=new Solicitud();

$id=isset($_POST["id"])? limpiarCadena($_POST["id"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$direccion=isset($_POST["direccion"])? limpiarCadena($_POST["direccion"]):"";
$localidad=isset($_POST["localidad"])? limpiarCadena($_POST["localidad"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";
$zona=isset($_POST["zona"])? limpiarCadena($_POST["zona"]):"";
$ramo=isset($_POST["ramo"])? limpiarCadena($_POST["ramo"]):"";
$longitud=isset($_POST["longitud"])? limpiarCadena($_POST["longitud"]):"";
$latitud=isset($_POST["latitud"])? limpiarCadena($_POST["latitud"]):"";
$codigo=isset($_POST["codigo"])? limpiarCadena($_POST["codigo"]):"";
$lista=isset($_POST["lista"])? limpiarCadena($_POST["lista"]):"";
$cuit=isset($_POST["cuit"])? limpiarCadena($_POST["cuit"]):"";
$deposito=isset($_POST["deposito"])? limpiarCadena($_POST["deposito"]):"";
$vendedor=isset($_POST["vendedor"])? limpiarCadena($_POST["vendedor"]):"";
$filter=isset($_GET["filter"])? limpiarCadena($_GET["filter"]):"";



$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listar', 'nextCodigo']);

switch ($_GET["op"]) {
    case 'guardarNuevoCliente':
            $rspta=$solicitud->guardarNuevoCliente($id,$codigo,$nombre, $direccion,$localidad,$telefono,$zona,$ramo,$latitud,$longitud,$lista,$cuit,$deposito,$vendedor);
            echo json_encode($rspta) ;
    break;

    case 'mostrar':
        $rspta=$solicitud->mostrar($id);
        echo json_encode($rspta);
        break;

    case 'listar':
        $rspta = $solicitud->listar($filter);
        $data = Array();
        while ($reg = $rspta->fetch_object()) {
            $data[] = array(
                "0" => $reg->estado ? '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\'' . $reg->id . '\')"><i class="mdi mdi-delete m-n2"></i></button>' : '<button class="btn btn-warning btn-sm btn-icon-line" onclick="asignar(\'' . $reg->id . '\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>' . ' ' . '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\'' . $reg->id . '\')"><i class="mdi mdi-delete m-n2"></i></button>',
                "1" => $reg->nombre,
                "2" => $reg->cuit,
                "3" => $reg->direccion,
                "4" => $reg->localidad,
                "5" => $reg->telefono,
                "6" => $reg->fecha,
                "7" => $reg->estado ?  '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-danger">Pendiente</span>' ,
            );
        }
        $results = array(
            "sEcho" => 1,//info para datatables
            "iTotalRecords" => count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords" => count($data),//enviamos el total de registros a visualizar
            "aaData" => $data);
        echo json_encode($results);
        break;

    case 'eliminar':
        $rspta=$solicitud->eliminar($id);
        echo $rspta ? "Solicitud eliminados correctamente" : "No se pudo eliminar la solicitud";
        break;

    case 'nextCodigo':
        echo json_encode(['codigo' => $solicitud->nextCodigo()]);
        break;
}
?>