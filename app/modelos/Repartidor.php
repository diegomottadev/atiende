<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');

class Repartidor
{
    public function __construct()
    {

    }

    public function insertar($nombre, $telefono){
        $sql="INSERT INTO repartidores (nombre,telefono) VALUES ('$nombre','$telefono')";
        return ejecutarConsulta($sql);
    }

    public function editar($id,$nombre, $telefono){
        $sql="UPDATE repartidores SET nombre='$nombre', telefono='$telefono'  WHERE id=$id";
        return ejecutarConsulta($sql);
    }

    public function eliminar($id){
        $sql="DELETE FROM repartidores WHERE id='$id'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($id){
        $sql="SELECT *  FROM `repartidores` WHERE id='$id' ";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar(){
        $sql="SELECT * from repartidores";
        return ejecutarConsulta($sql);
    }
}
?>
