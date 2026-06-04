<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require (__ROOT__.'/config/Connection.php');
require (__ROOT__.'/PHPExcel/Classes/PHPExcel.php');
Connection::setDatabase(!empty($_SESSION['tenant_db']) ? $_SESSION['tenant_db'] : DB_NAME);

// Mutación (import Excel por POST, sin switch) → exige token CSRF.
requireCsrf();

$carpeta = "excel";
if (!file_exists($carpeta)) {
    mkdir($carpeta, 0777, true);
}
$error_1 = "";
$error_2 = "";
$error_3 = "";
$error_4 = "";

if (!empty($_FILES['clientes']['name'])) {

    if (((strpos($_FILES['clientes']['name'], "xls") || strpos($_FILES['clientes']['name'], "xlsx")))) {

        if (strncasecmp($_FILES['clientes']['name'], "clientes", 8) === 0) {

            if (move_uploaded_file($_FILES['clientes']['tmp_name'], $carpeta . "/" . $_FILES['clientes']['name'])) {

                 guardarClientes($_FILES['clientes']['name']);
                //$info_1= "El archivo ha sido cargado correctamente.";
            } else {
                echo json_encode(["error"=>'Ocurrio algun error al subir el fichero. No pudo guardarse.',"status"=>404]);
            }
        } else {
            echo json_encode(["error"=>'El nombre del archivo debe ser empezar con <b>clientes</b>',"status"=>404]);
        }
    } else {
        echo json_encode(["error"=>"La extension archivos no es correcta","status"=>404]);
    }

}

if (!empty($_FILES['articulos']['name'])) {

    if (((strpos($_FILES['articulos']['name'], "xls") || strpos($_FILES['articulos']['name'], "xlsx")))) {

        if (strncasecmp($_FILES['articulos']['name'], "articulos", 9) === 0) {
            if (move_uploaded_file($_FILES['articulos']['tmp_name'], $carpeta . "/" . $_FILES['articulos']['name'])) {

                guardarArticulos($_FILES['articulos']['name']);
            } else {
                echo json_encode(["error"=>'Ocurrio algun error al subir el fichero. No pudo guardarse.',"status"=>404]);
            }
        } else {
            echo json_encode(["error"=>'El nombre del archivo debe ser empezar con <b>articulos</b>',"status"=>404]);
        }
    } else {
        echo json_encode(["error"=>"La extension archivos no es correcta","status"=>404]);
    }
}

if (!empty($_FILES['vendedores']['name'])) {

    if (((strpos($_FILES['vendedores']['name'], "xls") || strpos($_FILES['vendedores']['name'], "xlsx")))) {

        if (strncasecmp($_FILES['vendedores']['name'], "Vendedores", 9) === 0) {

            if (move_uploaded_file($_FILES['vendedores']['tmp_name'], $carpeta . "/" . $_FILES['vendedores']['name'])) {

                 guardarVendedor($_FILES['vendedores']['name']);
            } else {
                echo json_encode(["error"=>'Ocurrio algun error al subir el fichero. No pudo guardarse.',"status"=>404]);
            }
        } else {
            echo json_encode(["error"=>'El nombre del archivo debe ser empezar con <b>vendedores</b>',"status"=>404]);
        }
    } else {
        echo json_encode(["error"=>"La extension archivos no es correcta","status"=>404]);
    }
}
if (!empty($_FILES['mensajes']['name'])) {

    if (((strpos($_FILES['mensajes']['name'], "xls") || strpos($_FILES['mensajes']['name'], "xlsx")))) {

        if (strncasecmp($_FILES['mensajes']['name'], "mensajes", 8) === 0) {

            if (move_uploaded_file($_FILES['mensajes']['tmp_name'], $carpeta . "/" . $_FILES['mensajes']['name'])) {


                if ($_POST['_id'] == "") {
                    $info_1 = guardarMensajesConClientes($_FILES['mensajes']['name'], $_POST['titulo'], $_POST['mensaje']);
                } else {
                    $info_1 = editarMensajesConClientes($_FILES['mensajes']['name'], $_POST['_id'], $_POST['titulo'], $_POST['mensaje']);
                }

            } else {
                echo json_encode(["error"=>'Ocurrio algun error al subir el fichero. No pudo guardarse.',"status"=>404]);
            }
        } else {
            echo json_encode(["error"=>'El nombre del archivo debe ser empezar con <b>mensajes</b>',"status"=>404]);
        }
    } else {
        echo json_encode(["error"=>"La extension archivos no es correcta","status"=>404]);
    }
    //echo "Archivo ".$_FILES['clientes']['name']."<br>";

} else if (empty($_FILES['mensajes']['name']) && $_POST['_id'] != "") {
    $info_1 = editarMensajesConClientes($_FILES['mensajes']['name'], $_POST['_id'], $_POST['titulo'], $_POST['mensaje']);
}


if (!empty($_FILES['repartidores']['name'])) {

    if (((strpos($_FILES['repartidores']['name'], "xls") || strpos($_FILES['repartidores']['name'], "xlsx")))) {

        if (strncasecmp($_FILES['repartidores']['name'], "repartidores", 12) === 0) {

            if (move_uploaded_file($_FILES['repartidores']['tmp_name'], $carpeta . "/" . $_FILES['repartidores']['name'])) {
                 guardarRepartidor ($_FILES['repartidores']['name']);
            } else {
                echo json_encode(["error"=>'Ocurrio algun error al subir el fichero. No pudo guardarse.',"status"=>404]);
            }
        } else {
            echo json_encode(["error"=>'El nombre del archivo debe ser empezar con <b>repartidores</b>',"status"=>404]);
        }
    } else {
        echo json_encode(["error"=>"La extension archivos no es correcta","status"=>404]);
    }
}

function guardarClientes($archivo)
{
     try{
        $archivo = "excel/" . $archivo;

        $inputFileType = PHPExcel_IOFactory::identify($archivo);

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $num = 0;
        $fila = "";
    //-------------------------------------------------------------------------------
        Connection::runQuery("DROP TABLE IF EXISTS clientes");
    //    Connection::runQuery("CREATE TABLE `clientes`  (
    //                          `codigo` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
    //                          `razonSocial` varchar(500) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `direccion` varchar(500) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `zona` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `vendedor` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
    //                          `supervisor` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `telefono` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `lista` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `orden` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `ramo` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `subramo` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `localidad` varchar(300) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `provincia` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `pais` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
    //                          `deposito` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
    //                          `latitud` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
    //                          `longitud` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
    //                          PRIMARY KEY (`codigo`) USING BTREE
    //                        ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_spanish_ci ROW_FORMAT = Dynamic;");

        Connection::runQuery("CREATE TABLE `clientes`  (
                              `codigo` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `razonSocial` varchar(500) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `direccion` varchar(500) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `zona` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `vendedor` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `supervisor` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `telefono` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `lista` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `orden` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `ramo` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `subramo` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `localidad` varchar(300) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `provincia` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `pais` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `deposito` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `latitud` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `longitud` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `id` bigint(20) NOT NULL AUTO_INCREMENT,
                              PRIMARY KEY (`id`) USING BTREE
                            ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_spanish_ci ROW_FORMAT = Dynamic;");
    //-------------------------------------------------------------------------------

        $datos= "REPLACE INTO  `clientes`( `codigo`,`razonSocial`, `direccion`,zona,vendedor, supervisor,telefono,lista,orden,ramo, subramo,localidad, provincia, pais, deposito,`latitud`, `longitud`) VALUES";

        for ($row = 2; $row <= $highestRow; $row++) {
            $num++;
            $datos .= " ('" . $sheet->getCell("A" . $row)->getValue() . "', ";
            $datos .= " '" . str_replace("'", "", $sheet->getCell("B" . $row)->getValue()) . "', ";
            $datos .= "  '" . str_replace("'", "", $sheet->getCell("C" . $row)->getValue()) . "', ";
            $datos .= "  '" . $sheet->getCell("D" . $row)->getValue() ."', ";
            $datos .= "  '" . $sheet->getCell("E" . $row)->getValue() ."',";
            $datos .= " '" . $sheet->getCell("F" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("G" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("H" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("I" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("J" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("K" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("L" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("M" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("N" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("O" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("P" . $row)->getValue() . "',";
            $datos .= " '" . $sheet->getCell("Q" . $row)->getValue() . "'),";
        }

        //echo $datos;
        if ($num > 0) {
            $re1 = Connection::runQuery(substr($datos, 0, -1));
            //saveLog(substr($datos, 0, -1),"excel/clientes");
        }
         echo json_encode(["msj"=> "$num registros fueron actualizados con exito!","status"=>200]);
     } catch (Exception $e) {
         echo json_encode(["error"=> $e->getMessage(),"status"=>404]);
     }
}

function guardarArticulos($archivo)
{

    try {
        $archivo = "excel/" . $archivo;

        $inputFileType = PHPExcel_IOFactory::identify($archivo);

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $highestColumn = $sheet->getHighestColumn();

        $num = 0;
        $fila = "";
    //-------------------------------------------------------------------------------

        Connection::runQuery("DROP TABLE IF EXISTS articulos ");

        $camposLista = "";
        $_listas = "";
        $l = 1;
        $col = "A";
        /* letras */
        $array1 = range('A','N');
        $array2 = array_merge(range('O','Z'),array('AA'));
        $letters = array_merge($array1,$array2);
        foreach ($letters as $column) {
            if (strncasecmp($sheet->getCell($column . "1")->getValue(), "lista", 5) === 0) {
                $camposLista .= $sheet->getCell($column . "1")->getValue() . " DECIMAL(10,2),";
                $_listas .= $sheet->getCell($column . "1")->getValue() . ",";
                //	$l++;
            }
        }

        Connection::runQuery("CREATE TABLE `articulos`  (
                              `codigo` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `descripcion` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `lista1` decimal(10, 2) NULL DEFAULT NULL,
                              `lista2` decimal(10, 2) NULL DEFAULT NULL,
                              `lista3` decimal(10, 2) NULL DEFAULT NULL,
                              `lista4` decimal(10, 2) NULL DEFAULT NULL,
                              `lista5` decimal(10, 2) NULL DEFAULT NULL,
                              `lista6` decimal(10, 2) NULL DEFAULT NULL,
                              `lista7` decimal(10, 2) NULL DEFAULT NULL,
                              `linea` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `rubro` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `subrubro` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `marca` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `kilos` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `litros` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `color` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `tamano` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `palet` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,                          
                              `capacidad` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `pack` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `impInt` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `codBarra` varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `topecant` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                              `iva` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                              `deposito` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `stock` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              `orden` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                              PRIMARY KEY (`codigo`) USING BTREE
                            ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;");
        $datos = "REPLACE INTO  `articulos` (codigo, descripcion," . $_listas . "linea, rubro, subrubro, marca, kilos, litros, color,tamano, palet,capacidad, pack, impInt, codBarra,topecant,iva,deposito,stock,orden) VALUE ";

        $num = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $datos.= "(";

            foreach($letters as $key => $column){
                $datos .= "'".$sheet->getCell($column . $row)->getValue() . "',";
            }
            $datos = substr($datos, 0, -1) . "),";

            $num++;
        }

        if ($num > 0)
            $re1 = Connection::runQuery(substr($datos, 0, -1));

//        saveLog(substr($datos, 0, -1), "excel/articulos");

        echo json_encode(["msj"=> "$num registros fueron actualizados con exito!","status"=>200]);
    } catch (Exception $e) {
        echo json_encode(["error"=> $e->getMessage(),"status"=>404]);
    }
}


//-----------------------------------------------------------------

function guardarVendedor($archivo){
    try{
        $carpeta = "excel";
        $archivo = "excel/" . $archivo;

        $inputFileType = PHPExcel_IOFactory::identify($archivo);

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $num = 0;
        $fila = "";
        Connection::runQuery("DROP TABLE IF EXISTS vendedores");
        Connection::runQuery("CREATE TABLE `vendedores`  (
                              `codigo` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `nombre` varchar(250) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `telefono` varchar(120) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `version` varchar(120) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              `supervisor` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
                              `atencion` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
                              PRIMARY KEY (`codigo`) USING BTREE
                            ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_spanish_ci ROW_FORMAT = Dynamic;");

        $datos = "REPLACE INTO `vendedores`(`codigo`, `nombre`, `telefono`, `version`, `supervisor`) VALUES";
        for ($row = 2; $row <= $highestRow; $row++) {
            $num++;
            $datos .= "('" . $sheet->getCell("A" . $row)->getValue() . "','" . $sheet->getCell("B" . $row)->getValue() . "','" . $sheet->getCell("C" . $row)->getValue() . "','" . $sheet->getCell("D" . $row)->getValue() . "','" . $sheet->getCell("E" . $row)->getValue() . "'),";
        }
        if ($num > 0){
            $re1 = Connection::runQuery(substr($datos, 0, -1));
//            saveLog(substr($datos, 0, -1), "excel/vendedores");

        }
        echo json_encode(["msj"=> "$num registros fueron actualizados con exito!","status"=>200]);
    } catch (Exception $e) {
        echo json_encode(["error"=> $e->getMessage(),"status"=>404]);
    }

}

function guardarMensajesConClientes($archivo, $titulo, $mensaje)
{
    $archivo = "excel/" . $archivo;

    $inputFileType = PHPExcel_IOFactory::identify($archivo);

    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objPHPExcel = $objReader->load($archivo);
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();

    $num = 0;
    $fila = "";


    $data = [
        [],
        [],
        [],
        [],
        []
    ];
    for ($row = 2; $row <= $highestRow; $row++) {
        $num++;
        $data[4][] = $sheet->getCell("A" . $row)->getValue();
    }

    $destinatarios = json_encode($data);
    if ($num == 0) {

    }

    $fecha = date('Y-m-d H:i:s');
    Connection::runQuery("INSERT INTO mensajes (titulo,mensaje,fecha,cantidad,destino,estado)
                                 VALUES ('$titulo','$mensaje','$fecha',0,'$destinatarios',1)");

    return "<FONT COLOR='green'>" . $num . " registros fueron creados con exito!</FONT>";
}

function editarMensajesConClientes($archivo = null, $id, $titulo, $mensaje)
{


    if ($archivo != null) {
        $archivo = "excel/" . $archivo;

        $inputFileType = PHPExcel_IOFactory::identify($archivo);

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $num = 0;
        $fila = "";


        $data = [
            [],
            [],
            [],
            [],
            []
        ];
        for ($row = 2; $row <= $highestRow; $row++) {
            $num++;
            $data[4][] = $sheet->getCell("A" . $row)->getValue();
        }

        $destinatarios = json_encode($data);

        Connection::runQuery("UPDATE mensajes SET titulo='$titulo', mensaje= '$mensaje', destino='$destinatarios' where id = $id ");

    } else {
        Connection::runQuery("UPDATE mensajes SET titulo='$titulo', mensaje= '$mensaje' where id = $id ");

    }

    return "<FONT COLOR='green'>Registros fueron actualizados con exito!</FONT>";
}

function guardarRepartidor($archivo)
{

    try {
        $archivo = "excel/" . $archivo;

        $inputFileType = PHPExcel_IOFactory::identify($archivo);

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $num = 0;
        Connection::runQuery("DROP TABLE IF EXISTS repartidores");
        Connection::runQuery("CREATE TABLE `repartidores`  (
                              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                              `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
                              `telefono` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
                              PRIMARY KEY (`id`) USING BTREE
                            ) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;");
        $datos = "REPLACE INTO `repartidores`( `nombre`, `telefono`) VALUES";
        for ($row = 2; $row <= $highestRow; $row++) {
            $num++;
            $datos .= "('" . $sheet->getCell("A" . $row)->getValue() . "','" . $sheet->getCell("B" . $row)->getValue()  . "'),";
        }
        if ($num > 0){
            $re1 = Connection::runQuery(substr($datos, 0, -1));
//            saveLog(substr($datos, 0, -1), "excel/repartidores");

        }
        echo json_encode(["msj"=> "$num registros fueron actualizados con exito!","status"=>200]);
    } catch (Exception $e) {
        echo json_encode(["error"=> $e->getMessage(),"status"=>404]);
    }

}
function saveLog($json, $nombre)
{
    //$nombre =date("dmY_His");
    $fp = fopen($nombre . ".txt", "w+b");
    if ($fp == false) {
        echo "Error al crear el archivo";
    } else {
        fwrite($fp, $json);
        fclose($fp);
    }
}

?>