<?php 
//incluir la conexion de base de datos
require "../config/Conexion.php";
class Venta{

 	private $responseWebMaster;
	public function __construct(){
		$this->responseWebMaster = getWebMasterConfig();
	}

	public function anular($idventa){
		$id = intval($idventa);
		// Toggle: si está Anulado(3) vuelve a Pendiente(0); si no, lo Anula(3).
		ejecutarConsulta("UPDATE pedidos SET flag = IF(flag=3, 0, 3) WHERE pedidoid='$id'");
		$row = ejecutarConsultaSimpleFila("SELECT flag FROM pedidos WHERE pedidoid='$id' LIMIT 1");
		return $row ? $row['flag'] : null;
	}

	public function editarEstado($idventa){
		$id = intval($idventa);
		// Toggle: Entregado(2) <-> Pendiente(0). No afecta Anulado(3).
		ejecutarConsulta("UPDATE pedidos SET flag = IF(flag=2, 0, 2) WHERE pedidoid='$id' AND flag <> 3");
		$row = ejecutarConsultaSimpleFila("SELECT flag FROM pedidos WHERE pedidoid='$id' LIMIT 1");
		return $row ? $row['flag'] : null;
	}

	//implementar un metodopara mostrar los datos de unregistro a modificar
	public function mostrar($idventa){
		$idventa = (int)$idventa;
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT p.pedidoid,p.fecha AS fecha,p.clienteId,c.razonSocial,c.direccion,flag as estado, SUM(CAST(p.subtotal AS DECIMAL(25,2))) AS total, p.telefono FROM pedidos p INNER JOIN clientes c ON c.id=p.clienteId WHERE p.pedidoid='$idventa' GROUP BY p.pedidoid";
		}else if($this->responseWebMaster['data']['b2b']){
			$sql="SELECT p.pedidoid,p.fecha AS fecha,p.clienteId,c.razonSocial,c.direccion,flag as estado, SUM(CAST(p.subtotal AS DECIMAL(25,2))) AS total, p.telefono FROM pedidos p INNER JOIN clientes c ON c.codigo=p.clienteId WHERE p.pedidoid='$idventa' GROUP BY p.pedidoid";
		}
		return ejecutarConsultaSimpleFila($sql);
	}

	public function traerTelefono($idventa){
		$idventa = (int)$idventa;
		$sql="SELECT telefonos.telefono FROM `pedidos`,telefonos WHERE pedidos.clienteId= telefonos.clienteId and pedidos.pedidoid= $idventa LIMIT 1";
		return ejecutarConsultaSimpleFila($sql);
	}
	public function listarDetalle($idventa){
		$idventa = (int)$idventa;
		$sql="SELECT p.comment, p.pedidoid,p.producto,a.descripcion,p.cantidad,p.precio,p.descuento, p.subtotal FROM pedidos p INNER JOIN articulos a ON p.producto=a.codigo WHERE p.pedidoid='$idventa'";

		return ejecutarConsulta($sql) ;
	}

	public function listarMensajes($idventa){
		$idventa = (int)$idventa;
		$sql="SELECT * FROM `fidelizar` WHERE `pedidoid` = $idventa order by id desc ";
		//return "SELECT * FROM `fidelizar` WHERE `pedidoid` = $idventa ";
		return ejecutarConsulta($sql) ;
	}

	public function listarMensajesNoLeidos($idventa){
		$idventa = (int)$idventa;
		$sql="SELECT count(id) as noleidos FROM `fidelizar` WHERE `pedidoid` = $idventa AND `estado`=0 AND `tipo`=0 ";
		return ejecutarConsultaSimpleFila($sql) ;
	}

	public function marcarLeido($idventa){
		$idventa = (int)$idventa;
		$sql="UPDATE fidelizar SET estado=1 WHERE pedidoid=$idventa and tipo =0";
		return ejecutarConsulta($sql);
	}

	public function listarPedidos(){
		// Evita N+1: el listado traía nombre/teléfono del vendedor (Vendedor::mostrar),
		// teléfono del cliente (Persona::mostrar) y el conteo de mensajes no leídos
		// (listarMensajesNoLeidos) con UNA consulta por pedido. Ahora se resuelve todo
		// en esta query con LEFT JOIN vendedores + LEFT JOIN agregado de fidelizar.
		$noleidos = "LEFT JOIN (SELECT pedidoid, COUNT(id) AS noleidos FROM fidelizar WHERE estado=0 AND tipo=0 GROUP BY pedidoid) f ON f.pedidoid=p.pedidoid";
		$cols = "vendedorId as vendedor, p.pedidoid, CONCAT(DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha,p.clienteId,c.razonSocial,c.telefono AS clienteTelefono,flag as estado, ROUND(sum(p.subtotal),2) AS total, p.telefono,p.pagado,p.fecha_asignacion as fechaAsignacion, p.fecha_notificacion as fechaNotificacion, v.nombre AS vendedorNombre, v.telefono AS vendedorTelefono, COALESCE(f.noleidos,0) AS noleidos";
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT $cols FROM pedidos p INNER JOIN clientes c ON c.id=p.clienteId LEFT JOIN vendedores v ON v.codigo=p.vendedorId $noleidos GROUP BY p.pedidoid ORDER BY p.fecha DESC ";
		}else if ($this->responseWebMaster['data']['b2b'] ){
			$sql="SELECT $cols FROM pedidos p INNER JOIN clientes c ON c.codigo=p.clienteId LEFT JOIN vendedores v ON v.codigo=p.vendedorId $noleidos GROUP BY p.pedidoid ORDER BY p.fecha DESC ";
		}
		return ejecutarConsulta($sql);
	}

	public function listarObs(){
		$sql="SELECT pedidoid, MIN(`descripcion`) AS descripcion FROM `pedidos` WHERE `producto` like '.001' GROUP by `pedidoid`";
		return ejecutarConsulta($sql);
	}

	public function ventacabecera($idventa){
		$idventa = (int)$idventa;
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql= "SELECT v.pedidoid, v.clienteId, p.razonSocial AS cliente,p.direccion, p.localidad, p.telefono, v.pedidoid, v.pedidoid, v.fecha AS fecha, v.subtotal, p.codigo FROM pedidos v INNER JOIN clientes p ON p.id=v.clienteId WHERE v.pedidoid='$idventa'";
		}else if ($this->responseWebMaster['data']['b2b'] ){
			$sql= "SELECT v.pedidoid, v.clienteId, p.razonSocial AS cliente,p.direccion, p.localidad, p.telefono, v.pedidoid, v.pedidoid, v.fecha AS fecha, v.subtotal, p.codigo FROM pedidos v INNER JOIN clientes p ON p.codigo=v.clienteId WHERE v.pedidoid='$idventa'";
		}
		return ejecutarConsulta($sql);
	}

	public function ventadetalles($idventa){
		$idventa = (int)$idventa;
		$sql="SELECT a.descripcion AS articulo, a.codigo, d.cantidad, d.precio, d.descuento, ROUND((d.cantidad*d.precio-d.descuento),2) AS subtotal , dato9 FROM pedidos d INNER JOIN articulos a ON d.producto=a.codigo WHERE d.pedidoid='$idventa'";
		return ejecutarConsulta($sql);
	}

	public function insertarMensaje($clienteid,$pedidoid,$mensaje,$tipo){
		$sql="INSERT INTO `fidelizar`(`fecha`, `clienteid`, `pedidoid`, `tipo`, `mensaje`, `estado`) VALUES (NOW(),'$clienteid','$pedidoid','$tipo','$mensaje',0)";
		ejecutarConsulta("UPDATE fidelizar SET estado=1 WHERE pedidoid=$pedidoid ");
		return ejecutarConsulta($sql);
	}

}

 ?>
