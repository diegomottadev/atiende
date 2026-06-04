<?php
require_once '../config/auth.php';
include_once("../config/Connection.php");
require_once '../PHPExcel/Classes/PHPExcel.php';

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
//$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:L1');
//id	vendedor	razonSocial	direccion	clienteId	fecha	producto	descripcion	cantidad	precio	descuento
//Codigo	Fecha	clienteId	razonSocial	direccion	vendedor	telefono	nick	motivo	area	detalle	fecha_resolucion	resolucion	estado
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Cod Reclamo')
            ->setCellValue('B1', 'Fecha inicio reclamo')
            ->setCellValue('C1', 'clienteId')
			->setCellValue('D1', 'razonSocial')
            ->setCellValue('E1', 'direccion')
            ->setCellValue('F1', 'vendedor')
            ->setCellValue('G1', 'telefono')
            ->setCellValue('H1', 'nick')
            ->setCellValue('I1', 'motivo')
            ->setCellValue('J1', 'Sector')
            ->setCellValue('K1', 'Detalle')
			->setCellValue('L1', 'Fecha_resolucion')
			->setCellValue('M1', 'Resolucion')
            ->setCellValue('N1', 'Estado')
            ->setCellValue('O1', 'Localidad')
            ->setCellValue('P1', 'Ramo')
            ->setCellValue('Q1', 'Latitud')
            ->setCellValue('R1', 'Longitud')
            ->setCellValue('S1', 'Lista')
            ->setCellValue('T1', 'Hito')

;

// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

$objPHPExcel->getActiveSheet()->getStyle('A1:T1')->applyFromArray($boldArray);

	
			// > DATE_ADD(NOW(),INTERVAL -200 DAY)
//Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);	
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('0')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);


$query=Connection::runQuery("SELECT reclamos.`reclamoId` AS Codigo,reclamos.fecha_ingreso as Fecha, reclamos.clienteId, clientes.razonSocial,clientes.direccion, clientes.vendedor, TRIM(reclamos.telefono), reclamos.nick, reclamos.motivo,areas.area, reclamos.detalle, reclamos.fecha_resolucion,reclamos.resolucion, reclamos.estado, clientes.localidad, clientes.ramo, clientes.latitud, clientes.longitud, clientes.lista,(select	case when GROUP_CONCAT(case when estado = '' then 'Cliente' 	else estado end	, ' : ', `mensaje`, CHAR(13) ) = '' then '' else GROUP_CONCAT(case when estado = '' then CONCAT('Cliente: ' ,mensaje) else CONCAT('Administrador: ' ,mensaje) end	, ' - ', `estado`, CHAR(13) ) end as hit from msj_reclamos where id_reclamo = (reclamos.reclamoId)) as hito FROM reclamos LEFT JOIN clientes ON reclamos.clienteId= clientes.codigo LEFT JOIN areas ON reclamos.area= areas.id ");
    
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
            ->setCellValue("G".$cel, " ".$row[6])
            ->setCellValue("H".$cel, $row[7])
            ->setCellValue("I".$cel, $row[8])
            ->setCellValue("J".$cel, "".$row[9])
			->setCellValue("K".$cel, $row[10])
			->setCellValue("L".$cel, $row[11])
			->setCellValue("M".$cel, $row[12])
            ->setCellValue("N".$cel, $row[13])
            ->setCellValue("O".$cel, $row[14])
            ->setCellValue("P".$cel, $row[15])
            ->setCellValue("Q".$cel, $row[16])
            ->setCellValue("R".$cel, $row[17])
            ->setCellValue("S".$cel, $row[18])
            ->setCellValue("T".$cel, $row[19]);
        $cel+=1;
	}

/*Fin extracion de datos MYSQL*/
$rango="A2:$e";
$styleArray = array('font' => array( 'name' => 'Arial','size' => 10),
'borders'=>array('allborders'=>array('style'=> PHPExcel_Style_Border::BORDER_THIN,'color'=>array('argb' => 'FFF')))
);
//$objPHPExcel->getActiveSheet()->getStyle($rango)->applyFromArray($styleArray);
// Cambiar el nombre de hoja de c�lculo
$objPHPExcel->getActiveSheet()->setTitle('Reporte');


// Establecer �ndice de hoja activa a la primera hoja , por lo que Excel abre esto como la primera hoja
$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
     // We'll be outputting an excel file
     header('Content-type: application/vnd.ms-excel');
     // It will be called file.xls
     header('Content-Disposition: attachment; filename="Reclamos.xlsx"');
     // Write file to the browser
     $objWriter->save('php://output');

exit;

?>