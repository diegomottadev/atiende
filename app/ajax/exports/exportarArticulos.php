<?php
define('_ROOT_', dirname(dirname(__DIR__)));
require_once _ROOT_.'/config/auth.php';   // exige sesión: este export volcaba toda la base de artículos sin login
require_once _ROOT_.'/PHPExcel/Classes/PHPExcel.php';
require_once _ROOT_."/modelos/Articulo.php";

$objPHPExcel = new PHPExcel();

//Propiedades del documento
$objPHPExcel->getProperties()->setCreator("Axum")
							 ->setLastModifiedBy("Axum vm")
							 ->setTitle("Exportación de Articulos")
							 ->setSubject("Exportación de Articulos")
							 ->setDescription("Exportación de Articulos")
							 ->setKeywords("office 2010 openxml php")
							 ->setCategory("Articulos");

//Agregar cabeceras
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'codigo')
            ->setCellValue('B1', 'descripcion')
            ->setCellValue('C1', 'lista1')
			->setCellValue('D1', 'lista2')
            ->setCellValue('E1', 'lista3')
            ->setCellValue('F1', 'lista4')
            ->setCellValue('G1', 'lista5')
            ->setCellValue('H1', 'lista6')
            ->setCellValue('I1', 'lista7')
            ->setCellValue('J1', 'linea')
            ->setCellValue('K1', 'rubro')
			->setCellValue('L1', 'subrubro')
			->setCellValue('M1', 'marca')
            ->setCellValue('N1', 'kilos')
            ->setCellValue('O1', 'litros')
            ->setCellValue('P1', 'color')
            ->setCellValue('Q1', 'tamano')
            ->setCellValue('R1', 'palet')
            ->setCellValue('S1', 'capacidad')
            ->setCellValue('T1', 'pack')
			->setCellValue('U1', 'impInt')
            ->setCellValue('V1', 'codBarra')
            ->setCellValue('W1', 'topecant')
            ->setCellValue('X1', 'iva')
            ->setCellValue('Y1', 'deposito')
            ->setCellValue('Z1', 'stock')
            ->setCellValue('AA1', 'orden')
            ;
			
// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
$objPHPExcel->getActiveSheet()->getStyle('A1:AA1')->applyFromArray($boldArray);

// Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);	
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(80);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);	
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('X')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('Y')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('Z')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('AA')->setWidth(8);

$articulo=new Articulo();
$articulos =$articulo->listar();
$cel=2;//Numero de fila donde empezara a crear  el reporte
while ($row=mysqli_fetch_array($articulos)){
    
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
    ->setCellValue("S".$cel, $row[18])
    ->setCellValue("T".$cel, $row[19])
    ->setCellValue("U".$cel, $row[20])
    ->setCellValue("V".$cel, $row[21])
    ->setCellValue("W".$cel, $row[22])
    ->setCellValue("X".$cel, $row[23])
    ->setCellValue("Y".$cel, $row[24])
    ->setCellValue("Z".$cel, $row[25])
    ->setCellValue("AA".$cel, $row[26])
    ;
    $cel+=1;

}

$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
     // We'll be outputting an excel file
     header('Content-type: application/vnd.ms-excel');
     // It will be called file.xls
     header('Content-Disposition: attachment; filename="articulos-exportacion.xlsx"');
     // Write file to the browser
     $objWriter->save('php://output');

exit;

?>