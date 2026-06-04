<?php 
//incluir la conexion de base de datos
require "../config/Conexion.php";
class Motivo{


	//implementamos nuestro constructor
	public function __construct(){

	}

	//metodo insertar regiustro
	public function insertar($codigo,$motivo,$idarea,$ir,$guardar){
		$sql="INSERT INTO `menuitem`(`opcionId`, `opcion`, `menuId`, `guardar`, `area`) VALUES ( '$codigo', '$motivo','$ir',0, '$idarea') ";
		return ejecutarConsulta($sql);
	}

	public function editar($id,$codigo,$motivo,$idarea,$ir,$guardar){
		$sql="UPDATE menuitem SET opcionId ='$codigo', opcion='$motivo',area='$idarea',guardar=0,menuId='$ir' WHERE id = '$id' ";
		return ejecutarConsulta($sql);
	}

	//funcion para eliminar datos
	public function eliminar($id){
		$sql="DELETE FROM menuitem WHERE id=$id ";
		return ejecutarConsulta($sql);
	}

	//metodo para mostrar registros
	public function mostrar($id){
		$sql="SELECT * FROM `menuitem` WHERE `id` = $id ";
		return ejecutarConsultaSimpleFila($sql);
	}

	//listar registros
	public function listarp(){
		$sql="SELECT menuitem.*,areas.area  from menuitem,areas where areas.id=menuitem.area order by  opcionId ";
		return ejecutarConsulta($sql);
	}

	//filtro segun parametros 
	public function filterMotivos($select, $where){
		$sql = "SELECT $select FROM menuitem WHERE $where";
		return ejecutarConsulta($sql);
	}
}

 ?>
