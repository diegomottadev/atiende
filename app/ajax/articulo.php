<?php
require_once '../config/auth.php';
require_once "../modelos/Articulo.php";

$articulo=new Articulo();

$idarticulo=isset($_POST["idarticulo"])? limpiarCadena($_POST["idarticulo"]):"";
$rubro=isset($_POST["rubro"])? limpiarCadena($_POST["rubro"]):"";
$linea=isset($_POST["linea"])? limpiarCadena($_POST["linea"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$calibre=isset($_POST["calibre"])? limpiarCadena($_POST["calibre"]):"";
//$descripcion=isset($_POST["descripcion"])? limpiarCadena($_POST["descripcion"]):"";
$imagen=isset($_POST["imagen"])? limpiarCadena($_POST["imagen"]):"";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listar', 'selectCategoria', 'listarCabecera', 'filtros']);

switch ($_GET["op"]) {
	case 'guardaryeditar':

	// Validación de imagen server-side: tamaño + contenido real (no el Content-Type del cliente, falsificable)
	$MAX_IMG_BYTES = 2 * 1024 * 1024; // 2 MB
	$allowedImg    = ['image/jpeg' => 'jpg']; // el form de artículos solo acepta JPG; la miniatura busca CODIGO.jpg
	if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
		$imagen = $_POST["imagenactual"];
	} elseif ($_FILES['imagen']['size'] > $MAX_IMG_BYTES) {
		// Supera 2 MB: se conserva la imagen actual y no se reemplaza
		$imagen = $_POST["imagenactual"];
	} else {
		$finfo    = new finfo(FILEINFO_MIME_TYPE);
		$realMime = $finfo->file($_FILES['imagen']['tmp_name']);
		$isImage  = @getimagesize($_FILES['imagen']['tmp_name']) !== false;
		if ($isImage && isset($allowedImg[$realMime])) {
			$imagen = $idarticulo.'.'.$allowedImg[$realMime];
			move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/articulos/".$imagen);
		} else {
			$imagen = $_POST["imagenactual"];
		}
	}
	if (empty($idarticulo)) {
		$rspta=$articulo->insertar($idcategoria,$codigo,$nombre,$stock,$descripcion,$imagen);
		echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
	}else{
         $rspta=$articulo->editar($idarticulo,$rubro,$linea,$nombre,$calibre);
		echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
	}
		break;
	

	case 'desactivar':
		$rspta=$articulo->desactivar($idarticulo);
		echo $rspta ? "Datos desactivados correctamente" : "No se pudo desactivar los datos";
		break;
	case 'activar':
		$rspta=$articulo->activar($idarticulo);
		echo $rspta ? "Datos activados correctamente" : "No se pudo activar los datos";
		break;
	
	case 'mostrar':
		$rspta=$articulo->mostrar($idarticulo);
		echo json_encode($rspta);
		break;

    case 'listar':
		// Server-side processing (DataTables): devuelve SOLO la página pedida + totales.
		// Escala a millones de filas: la DB hace el trabajo (WHERE/ORDER/LIMIT con índices),
		// el navegador recibe ~10-50 filas por vez.
		$draw    = isset($_REQUEST['draw'])   ? intval($_REQUEST['draw'])   : 1;
		$start   = isset($_REQUEST['start'])  ? intval($_REQUEST['start'])  : 0;
		$length  = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
		$buscar  = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : '';
		$orden   = (isset($_REQUEST['order'])   && is_array($_REQUEST['order']))   ? $_REQUEST['order']   : array();
		$columns = (isset($_REQUEST['columns']) && is_array($_REQUEST['columns'])) ? $_REQUEST['columns'] : array();

		$res  = $articulo->listarServerSide($start, $length, $buscar, $orden, $columns);

		$data = array();
		foreach ($res['rows'] as $row) {
			$idart = $row[0];
			$row[count($row)] = "<img src='../files/articulos/".$idart.".jpg?im=".rand()."' height='38px' width='38px' onerror=\"this.onerror=null;this.src='../files/articulos/camara.jpg';\">";
			// Botón editar como PRIMERA columna, con tooltip (igual que las otras tablas)
			array_unshift($row, '<button class="btn btn-warning btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Editar" onclick="mostrar(\''.$idart.'\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>');
			$data[] = $row;
		}

		echo json_encode(array(
			"draw"            => $draw,
			"recordsTotal"    => $res['recordsTotal'],
			"recordsFiltered" => $res['recordsFiltered'],
			"data"            => $data
		), JSON_UNESCAPED_UNICODE);
		break;

	case 'filtros':
		// Valores distintos para los dropdowns de filtro (rubro / subrubro / línea / marca)
		echo json_encode(array(
			"rubro"    => $articulo->distinct('rubro'),
			"subrubro" => $articulo->distinct('subrubro'),
			"linea"    => $articulo->distinct('linea'),
			"marca"    => $articulo->distinct('marca')
		), JSON_UNESCAPED_UNICODE);
		break;

		case 'selectCategoria':
			require_once "../modelos/Categoria.php";
			$categoria=new Categoria();

			$rspta=$categoria->select();

			while ($reg=$rspta->fetch_object()) {
				echo '<option value=' . $reg->idcategoria.'>'.$reg->nombre.'</option>';
			}
	break;
	case 'listarCabecera':
			$rspta=$articulo->listarCabecera();
			while ($row = mysqli_fetch_assoc($rspta)){
			   $cabecera=implode(",", array_keys($row)).",";
			}
        echo "<th>".str_replace(",","</th><th>", $cabecera) ;
	break;		
			
			
			
}
 ?>