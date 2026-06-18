<?php
require_once '../config/auth.php';
require_once "../config/Telefono.php";
require_once "../modelos/Area.php";

$area=new Area();

$idpersona=isset($_POST["idpersona"])? limpiarCadena($_POST["idpersona"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";


$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp']);

switch ($_GET["op"]) {
	case 'guardaryeditar':
	// Normalizar el teléfono al formato wa_id según el país del tenant (bot_config.pais).
	// El bot envía el aviso del reclamo a este número (areas.telefono) → debe quedar en
	// formato internacional para que WhatsApp lo entregue. Default AR si no hay pais cargado.
	$pais = 'AR';
	$rp = ejecutarConsultaSimpleFila("SELECT pais FROM bot_config LIMIT 1");
	if (is_array($rp) && !empty($rp['pais'])) { $pais = $rp['pais']; }
	$telefono = Telefono::normalizar($telefono, $pais);
	if (empty($idpersona)) {
		$rspta=$area->insertar($nombre,$telefono);
		echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
	}else{
         $rspta=$area->editar($idpersona,$nombre,$telefono);
		echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
	}
		break;
	

	case 'eliminar':
		$rspta=$area->eliminar($idpersona);
		echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
		break;
	
	case 'mostrar':
		$rspta=$area->mostrar($idpersona);
		echo json_encode($rspta);
		break;

    case 'listarp':
		$rspta=$area->listarp();
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