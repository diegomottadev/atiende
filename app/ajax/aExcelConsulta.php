<?php
require_once '../config/auth.php';
include_once("../config/Connection.php");
require_once '../PHPExcel/Classes/PHPExcel.php';
define('__ROOT__', dirname(dirname(__FILE__)));
require(__ROOT__ . '/config/global.php');

$objPHPExcel = new PHPExcel();

$fecha_inicio=isset($_GET["fecha_inicio"])? $_GET["fecha_inicio"]:"";
$fecha_fin=isset($_GET["fecha_fin"])? $_GET["fecha_fin"]:"";
$idcliente=isset($_GET["idcliente"])? $_GET["idcliente"]:"";

// Propiedades del documento
$objPHPExcel->getProperties()->setCreator("Axum")
							 ->setLastModifiedBy("Axum vm")
							 ->setTitle("Office 2010 XLSX Documento de prueba")
							 ->setSubject("Office 2010 XLSX Documento de prueba")
							 ->setDescription("Documento de prueba para Office 2010 XLSX, generado usando clases de PHP.")
							 ->setKeywords("office 2010 openxml php")
							 ->setCategory("Archivo con resultado de prueba");



// Combino las celdas desde A1 hasta E1


//Opciones	N�mero	Fecha	ClienteID	Cliente	Telefono	Total Venta	Estado
/*
-	Vendedor
-	Ramo
-	Zona
-	Localidad
-	Direcci�n
-	Tel�fono
-	Lista de precios
*/

//Fecha	ClienteId	Cliente	N�mero	ProductoId	Producto	Cantidad	Precio	SubTotal

// `codigo`, `vendedor`, `supervisor`, `razonSocial`, `direccion`, `localidad`, `ramo`, `zona`, `lista`, `telefono`, `latitud`, 
$objPHPExcel->setActiveSheetIndex(0)
           // ->setCellValue('A1', 'R')
            ->setCellValue('A1', 'N° de pedido')
            ->setCellValue('B1', 'Fecha')
            ->setCellValue('C1', 'ClienteID')
			->setCellValue('D1', 'RazonSocial')
            ->setCellValue('E1', 'Direccion')
            ->setCellValue('F1', 'Localidad')
            ->setCellValue('G1', 'Latitud')
            ->setCellValue('H1', 'Longitud')
            ->setCellValue('I1', 'Vendedor')
            ->setCellValue('J1', 'Ramo')
            ->setCellValue('K1', 'Telefono')
			->setCellValue('L1', 'Lista')
			->setCellValue('M1', 'ProductoId')
			->setCellValue('N1', 'Producto')
			->setCellValue('O1', 'Linea')
			->setCellValue('P1', 'Rubro')
			->setCellValue('Q1', 'Capacidad')
			->setCellValue('R1', 'Pack')
			->setCellValue('S1', 'impInt')
			->setCellValue('T1', 'codBarra')
			->setCellValue('U1', 'Cantidad')
            ->setCellValue('V1', 'Precio')
            ->setCellValue('W1', 'SubTotal')
            ->setCellValue('X1', 'Zona')
            ->setCellValue('Y1', 'Empresa')
            ->setCellValue('Z1', 'Cod repartidor')
            ->setCellValue('AA1', 'Nombre repartidor')
            ->setCellValue('AB1', 'Fecha asignación repartidor')
            ->setCellValue('AC1', 'Origen del Pedido');

			//linea`, `rubro`, `capacidad`, `pack`, `impInt`, `codBarra`
// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

$objPHPExcel->getActiveSheet()->getStyle('A1:AZ1')->applyFromArray($boldArray);

	
			// > DATE_ADD(NOW(),INTERVAL -200 DAY)
//Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);	
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('Z')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('AA')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('AB')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('AC')->setWidth(20);

//$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);		
//SELECT `codigo`, `vendedor`, `supervisor`, `razonSocial`, `direccion`, `localidad`, `ramo`, `zona`, `lista`, `telefono`, `latitud`, `longitud`
$and="";
if (!empty($idcliente)) 
$and="AND v.clienteId='$idcliente' ";
//codigo`, `vendedor`, `supervisor`, `razonSocial`, `direccion`, `localidad`, `ramo`, `zona`, `lista`, `telefono`, `latitud`, 
  $query = Connection::runQuery("SELECT  v.pedidoid,DATE(v.fecha) as fecha, p.codigo,p.razonSocial,p.direccion,p.localidad,p.latitud, p.longitud,p.vendedor,p.ramo,v.telefono,p.lista,v.producto, v.descripcion ,a.linea,a.rubro,a.capacidad,a.pack,a.impInt,a.codBarra, v.cantidad, v.precio,v.subtotal, p.zona,  '". DB_NAME ."' as empresa,v.repartidor_id,(Select nombre from repartidores where id = v.repartidor_id) as nombre_repartidor,v.fecha_asignacion,
  case when vendedorId is null or vendedorId='' then 'Cliente' else (SELECT concat (codigo,' ' , nombre) from vendedores where codigo = v.vendedorId) end as origen_pedido 
  FROM  clientes p INNER JOIN pedidos v ON v.clienteId=p.codigo INNER JOIN articulos a ON a.codigo =v.producto WHERE DATE(v.fecha)>='$fecha_inicio' AND DATE(v.fecha)<='$fecha_fin' ".$and);
    
	// `linea`, `rubro`, `capacidad`, `pack`, `impInt`, `codBarra`, `topecant`, `iva`, `deposito`, `orden` FROM `articulos` WHERE 1
	//$query=mysqli_query($con,$sql);
    $cel=2;//Numero de fila donde empezara a crear  el reporte
    //`id	vendedor	razonSocial	direccion	clienteId	fecha	producto	descripcion	cantidad	precio	descuento
	while ($row=mysqli_fetch_array($query)){
		
		
			$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue("A".$cel, $row[0])
            ->setCellValue("B".$cel, $row[1])
            ->setCellValue("C".$cel, $row[2])
            ->setCellValue("D".$cel, $row[3])
            ->setCellValue("E".$cel, $row[4])
            ->setCellValue("F".$cel, $row[5])
            ->setCellValue("G".$cel, $row[6])
            ->setCellValue("H".$cel, $row[7])
            ->setCellValue("I".$cel, $row[8])
            ->setCellValue("J".$cel, $row[9])
			->setCellValue("K".$cel, " ".$row[10])
			->setCellValue("L".$cel, $row[11])
			->setCellValue("M".$cel, $row[12])
			->setCellValue("N".$cel, $row[13])
			->setCellValue("O".$cel, $row[14])
			->setCellValue("P".$cel, $row[15])
			->setCellValue("Q".$cel, $row[16])
			->setCellValue("R".$cel, $row[17])
			->setCellValue("S".$cel, $row[18])
			->setCellValue("T".$cel, $row[19])
			->setCellValue("U".$cel, $row[20])
            ->setCellValue("V".$cel, $row[21])
            ->setCellValue("W".$cel, $row[22])
            ->setCellValue("X".$cel, $row[23])
            ->setCellValue("Y".$cel, $row[24])
            ->setCellValue("Z".$cel, $row[25])
            ->setCellValue("AA".$cel, $row[26])
            ->setCellValue("AB".$cel, $row[27])
            ->setCellValue("AC".$cel, $row[28]);
	$cel+=1;
	}

/*Fin extracion de datos MYSQL*/
$rango="A2:$e";
$styleArray = array('font' => array( 'name' => 'Arial','size' => 10),
'borders'=>array('allborders'=>array('style'=> PHPExcel_Style_Border::BORDER_THIN,'color'=>array('argb' => 'FFF')))
);
//$objPHPExcel->getActiveSheet()->getStyle($rango)->applyFromArray($styleArray);
// Cambiar el nombre de hoja de c�lculo
$objPHPExcel->getActiveSheet()->setTitle('Ventas');


// Establecer �ndice de hoja activa a la primera hoja , por lo que Excel abre esto como la primera hoja
$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
     // We'll be outputting an excel file
     header('Content-type: application/vnd.ms-excel');
     // It will be called file.xls
     header('Content-Disposition: attachment; filename="Ventas.xlsx"');
     // Write file to the browser
     $objWriter->save('php://output');

exit;

?>