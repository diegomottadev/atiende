<?php
require_once '../config/auth.php';
include_once("../config/Connection.php");
require_once '../PHPExcel/Classes/PHPExcel.php';

Connection::setDatabase(!empty($_SESSION['tenant_db']) ? $_SESSION['tenant_db'] : DB_NAME);

$objPHPExcel = new PHPExcel();

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

$objPHPExcel->setActiveSheetIndex(0)
           // ->setCellValue('A1', 'R')
            ->setCellValue('A1', 'N�mero')
            ->setCellValue('B1', 'Fecha')
            ->setCellValue('C1', 'ClienteID')
			->setCellValue('D1', 'RazonSocial')
            ->setCellValue('E1', 'Direccion')
            ->setCellValue('F1', 'Localidad')
			->setCellValue('G1', 'Vendedor')
            ->setCellValue('H1', 'Ramo')
            ->setCellValue('I1', 'Telefono')
            ->setCellValue('J1', 'Lista')
            ->setCellValue('K1', 'Total Venta')
            ->setCellValue('L1', 'Estado');
			
// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->applyFromArray($boldArray);		

	
			// > DATE_ADD(NOW(),INTERVAL -200 DAY)
//Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);	
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);		
//$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);		

  $query=Connection::runQuery("SELECT p.pedidoid,DATE(p.fecha) AS fecha,p.clienteId,c.razonSocial, c.direccion,c.localidad,c.vendedor,c.ramo,t.telefono,c.lista ,ROUND(sum(p.subtotal),2) AS total,flag as estado FROM pedidos p INNER JOIN clientes c ON c.codigo=p.clienteId LEFT JOIN telefonos t ON p.clienteId=t.clienteId GROUP BY p.pedidoid ORDER BY DATE(p.fecha) DESC ");
    
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
			->setCellValue("K".$cel, $row[10])
			->setCellValue("L".$cel, $row[11]);
			
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