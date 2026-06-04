<?php
define('_ROOT_', dirname(dirname(__DIR__)));
require_once _ROOT_.'/PHPExcel/Classes/PHPExcel.php';
require_once _ROOT_."/modelos/MensajeB2B.php";
$mensaje_id = $_GET['id'];
$objPHPExcel = new PHPExcel();

//Propiedades del documento
$objPHPExcel->getProperties()->setCreator("Axum")
    ->setLastModifiedBy("Axum vm")
    ->setTitle("Exportación de Contactos")
    ->setSubject("Exportación de Contactos")
    ->setDescription("Exportación de Contactos")
    ->setKeywords("office 2010 openxml php")
    ->setCategory("Contactos");

//Agregar cabeceras
$objPHPExcel->setActiveSheetIndex(0)
    ->setCellValue('A1', 'codigo')
    ->setCellValue('B1', 'telefono')
    ->setCellValue('C1', 'razonSocial')
    ->setCellValue('D1', 'direccion')
    ->setCellValue('E1', 'localidad')
    ->setCellValue('F1', 'ramo')
;

// Fuente de la primera fila en negrita
$boldArray = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
$objPHPExcel->getActiveSheet()->getStyle('A1:R1')->applyFromArray($boldArray);
// Ancho de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(40);


$contactos=new MensajeB2B();
$contactos =$contactos->listarContactos($mensaje_id);

$cel=2;
while ($row=mysqli_fetch_array($contactos)){

    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue("A".$cel, $row[0])
        ->setCellValue("B".$cel, $row[1])
        ->setCellValue("C".$cel, $row[2])
        ->setCellValue("D".$cel, $row[3])
        ->setCellValue("E".$cel, $row[4])
        ->setCellValue("F".$cel, $row[5])
    ;
    $cel+=1;

}

$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
// We'll be outputting an excel file
header('Content-type: application/vnd.ms-excel');
// It will be called file.xls
header('Content-Disposition: attachment; filename="mensajes-contactos-exportacion.xlsx"');
// Write file to the browser
$objWriter->save('php://output');

exit;

?>