<?php
define('_ROOT_', dirname(dirname(__DIR__)));
require_once _ROOT_.'/PHPExcel/Classes/PHPExcel.php';
require_once _ROOT_."/modelos/Persona.php";

$objPHPExcel = new PHPExcel();

//Propiedades del documento
$objPHPExcel->getProperties()->setCreator("Axum")
							 ->setLastModifiedBy("Axum vm")
							 ->setTitle("Exportación de Clientes")
							 ->setSubject("Exportación de Clientes")
							 ->setDescription("Exportación de Clientes")
							 ->setKeywords("office 2010 openxml php")
							 ->setCategory("Clientes");

//Agregar cabeceras

$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'codigo')
            ->setCellValue('B1', 'razon_social')
            ->setCellValue('C1', 'direccion')
			->setCellValue('D1', 'zona')
            ->setCellValue('E1', 'vendedor')
            ->setCellValue('F1', 'supervisor')            
            ->setCellValue('G1', 'telefono')
            ->setCellValue('H1', 'lista')
            ->setCellValue('I1', 'orden')
            ->setCellValue('J1', 'ramo')
            ->setCellValue('K1', 'subramo')
            ->setCellValue('L1', 'localidad')
            ->setCellValue('M1', 'provincia')
            ->setCellValue('N1', 'pais')
            ->setCellValue('O1', 'deposito')
            ->setCellValue('P1', 'latitud')
            ->setCellValue('Q1', 'longitud')
            ->setCellValue('R1', 'id')
            ;

			
// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
$objPHPExcel->getActiveSheet()->getStyle('A1:R1')->applyFromArray($boldArray);

// Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);	
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);	
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);

$persona=new Persona();
$clientes =$persona->listarc();
$cel=2;//Numero de fila donde empezara a crear  el reporte
while ($row=mysqli_fetch_array($clientes)){
    
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
    ->setCellValue("L".$cel, $row[11])
    ->setCellValue("M".$cel, $row[12])
    ->setCellValue("N".$cel, $row[13])
    ->setCellValue("O".$cel, $row[14])
    ->setCellValue("P".$cel, $row[15])
    ->setCellValue("Q".$cel, $row[16])
    ->setCellValue("R".$cel, $row[17])
    ;
    $cel+=1;

}

$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
     // We'll be outputting an excel file
     header('Content-type: application/vnd.ms-excel');
     // It will be called file.xls
     header('Content-Disposition: attachment; filename="clientes-exportacion.xlsx"');
     // Write file to the browser
     $objWriter->save('php://output');

exit;

?>