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
csrfGuard($op, ['mostrar', 'listar', 'filtros', 'nextCodigo']);

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
        // Server-side processing (DataTables): devuelve SOLO la página pedida + totales.
        // La DB hace búsqueda/orden/paginado con índices → escala a millones de filas.
        $draw    = isset($_REQUEST['draw'])   ? intval($_REQUEST['draw'])   : 1;
        $start   = isset($_REQUEST['start'])  ? intval($_REQUEST['start'])  : 0;
        $length  = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
        $buscar  = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : '';
        $orden   = (isset($_REQUEST['order'])   && is_array($_REQUEST['order']))   ? $_REQUEST['order']   : array();
        $columns = (isset($_REQUEST['columns']) && is_array($_REQUEST['columns'])) ? $_REQUEST['columns'] : array();

        $res = $solicitud->listarServerSide($start, $length, $buscar, $orden, $columns);

        $data = array();
        foreach ($res['rows'] as $reg) {
            $id     = $reg['id'];
            $estado = $reg['estado'];
            $acciones = $estado
                ? '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\'' . $id . '\')"><i class="mdi mdi-delete m-n2"></i></button>'
                : '<button class="btn btn-warning btn-sm btn-icon-line" onclick="asignar(\'' . $id . '\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>' . ' ' . '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\'' . $id . '\')"><i class="mdi mdi-delete m-n2"></i></button>';
            $data[] = array(
                "0" => $acciones,
                "1" => $reg['nombre'],
                "2" => $reg['cuit'],
                "3" => $reg['direccion'],
                "4" => $reg['localidad'],
                "5" => $reg['telefono'],
                "6" => $reg['fecha'],
                "7" => $estado ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-danger">Pendiente</span>',
            );
        }
        echo json_encode(array(
            "draw"            => $draw,
            "recordsTotal"    => $res['recordsTotal'],
            "recordsFiltered" => $res['recordsFiltered'],
            "data"            => $data
        ), JSON_UNESCAPED_UNICODE);
        break;

    case 'filtros':
        // Valores distintos para los dropdowns de filtro.
        // Estado es 0/1 → lo mapeamos a etiquetas (col 7 filtra por valor exacto = 0|1).
        echo json_encode(array(
            "localidad" => $solicitud->distinctSolicitud('localidad'),
            "estado"    => array(
                array("v"=>"0", "t"=>"Pendiente"),
                array("v"=>"1", "t"=>"Aprobado")
            )
        ), JSON_UNESCAPED_UNICODE);
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