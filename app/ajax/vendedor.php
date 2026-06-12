<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require_once __ROOT__ . '/config/Telefono.php';
require (__ROOT__.'/modelos/Vendedor.php');
$vendedor=new Vendedor();
$idvendedor=isset($_POST["idvendedor"])? limpiarCadena($_POST["idvendedor"]):"";
$codigo=isset($_POST["codigo"])? limpiarCadena($_POST["codigo"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listar', 'listarClientesVendedor', 'selectVendedores']);

switch ($_GET["op"]) {
	case 'guardaryeditar':
	// Normalizar el teléfono al formato wa_id según el país del tenant (bot_config.pais).
	$pais = 'AR';
	$rp = ejecutarConsultaSimpleFila("SELECT pais FROM bot_config LIMIT 1");
	if (is_array($rp) && !empty($rp['pais'])) { $pais = $rp['pais']; }
	$telefono = Telefono::normalizar($telefono, $pais);
	if (empty($codigo)) {
		$rspta=$vendedor->insertar($idvendedor,$nombre,$telefono);
		echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
	}else{
         $rspta=$vendedor->editar($codigo,$nombre,$telefono);
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

	// Opciones para el <select> "Vendedor asignado" del form de cliente (value = vendedores.codigo)
	case 'selectVendedores':
		$rspta=$vendedor->listar();
		echo '<option value="">— Sin asignar —</option>';
		while ($reg=$rspta->fetch_object()) {
			$cod = htmlspecialchars((string)$reg->codigo, ENT_QUOTES);
			$nom = htmlspecialchars((string)$reg->nombre.' ('.$reg->codigo.')', ENT_QUOTES);
			echo '<option value="'.$cod.'">'.$nom.'</option>';
		}
		break;

    case 'listar':
		$rspta=$vendedor->listar();
		$data=Array();

		while ($reg=$rspta->fetch_object()) {
			$data[]=array(
            "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar(\''.$reg->codigo.'\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>',
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

	// Listado server-side de clientes de la solapa "Clientes" (asignados / disponibles).
	case 'listarClientesVendedor':
		$codVendedor = isset($_GET['vendedor']) ? limpiarCadena($_GET['vendedor']) : '';
		$modo        = (isset($_GET['modo']) && $_GET['modo'] === 'disponibles') ? 'disponibles' : 'asignados';
		$out = ($codVendedor === '')
			? array('recordsTotal'=>0, 'recordsFiltered'=>0, 'rows'=>array())
			: $vendedor->clientesServerSide($codVendedor, $modo, $_GET);
		$data = array();
		foreach ($out['rows'] as $r) {
			$cod = htmlspecialchars((string)($r['codigo'] ?? ''), ENT_QUOTES);
			if ($modo === 'asignados') {
				$btn = '<button class="btn btn-danger btn-sm rounded-pill sombra-logo" onclick="quitarClienteVendedor(\''.$cod.'\')"><i class="mdi mdi-account-remove me-1"></i> Quitar</button>';
			} else {
				$btn = '<button class="btn btn-success btn-sm rounded-pill sombra-logo" onclick="asignarClienteVendedor(\''.$cod.'\')"><i class="mdi mdi-account-plus me-1"></i> Asignar</button>';
			}
			$data[] = array(
				$btn,
				htmlspecialchars((string)($r['codigo'] ?? '')),
				htmlspecialchars((string)($r['razonSocial'] ?? '')),
				htmlspecialchars((string)($r['localidad'] ?? '')),
				htmlspecialchars((string)($r['telefono'] ?? '')),
				htmlspecialchars((string)($r['vendedor'] ?? ''))
			);
		}
		echo json_encode(array(
			'draw'            => isset($_GET['draw']) ? (int)$_GET['draw'] : 0,
			'recordsTotal'    => $out['recordsTotal'],
			'recordsFiltered' => $out['recordsFiltered'],
			'data'            => $data
		));
		break;

	case 'asignarCliente':
		$codigoCliente = isset($_POST['codigoCliente']) ? limpiarCadena($_POST['codigoCliente']) : '';
		$rspta = $vendedor->asignarCliente($codigoCliente, $codigo);
		echo $rspta > 0 ? "Cliente asignado correctamente" : "No se pudo asignar el cliente";
		break;

	case 'quitarCliente':
		$codigoCliente = isset($_POST['codigoCliente']) ? limpiarCadena($_POST['codigoCliente']) : '';
		$rspta = $vendedor->quitarCliente($codigoCliente, $codigo);
		echo $rspta > 0 ? "Cliente quitado correctamente" : "No se pudo quitar el cliente";
		break;
}
 ?>