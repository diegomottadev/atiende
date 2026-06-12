<?php 
//incluir la conexion de base de datos
require "../config/Conexion.php";

define('__ROOT__', dirname(dirname(__FILE__)));
require(__ROOT__ . '/config/global.php');

class Consultas{

	private $responseWebMaster;

	public function __construct(){
		$this->responseWebMaster = getWebMasterConfig();
	}


	public function totalreclamosMes($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT estado,count(*) as cantidad FROM `reclamos`  WHERE   MONTH(fecha_ingreso)='".$periodo[1]."' and  YEAR(fecha_ingreso)='".$periodo[0]."'  GROUP by estado ORDER BY cantidad DESC";
		return ejecutarConsulta($sql);
	}

	public function totalconsultasMes($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT  COUNT(`consultaId`) as cantidad FROM `consultas`  WHERE   MONTH(fecha_ingreso)='".$periodo[1]."' and  YEAR(fecha_ingreso)='".$periodo[0]."' ORDER BY DATE_FORMAT(`fecha_ingreso`, '%m') ASC";
		return ejecutarConsulta($sql);
	}

	public function totalConsultasMesEstado($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT estado,count(*) as cantidad FROM `consultas`  WHERE   MONTH(fecha_ingreso)='".$periodo[1]."' and  YEAR(fecha_ingreso)='".$periodo[0]."'  GROUP by estado ORDER BY cantidad DESC";
		return ejecutarConsulta($sql);
	}

	public function solicitudesPorMes($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT  count(`id`) AS cantidad FROM solicitudes where MONTH(fecha)='".$periodo[1]."' and  YEAR(fecha)='".$periodo[0]."' ORDER BY DATE_FORMAT(`fecha`, '%m') ASC";
		return ejecutarConsulta($sql);
	}

	public function totalventaMes($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT IFNULL(SUM(subtotal),0) as total_venta FROM pedidos WHERE   MONTH(fecha)='".$periodo[1]."' and  YEAR(fecha)='".$periodo[0]."'";
		return ejecutarConsulta($sql);
	}

		public function totalventaDia($fecha){
			$periodo=explode("-",$fecha);
			$sql="SELECT IFNULL(SUM(subtotal),0) as total_venta FROM pedidos WHERE DAY(fecha)='". date('d') ."' and  MONTH(fecha)='".$periodo[1]."' and  YEAR(fecha)='".$periodo[0]."'";
			return ejecutarConsulta($sql);
		}

	public function clientesReclamosMes($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT count(*) as cantidad FROM `reclamos`  WHERE   MONTH(fecha_ingreso)='".$periodo[1]."' and  YEAR(fecha_ingreso)='".$periodo[0]."' ";
		return ejecutarConsulta($sql);
	}

	public function clientesVentasMes($fecha){
		$periodo=explode("-",$fecha);
		$sql="SELECT count(pedidoid) as cantidad FROM pedidos WHERE   MONTH(fecha)='".$periodo[1]."' and  YEAR(fecha)='".$periodo[0]."' GROUP by pedidoid ";
		return ejecutarConsulta($sql);
	}

	public function comprasfecha($fecha_inicio,$fecha_fin){
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha_inicio)) { $fecha_inicio=''; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha_fin)) { $fecha_fin=''; }
		$sql="SELECT DATE(i.fecha_hora) as fecha, u.nombre as usuario, p.nombre as proveedor, i.tipo_comprobante, i.serie_comprobante, i.num_comprobante, i.total_compra,i.impuesto,i.estado, empresa as ". DB_NAME ." FROM ingreso i INNER JOIN persona p ON i.idproveedor=p.idpersona INNER JOIN usuario u ON i.idusuario=u.idusuario WHERE DATE(i.fecha_hora)>='$fecha_inicio' AND DATE(i.fecha_hora)<='$fecha_fin'";
		return ejecutarConsulta($sql);
	}


	public function ventasfechacliente($fecha_inicio,$fecha_fin,$idcliente){
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha_inicio)) { $fecha_inicio=''; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha_fin)) { $fecha_fin=''; }
		$idcliente = limpiarCadena((string)$idcliente);
		$empresa = DB_NAME;
		$and = "";
		if (!empty($idcliente)) {
			// El <select> Cliente envía clientes.id (PK). Filtramos por p.id en ambos modos:
			// b2c une por p.id y b2b por p.codigo, pero p siempre es clientes → p.id es el valor del select.
			$and = "AND p.id='$idcliente' ";
		}
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT
			p.id as codigoCliente,
			DATE(v.fecha) as fechaPedido, 
			p.razonSocial, 
			p.ramo,
			p.direccion,
			p.localidad,
			p.latitud,
			p.longitud,
			v.telefono as telefono,
			p.zona, 
			v.pedidoid,
			v.producto, 
			v.descripcion ,
			p.lista as listaPrecio, 
			v.precio,
			 v.cantidad, 
			 v.subtotal, 
			 '$empresa' as empresa 
			 FROM pedidos v INNER JOIN clientes p ON v.clienteId=p.id WHERE DATE(v.fecha)>='$fecha_inicio' AND DATE(v.fecha)<='$fecha_fin'".$and;
			return ejecutarConsulta($sql);
		}else if($this->responseWebMaster['data']['b2b']){
			$sql="SELECT
			p.codigo as codigoCliente,
			DATE(v.fecha) as fechaPedido, 
			p.razonSocial, 
			p.ramo,
			p.direccion,
			p.localidad,
			p.latitud,
			p.longitud,
			v.telefono as telefono,
			p.zona, 
			v.pedidoid,
			v.producto, 
			v.descripcion ,
			p.lista as listaPrecio, 
			v.precio,
			 v.cantidad, 
			 v.subtotal, 
			 '$empresa' as empresa 
			 FROM pedidos v INNER JOIN clientes p ON v.clienteId=p.codigo WHERE DATE(v.fecha)>='$fecha_inicio' AND DATE(v.fecha)<='$fecha_fin' ".$and;
			return ejecutarConsulta($sql);
		}
	}

	public function pedidosfechacliente($fecha_inicio,$fecha_fin,$idcliente){
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha_inicio)) { $fecha_inicio=''; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha_fin)) { $fecha_fin=''; }
		$idcliente = limpiarCadena((string)$idcliente);
		$and = "";
		if (!empty($idcliente)) {
			// idem ventasfechacliente: el select envía clientes.id (PK) → filtrar por p.id
			$and = "AND p.id='$idcliente' ";
		}
		// Mismo JOIN que ventasfechacliente para que el conteo coincida con la tabla
		$join = ($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'])
			? "v.clienteId=p.id"
			: "v.clienteId=p.codigo";
		$sql="SELECT COUNT(v.pedidoid) as cantidad FROM pedidos v INNER JOIN clientes p ON $join WHERE DATE(v.fecha)>='$fecha_inicio' AND DATE(v.fecha)<='$fecha_fin' ".$and." GROUP BY v.pedidoid";
		return ejecutarConsulta($sql);

	}



	public function totalcomprahoy(){
		$sql="SELECT IFNULL(SUM(total_compra),0) as total_compra FROM ingreso WHERE DATE(fecha_hora)=curdate()";
		return ejecutarConsulta($sql);
	}

	public function totalventahoy(){
		$sql="SELECT IFNULL(SUM(total_venta),0) as total_venta FROM venta WHERE DATE(fecha_hora)=curdate()";
		return ejecutarConsulta($sql);
	}


	public function comprasultimos_10dias($mes){
		//$sql="SELECT DATE_FORMAT(fecha, '%d') AS fecha, SUM(`subtotal`) AS total FROM pedidos WHERE MONTH(`fecha`)=MONTH( '$mes-01 00:00:00') AND YEAR(fecha)=YEAR('$mes-01 00:00:00')  GROUP BY DATE_FORMAT(fecha, '%d') ASC";
		$sql="SELECT DATE_FORMAT(fecha, '%d') AS fecha, REPLACE(FORMAT(SUM(`subtotal`),2,'de_DE'),',00','') AS total FROM pedidos WHERE MONTH(`fecha`)=MONTH( '$mes-01 00:00:00') AND YEAR(fecha)=YEAR('$mes-01 00:00:00')  GROUP BY DATE_FORMAT(fecha, '%d') ORDER BY DATE_FORMAT(fecha, '%d') ASC";
		return ejecutarConsulta($sql);
	}


	public function solicitudesPorMesBar($year){
		$year = (int)$year;

		$sql="SELECT DATE_FORMAT(fecha, '%M') AS fecha, count(`id`) AS total FROM solicitudes WHERE DATE_FORMAT(fecha, '%Y')=$year GROUP BY DATE_FORMAT(`fecha`, '%M') ORDER BY DATE_FORMAT(fecha, '%m') ";
		return ejecutarConsulta($sql);
	}

	public function ventas_x_Mes($year){
		$year = (int)$year;
		//$sql="SELECT DATE_FORMAT(fecha, '%M') AS fecha, SUM(`subtotal`) AS total FROM pedidos WHERE DATE_FORMAT(fecha,'%Y')=$year GROUP BY DATE_FORMAT(fecha, '%M') ORDER BY DATE_FORMAT(fecha, '%m') ASC";
		$sql = "SELECT DATE_FORMAT(fecha, '%M') AS fecha, REPLACE(FORMAT(SUM(`subtotal`),2,'de_DE'),',00','') AS total FROM pedidos WHERE DATE_FORMAT(fecha,'%Y')=$year GROUP BY DATE_FORMAT(fecha, '%M') ORDER BY DATE_FORMAT(fecha, '%m') ASC";
		return ejecutarConsulta($sql);
	}

	public function ventas_x_dia(){
		$sql="SELECT DATE_FORMAT(fecha, '%d') AS fecha, SUM(`subtotal`) AS total FROM pedidos GROUP BY DATE_FORMAT(fecha, '%d') ORDER BY DATE_FORMAT(fecha, '%d') ASC";

		return ejecutarConsulta($sql);
	}


	public function pedidos_x_Mes($year){
		$year = (int)$year;
		$sql="SELECT DATE_FORMAT(fecha, '%M') AS fecha, count( distinct `pedidoid`) AS total FROM pedidos WHERE DATE_FORMAT(fecha,'%Y')=$year GROUP BY DATE_FORMAT(fecha, '%M') ORDER BY DATE_FORMAT(fecha, '%m') ASC";		
		return ejecutarConsulta($sql);
	}

	public function ticket_promedio_Mes($year){
		$year = (int)$year;
		// $sql="SELECT DATE_FORMAT(fecha, '%M') AS fecha, ROUND(SUM(`subtotal`)/count( distinct `pedidoid`),2) AS total FROM pedidos WHERE DATE_FORMAT(fecha,'%Y')=$year GROUP BY DATE_FORMAT(fecha, '%M') ORDER BY DATE_FORMAT(fecha, '%m') ASC";		
		$sql = " SELECT DATE_FORMAT(fecha, '%M') AS fecha, REPLACE(FORMAT((ROUND(SUM(`subtotal`)/count( distinct `pedidoid`),2)),2,'de_DE'),',00','') AS total FROM pedidos WHERE DATE_FORMAT(fecha,'%Y')=$year GROUP BY DATE_FORMAT(fecha, '%M') ORDER BY DATE_FORMAT(fecha, '%m') ASC";
		return ejecutarConsulta($sql);
	}

	public function reclamosMes($year){
		$year = (int)$year;
		$sql="SELECT DATE_FORMAT(`fecha_ingreso`, '%M') AS fecha, COUNT(`clienteId`) AS total FROM reclamos WHERE DATE_FORMAT(fecha_ingreso,'%Y')=$year  GROUP BY DATE_FORMAT(`fecha_ingreso`, '%M') ORDER BY DATE_FORMAT(`fecha_ingreso`, '%m') ASC";
		return ejecutarConsulta($sql);
	}


	public function consultasMes(){
		$sql="SELECT DATE_FORMAT(`fecha_ingreso`, '%M') AS fecha, COUNT(`consultaId`) AS total FROM consultas GROUP BY DATE_FORMAT(`fecha_ingreso`, '%M') ORDER BY DATE_FORMAT(`fecha_ingreso`, '%m') ASC";
		return ejecutarConsulta($sql);
	}

	public function reclamos_x_Motivos($year){
		$year = (int)$year;
		$sql="SELECT motivo  ,count(*) as cantidad FROM `reclamos` WHERE DATE_FORMAT(fecha_ingreso,'%Y')=$year GROUP BY motivo order by cantidad DESC ";
		return ejecutarConsulta($sql);
	}

	public function consultasPorMotivos(){
		$sql="SELECT motivo  ,count(*) as cantidad FROM `consultas` GROUP BY motivo order by cantidad DESC ";
		return ejecutarConsulta($sql);
	}

	public function reclamos_x_Sector($year){
		$year = (int)$year;
		$sql="SELECT areas.area as sector ,count(*) as cantidad FROM `reclamos` INNER JOIN areas ON reclamos.area=areas.id WHERE DATE_FORMAT(reclamos.fecha_ingreso,'%Y')=$year GROUP by areas.area ORDER BY cantidad DESC ";
		return ejecutarConsulta($sql);
	}

	public function consultasPorSector($year){
		$year = (int)$year;
		$sql="SELECT areas_consultas.area as sector , count(*) as cantidad FROM `consultas` INNER JOIN areas_consultas ON consultas.area=areas_consultas.id WHERE DATE_FORMAT(fecha_ingreso, '%Y')=$year GROUP by areas_consultas.area ORDER BY cantidad DESC ";
		return ejecutarConsulta($sql);
	}


	public function ventasultimos_12meses($mes){
		$sql=" SELECT DATE_FORMAT(fecha_ingreso, '%d') AS fecha,count(*) AS total FROM `reclamos` WHERE MONTH(fecha_ingreso)=MONTH('$mes-01 00:00:00') AND YEAR(fecha_ingreso)=YEAR('$mes-01 00:00:00') GROUP BY DATE_FORMAT(fecha_ingreso, '%d') ORDER BY DATE_FORMAT(fecha_ingreso, '%d') ASC";
		return ejecutarConsulta($sql);
	}

	public function consultasPorMesLine($year){
		$year = (int)$year;
		$sql=" SELECT DATE_FORMAT(fecha_ingreso, '%M') AS fecha, COUNT(`clienteId`) AS total FROM `consultas` WHERE DATE_FORMAT(fecha_ingreso, '%Y')=$year GROUP BY DATE_FORMAT(`fecha_ingreso`, '%M') ORDER BY DATE_FORMAT(`fecha_ingreso`, '%m') ASC";
		return ejecutarConsulta($sql);
	}
}



 ?>
