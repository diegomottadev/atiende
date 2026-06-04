<?php 
//incluir la conexion de base de datos
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
class Reclamo{

	private $responseWebMaster;

	public function __construct(){
		$this->responseWebMaster = getWebMasterConfig();
	}

	public function editar($idreclamo,$resolucion,$estado,$canal){
		$sql="UPDATE reclamos SET resolucion='$resolucion', estado='$estado' , fecha_resolucion =NOW() WHERE reclamoId=$idreclamo";
		ejecutarConsulta("INSERT INTO `msj_reclamos`(`id_reclamo`, tipo,`fecha`, `mensaje`,respondido, `estado`,`canal`) VALUES ($idreclamo,0,NOW(),'$resolucion',1,'$estado','$canal')");
		ejecutarConsulta("UPDATE `msj_reclamos` SET `respondido`=1  WHERE `id_reclamo` = ".$idreclamo);
		return ejecutarConsulta($sql);
	}
	public function insertarMensaje($idreclamo,$resolucion,$estado,$canal){
		$sql= "INSERT INTO `msj_reclamos`(`id_reclamo`, tipo,`fecha`, `mensaje`, respondido,`estado`,`canal`) VALUES ($idreclamo,1,NOW(),'$resolucion',0,'$estado',$canal )";
		return ejecutarConsulta($sql);
	}

	public function eliminar($idpersona){
		$sql="DELETE FROM persona WHERE idpersona='$idpersona'";
		return ejecutarConsulta($sql);
	}

	public function mostrar($idreclamo){
		$sql="SELECT reclamos.*, reclamos.area as _area FROM `reclamos` WHERE reclamoId='$idreclamo' ";
		return ejecutarConsultaSimpleFila($sql);
	}

	public function listarp(){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT reclamos.*, clientes.razonSocial, 
				CONCAT(DATE_FORMAT(reclamos.fecha_ingreso, '%d/%m/%Y %H:%i'),' hs') AS _fecha,
				CONCAT(DATE_FORMAT(reclamos.fecha_resolucion, '%d/%m/%Y %H:%i'),' hs') AS _fecha_resolucion, areas.area as _area 
				FROM `reclamos`  LEFT JOIN  areas ON  reclamos.area = areas.id 
				LEFT JOIN clientes ON clientes.id = reclamos.clienteId 
				ORDER BY reclamos.reclamoId DESC";
		}else if ($this->responseWebMaster['data']['b2b'] ){
			$sql="SELECT reclamos.*, clientes.razonSocial, 
				CONCAT(DATE_FORMAT(reclamos.fecha_ingreso, '%d/%m/%Y %H:%i'),' hs') AS _fecha,
				CONCAT(DATE_FORMAT(reclamos.fecha_resolucion, '%d/%m/%Y %H:%i'),' hs') AS _fecha_resolucion, areas.area as _area 
				FROM `reclamos`  LEFT JOIN  areas ON  reclamos.area = areas.id 
				LEFT JOIN clientes ON clientes.codigo = reclamos.clienteId 
				ORDER BY reclamos.reclamoId DESC";		}
		return ejecutarConsulta($sql);
	}

	public function listarRespuesta(){
		$sql="SELECT `id_reclamo` FROM msj_reclamos WHERE id IN (SELECT MAX(id) FROM msj_reclamos GROUP BY `id_reclamo`) and `respondido` = 0 ORDER BY id  ASC";
		return ejecutarConsulta($sql);
	}

	public function listarMensajes($idventa){
		$sql="SELECT * FROM `msj_reclamos` WHERE `id_reclamo` = $idventa ";
		return ejecutarConsulta($sql) ;
	}
	public function listarChats($id_reclamo){
		$sql = "SELECT 
					fecha,DATE_FORMAT(fecha, '%e de %M %Y ') as fechaMensaje,
					DATE_FORMAT(fecha, ' %H:%i')  as hora, mensaje,
					id_reclamo, canal, CASE WHEN canal= '-1' THEN 'Cliente' 
					WHEN canal = '0' THEN 'Administrativo' 
					WHEN canal = '1' THEN 'Supervisor' END AS usuario 
				FROM `msj_reclamos` WHERE `id_reclamo` = $id_reclamo ";
		return ejecutarConsulta($sql);
	}

	public function listarRespReclamo($reclamoId){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT reclamos.*,clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `reclamos` LEFT JOIN clientes ON reclamos.clienteId = clientes.id WHERE reclamoId like '$reclamoId' and estado <> 'Finalizado'";
		}else if ($this->responseWebMaster['data']['b2b'] ){
			$sql="SELECT reclamos.*,clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `reclamos` LEFT JOIN clientes ON reclamos.clienteId = clientes.codigo WHERE reclamoId like '$reclamoId' and estado <> 'Finalizado'";
		}
		return ejecutarConsulta($sql);
	}
}

 ?>
