<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');

class MotivoConsulta{

    public function __construct(){

    }

    public function insertar($codigo,$motivo,$idarea,$ir,$guardar){
        $sql="INSERT INTO `motivo_consultas`(`opcionId`, `opcion`, `menuId`, `guardar`, `area`) VALUES ( '$codigo', '$motivo','$ir',0, '$idarea') ";
        return ejecutarConsulta($sql);
    }

    public function editar($id,$codigo,$motivo,$idarea,$ir,$guardar){
        $sql="UPDATE motivo_consultas SET opcionId ='$codigo', opcion='$motivo',area='$idarea',guardar=0,menuId='$ir' WHERE id = '$id' ";
        return ejecutarConsulta($sql);
    }

    public function eliminar($id){
        $sql="DELETE FROM motivo_consultas WHERE id=$id ";
        return ejecutarConsulta($sql);
    }

    public function mostrar($id){
        $sql="SELECT * FROM `motivo_consultas` WHERE `id` = $id ";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listarp(){
        $sql="SELECT motivo_consultas.*,areas_consultas.area as area from motivo_consultas,areas_consultas where areas_consultas.id=motivo_consultas.area order by  opcionId ";
        return ejecutarConsulta($sql);
    }

    //filtro segun parametros
    public function filters($select, $where){
        $sql = "SELECT $select FROM motivo_consultas WHERE $where";
        return ejecutarConsulta($sql);
    }
}

?>
