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
csrfGuard($op, ['mostrar', 'listar', 'selectCategoria', 'listarCabecera']);

switch ($_GET["op"]) {
	case 'guardaryeditar':

	if (!file_exists($_FILES['imagen']['tmp_name'])|| !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
		$imagen=$_POST["imagenactual"];
	}else{
		$ext=explode(".", $_FILES["imagen"]["name"]);
		if ($_FILES['imagen']['type']=="image/jpg" || $_FILES['imagen']['type']=="image/jpeg" || $_FILES['imagen']['type']=="image/png") {
			$imagen=$idarticulo.'.'. end($ext);
			move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/articulos/".$imagen);
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
		$rspta=$articulo->listar();
		$data=Array();
		
		$i=0;
		while ($row = mysqli_fetch_row($rspta)){
		
		if (file_exists("../files/articulos/".$row[0].".jpg"))
		$row[count ($row)]="<img src='../files/articulos/".$row[0].".jpg?im=".rand()."' height='50px' width='50px'>";
		else
		$row[count ($row)]="Sin Imagen";
		$row[]='<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar(\''.$row[0].'\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>';
		$data[$i]=$row;
		$i++;
		}

		
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		echo json_encode($results);
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