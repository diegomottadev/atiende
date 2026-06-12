<?php
define('_ROOT_', dirname(dirname(__DIR__)));
require_once _ROOT_.'/config/auth.php';   // exige sesión: este export volcaba toda la base de repartidores sin login
require_once _ROOT_.'/PHPExcel/Classes/PHPExcel.php';
require_once _ROOT_."/modelos/Repartidor.php";
$mensaje_id = $_GET['id'];
$objPHPExcel = new PHPExcel();

//Propiedades del documento
$objPHPExcel->getProperties()->setCreator("Axum")
    ->setLastModifiedBy("Axum vm")
    ->setTitle("Exportación de Repartidores")
    ->setSubject("Exportación de Repartidores")
    ->setDescription("Exportación de Repartidores")
    ->setKeywords("office 2010 openxml php")
    ->setCategory("Contactos");

//Agregar cabeceras
$objPHPExcel->setActiveSheetIndex(0)
    ->setCellValue('A1', 'Nombre')
    ->setCellValue('B1', 'Telefono')

;

// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
$objPHPExcel->getActiveSheet()->getStyle('A1:R1')->applyFromArray($boldArray);
// Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);


$repartidores=new Repartidor();
$repartidores =$repartidores->listar();

$cel=2;
while ($row=mysqli_fetch_array($repartidores)){

    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue("A".$cel, $row[1])
        ->setCellValue("B".$cel, $row[2])
    ;
    $cel+=1;

}

$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
// We'll be outputting an excel file
header('Content-type: application/vnd.ms-excel');
// It will be called file.xls
header('Content-Disposition: attachment; filename="repartidores-exportacion.xlsx"');
// Write file to the browser
$objWriter->save('php://output');

exit;

?>