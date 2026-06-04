<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require (__ROOT__.'/modelos/Vendedor.php');
$vendedor=new Vendedor();
$idvendedor=isset($_POST["idvendedor"])? limpiarCadena($_POST["idvendedor"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listar']);

switch ($_GET["op"]) {
	case 'guardaryeditar':
	if (empty($idvendedor)) {
		$rspta=$vendedor->insertar($idvendedor,$nombre,$telefono);
		echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
	}else{
         $rspta=$vendedor->editar($idvendedor,$nombre,$telefono);
		echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
	}
		break;
	

	case 'desactivar':
		$rspta=$vendedor->desactivar($idvendedor);
		echo $rspta ? "Datos desactivados correctamente" : "No se pudo desactivar los datos";
		break;
	case 'activar':
		$rspta=$vendedor->activar($idcategoria);
		echo $rspta ? "Datos activados correctamente" : "No se pudo activar los datos";
		break;
	
	case 'mostrar':
		$rspta=$vendedor->mostrar($idvendedor);
		echo json_encode($rspta);
		break;

    case 'listar':
		$rspta=$vendedor->listar();
		$data=Array();

		while ($reg=$rspta->fetch_object()) {
			$data[]=array(
            "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar('.$reg->codigo.')"><i class="mdi mdi-lead-pencil m-n2"></i></button>',
			"1"=>$reg->codigo,
            "2"=>$reg->nombre,
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