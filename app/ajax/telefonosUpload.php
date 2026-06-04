<?php
require_once '../config/auth.php';
include_once '../config/Connection.php';
require_once '../PHPExcel/Classes/PHPExcel.php';

// Mutación (import Excel por POST, sin switch) → exige token CSRF.
requireCsrf();

$carpeta = "excel";
if (!file_exists($carpeta)) {
    mkdir($carpeta, 0777, true);
}

$error_1 = "";
if (!empty($_FILES['contactos']['name'])) {

    if (((strpos($_FILES['contactos']['name'], "xls") || strpos($_FILES['contactos']['name'], "xlsx")))) {

        if (strncasecmp($_FILES['contactos']['name'], "contactos", 8) === 0) {

            if (move_uploaded_file($_FILES['contactos']['tmp_name'], $carpeta . "/" . $_FILES['contactos']['name'])) {


                if ($_POST['_id'] == "") {
                    $info_1 = guardarMensajesConClientes($_FILES['contactos']['name'], $_POST['titulo'], $_POST['mensaje']);
                } else {
                    $info_1 = editarMensajesConClientes($_FILES['contactos']['name'], $_POST['_id'], $_POST['titulo'], $_POST['mensaje']);
                }

            } else {

                $info_1 = "<FONT COLOR='red'>Ocurrio algun error al subir el fichero. No pudo guardarse. </FONT>";
            }
        } else {

            $info_1 = "<FONT COLOR='red'> El nombre del archivo debe ser 'Mensajes' </FONT>";
        }


    } else {

        $info_1 = "<FONT COLOR='red'> La extension archivos no es correcta. </FONT>";
    }
    //echo "Archivo ".$_FILES['clientes']['name']."<br>";

} else if (empty($_FILES['contactos']['name']) && $_POST['_id'] != "") {
    $info_1 = editarMensajesConClientes($_FILES['contactos']['name'], $_POST['_id'], $_POST['titulo'], $_POST['mensaje']);
}

function guardarMensajesConClientes($archivo, $titulo, $mensaje)
{

    try {

        $archivo = "excel/" . $archivo;

        $inputFileType = PHPExcel_IOFactory::identify($archivo);

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $fecha = date('Y-m-d H:i:s');
        $cantidad = $highestRow - 1;
        $mensajes2c_id = Connection::runQueryID("INSERT INTO mensajesb2c (titulo,mensaje,fecha,cantidad) VALUES ('$titulo','$mensaje','$fecha','$cantidad')");
//        die(($cantidad > 100) );
//
        if ($cantidad > 100) {
            throw new Exception('El archivo Tiene mas de 100 contactos.');
        }

        for ($row = 2; $row <= $highestRow; $row++) {

            $telefono = $sheet->getCell("A" . $row)->getValue();
            $codigo = $sheet->getCell("B" . $row)->getValue();
            $razonSocial = $sheet->getCell("C" . $row)->getValue();
            $direccion = $sheet->getCell("D" . $row)->getValue();
            $zona = $sheet->getCell("E" . $row)->getValue();
            $ramo = $sheet->getCell("F" . $row)->getValue();
            $localidad = $sheet->getCell("G" . $row)->getValue();
            $cuit = $sheet->getCell("H" . $row)->getValue();

            $contactob2c_id = Connection::runQueryID("INSERT INTO contactosb2c (telefono,codigo,razonSocial,direccion,localidad,ramo,zona,cuit) VALUES ('$telefono','$codigo','$razonSocial','$direccion','$localidad','$ramo','$zona','$cuit')");

            Connection::runQuery("INSERT INTO mensajeb2c_contactob2c (mensajeb2c_id,contactob2c_id) VALUES ('$mensajes2c_id','$contactob2c_id')");
            usleep(100);
        }

        echo json_encode(array(
            'result' => 'Registro creado con exito!',
        ));

    } catch (Exception $e) {
        echo json_encode(array(
            'error' => array(
                'msg' => $e->getMessage(),
                'code' => $e->getCode(),
            ),
        ));
    }
}


function editarMensajesConClientes($archivo = null, $id, $titulo, $mensaje)
{

   try {

        $fecha = date('Y-m-d H:i:s');

        if ($archivo != null) {
            $archivo = "excel/" . $archivo;

            $inputFileType = PHPExcel_IOFactory::identify($archivo);

            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($archivo);
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $cantidad = $highestRow - 1;
            Connection::runQuery("DELETE mensaje, contacto from contactosb2c contacto INNER JOIN  mensajeb2c_contactob2c mensaje ON  contacto.id = mensaje.contactob2c_id WHERE mensaje.mensajeb2c_id = '$id'");
            usleep(200);

            for ($row = 2; $row <= $highestRow; $row++) {

                $telefono = $sheet->getCell("A" . $row)->getValue();
                $codigo = $sheet->getCell("B" . $row)->getValue();
                $razonSocial = $sheet->getCell("C" . $row)->getValue();
                $direccion = $sheet->getCell("D" . $row)->getValue();
                $zona = $sheet->getCell("E" . $row)->getValue();
                $ramo = $sheet->getCell("F" . $row)->getValue();
                $localidad = $sheet->getCell("G" . $row)->getValue();
                $cuit = $sheet->getCell("H" . $row)->getValue();

                $contactob2c_id = Connection::runQueryID("INSERT INTO contactosb2c (telefono,codigo,razonSocial,direccion,localidad,ramo,zona,cuit) VALUES ('$telefono','$codigo','$razonSocial','$direccion','$localidad','$ramo','$zona','$cuit')");

                Connection::runQuery("INSERT INTO mensajeb2c_contactob2c (mensajeb2c_id,contactob2c_id) VALUES ('$id','$contactob2c_id')");
                usleep(100);
            }

            Connection::runQuery("UPDATE mensajesb2c SET titulo='$titulo', mensaje= '$mensaje', fecha='$fecha',cantidad='$cantidad'where id ='$id'");
            echo json_encode(array(
                'result' => 'Registro actualizado con exito!',
            ));

        }else{
            Connection::runQuery("UPDATE mensajesb2c SET titulo='$titulo', mensaje= '$mensaje', fecha='$fecha' where id ='$id'");
            echo json_encode(array(
                'result' => 'Registro actualizado con exito!',
            ));
        }
    }
    catch (Exception $e) {
            echo json_encode(array(
                'error' => array(
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                ),
            ));
        }

}
