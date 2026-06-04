<?php
//incluir la conexion de base de datos
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
class Vendedor{
	//implementamos nuestro constructor
	public function __construct(){

	}

	//metodo insertar regiustro
	public function insertar($codigo, $nombre,$telefono){
		$sql="INSERT INTO `vendedores`(codigo, `nombre`, `telefono`) VALUES ('$codigo','$nombre','$telefono')";
		return ejecutarConsulta($sql);
	}

	public function editar($codigo,$nombre,$telefono){
		$sql="UPDATE vendedores SET nombre='$nombre',telefono='$telefono' WHERE codigo=$codigo";
		return ejecutarConsulta($sql);
	}
	public function desactivar($idvendedores){
		$sql="UPDATE vendedores SET condicion='0' WHERE idvendedores='$idvendedores'";
		return ejecutarConsulta($sql);
	}
	public function activar($idcategoria){
		$sql="UPDATE vendedores SET condicion='1' WHERE idcategoria='$idcategoria'";
		return ejecutarConsulta($sql);
	}

	//metodo para mostrar registros
	public function mostrar($idvendedores){
		$sql="SELECT * FROM vendedores WHERE codigo='$idvendedores'";
		return ejecutarConsultaSimpleFila($sql);
	}

	//listar registros
	public function listar(){
		$sql="SELECT * FROM vendedores";
		return ejecutarConsulta($sql);
	}
	//listar y mostrar en selct
	public function select(){
		$sql="SELECT * FROM vendedores WHERE codigo=1";
		return ejecutarConsulta($sql);
	}
}

 ?>
