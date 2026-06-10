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
$deposito=isset($_POST["deposito"])? limpiarCadena($_POST["deposito"]):"";
$latitud=isset($_POST["latitud"])? limpiarCadena($_POST["latitud"]):"";
$longitud=isset($_POST["longitud"])? limpiarCadena($_POST["longitud"]):"";
$modo=isset($_POST["modo"])? limpiarCadena($_POST["modo"]):"";


$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp', 'listarc', 'filtros']);

switch ($_GET["op"]) {
	case 'guardaryeditar':
		if ($modo === 'nuevo') {
			if ($idpersona === '') { echo "Ingresá un código de cliente"; break; }
			$rspta=$persona->insertar($idpersona,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono,$deposito,$latitud,$longitud);
			if ($rspta === 'dup') echo "Ya existe un cliente con el código ".$idpersona;
			else                  echo $rspta ? "Cliente registrado correctamente" : "No se pudo registrar el cliente";
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
		// Server-side processing (DataTables): solo la página pedida + totales → escala a millones de filas.
		$draw    = isset($_REQUEST['draw'])   ? intval($_REQUEST['draw'])   : 1;
		$start   = isset($_REQUEST['start'])  ? intval($_REQUEST['start'])  : 0;
		$length  = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
		$buscar  = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : '';
		$orden   = (isset($_REQUEST['order'])   && is_array($_REQUEST['order']))   ? $_REQUEST['order']   : array();
		$columns = (isset($_REQUEST['columns']) && is_array($_REQUEST['columns'])) ? $_REQUEST['columns'] : array();

		$resc = $persona->listarcServerSide($start, $length, $buscar, $orden, $columns);
		$data = array();
		foreach ($resc['rows'] as $reg) {
			$cod = $reg['codigo'];
			$data[] = array(
				'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar(\''.$cod.'\')"><i class="mdi mdi-lead-pencil m-n2"></i></button> <button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\''.$cod.'\')"><i class="mdi mdi-delete m-n2"></i></button>',
				$reg['codigo'], $reg['vendedor'], $reg['razonSocial'], $reg['direccion'], $reg['localidad'],
				$reg['telefono'], $reg['ramo'], $reg['zona'], $reg['lista'], $reg['latitud'], $reg['longitud'], $reg['deposito']
			);
		}
		echo json_encode(array(
			"draw"            => $draw,
			"recordsTotal"    => $resc['recordsTotal'],
			"recordsFiltered" => $resc['recordsFiltered'],
			"data"            => $data
		), JSON_UNESCAPED_UNICODE);
		break;

	case 'filtros':
		// Valores distintos para los dropdowns de filtro (vendedor / zona / lista / ramo)
		echo json_encode(array(
			"vendedor" => $persona->distinctCliente('vendedor'),
			"zona"     => $persona->distinctCliente('zona'),
			"lista"    => $persona->distinctCliente('lista'),
			"ramo"     => $persona->distinctCliente('ramo')
		), JSON_UNESCAPED_UNICODE);
		break;
}
 ?>