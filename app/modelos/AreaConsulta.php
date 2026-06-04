<?php
//incluir la conexion de base de datos
require "../config/Conexion.php";
class AreaConsulta{


    //implementamos nuestro constructor
    public function __construct(){

    }

//metodo insertar regiustro
    public function insertar($nombre,$telefono){
        $sql="INSERT INTO `areas_consultas`(`area`, `telefono`, `activo`) VALUES ('$nombre','$telefono',1)";
        return ejecutarConsulta($sql);
    }

//`area`, `telefono`, `activo`

    public function editar($idpersona,$nombre,$telefono){
        $sql="UPDATE areas_consultas SET  area='$nombre',telefono='$telefono' WHERE id='$idpersona'";
        return ejecutarConsulta($sql);
    }
//funcion para eliminar datos
    public function eliminar($idpersona){
        $sql="DELETE FROM areas_consultas WHERE id='$idpersona'";
        return ejecutarConsulta($sql);
    }

//metodo para mostrar registros
    public function mostrar($idpersona){
        $sql="SELECT * FROM `areas_consultas` WHERE id ='$idpersona'";
        return ejecutarConsultaSimpleFila($sql);
    }

//listar registros
    public function listarp(){
        $sql="SELECT * FROM `areas_consultas` ";
        return ejecutarConsulta($sql);
    }


    public function select(){
        $sql="SELECT * FROM `areas_consultas` where activo=1";
        return ejecutarConsulta($sql);
    }

}

?>
