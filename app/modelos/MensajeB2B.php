<?php
//incluir la conexion de base de datos
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
require (__ROOT__.'/config/Connection.php');
class MensajeB2B{

	public function __construct(){

	}
	public function listar(){
		$sql="SELECT *,  DATE_FORMAT(fecha, '%d/%m/%Y') as fechaFormato FROM mensajes";
		return ejecutarConsulta($sql);
	}
	public function show($id){
		return ejecutarConsultaSimpleFila('SELECT * FROM `mensajes` where id='.$id);
	}

	public function eliminar($id){
		return ejecutarConsulta('DELETE  FROM `mensajes` where id='.$id );
	}

	public  function  listarContactos ($id){
		$request = Connection::runQuery("SELECT `destino` FROM `mensajes` WHERE `id` = '" . $id . "'  ");
		$json = "";
		if ($request)
			while ($row = mysqli_fetch_assoc($request)) {
				$json = $row["destino"];
			}
		$obj = json_decode($json, true);
		$and = " WHERE 0";
		if (count($obj[4]) > 0) {
			$codigo = substr(json_encode($obj[4]), 1, -1);
			$and = ' where  contacto.codigo IN (' . $codigo . ')';
		}


		return ejecutarConsulta("SELECT DISTINCT  contacto.codigo,(SELECT telefono  FROM  telefonos where clienteId = contacto.codigo ORDER BY telefono DESC limit 1) as telefono, contacto.razonSocial, contacto.direccion, contacto.zona, contacto.ramo, contacto.localidad FROM clientes contacto INNER JOIN telefonos telefono ON contacto.codigo = telefono.clienteId" . $and);
	}
}

 ?>
