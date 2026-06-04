<?php
require_once '../config/auth.php';
require_once "../modelos/Persona.php";

$persona=new Persona();
/*   
    "codigo": "117",
    "vendedor": "GODOY CRUZ",
    "supervisor": "1",
    "razonSocial": "PINI ARMANDO ",
    "direccion": "B� Laprida Alfonsina Storni  N� 758",
    "localidad": "2616406953",
    "ramo": "1",
    "zona": "117",
    "lista": "VERDULERIA"
	*/
$idpersona=isset($_POST["codigo"])? limpiarCadena($_POST["codigo"]):"";
$vendedor=isset($_POST["vendedor"])? limpiarCadena($_POST["vendedor"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$direccion=isset($_POST["direccion"])? limpiarCadena($_POST["direccion"]):"";
$localidad=isset($_POST["localidad"])? limpiarCadena($_POST["localidad"]):"";
$ramo=isset($_POST["ramo"])? limpiarCadena($_POST["ramo"]):"";
$zona=isset($_POST["zona"])? limpiarCadena($_POST["zona"]):"";
$lista=isset($_POST["lista"])? limpiarCadena($_POST["lista"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";


$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp', 'listarc']);

switch ($_GET["op"]) {
	case 'guardaryeditar':
		if (empty($idpersona)) {
			$rspta=$persona->insertar($tipo_persona,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email);
			echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
		}else{
			$rspta=$persona->editar($idpersona,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono);
			echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
		}
	break;
	case 'eliminar':
		$rspta=$persona->eliminar($idpersona);
		echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
		break;
	case 'eliminarCliente':
		$rspta=$persona->eliminarCliente($idpersona);
		echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
		break;

	case 'mostrar':
		$rspta=$persona->mostrar($idpersona);
		echo json_encode($rspta);
		break;

    case 'listarp':
		$rspta=$persona->listarp();
		$data=Array();

		while ($reg=$rspta->fetch_object()) {
			$data[]=array(
            "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar('.$reg->idpersona.')"><i class="mdi mdi-lead-pencil m-n2"></i></button>'.' '.'<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar('.$reg->idpersona.')"><i class="mdi mdi-delete m-n2"></i></button>',
            "1"=>$reg->nombre,
            "2"=>$reg->tipo_documento,
            "3"=>$reg->num_documento,
            "4"=>$reg->telefono,
            "5"=>$reg->email
              );
		}
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		echo json_encode($results);
		break;

		  case 'listarc':
		$rspta=$persona->listarc();
		$data=Array();
//SELECT `codigo`, `vendedor`, `razonSocial`, `direccion`, `localidad`, `ramo`, `zona`, `lista` FROM `clientes` WHERE 1
		while ($reg=$rspta->fetch_object()) {
			$data[]=array(
            "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar('.$reg->codigo.')"><i class="mdi mdi-lead-pencil m-n2"></i></button>'.' '.'<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar('.$reg->codigo.')"><i class="mdi mdi-delete m-n2"></i></button>',
            "1"=>$reg->codigo,
			"2"=>$reg->vendedor,
            "3"=>$reg->razonSocial,
            "4"=>$reg->direccion,
            "5"=>$reg->localidad,
			"6"=>$reg->telefono,
            "7"=>$reg->ramo,
			"8"=>$reg->zona,
			"9"=>$reg->lista,
			"10"=>$reg->latitud,
			"11"=>$reg->longitud,
            "12"=>$reg->deposito,
              );
			 // latitud y longitud
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