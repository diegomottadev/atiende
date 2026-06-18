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
		$idreclamo = (int)$idreclamo;
		$sql="UPDATE reclamos SET resolucion='$resolucion', estado='$estado' , fecha_resolucion =NOW() WHERE reclamoId=$idreclamo";
		ejecutarConsulta("INSERT INTO `msj_reclamos`(`id_reclamo`, tipo,`fecha`, `mensaje`,respondido, `estado`,`canal`) VALUES ($idreclamo,0,NOW(),'$resolucion',1,'$estado','$canal')");
		ejecutarConsulta("UPDATE `msj_reclamos` SET `respondido`=1  WHERE `id_reclamo` = ".$idreclamo);
		return ejecutarConsulta($sql);
	}
	public function insertarMensaje($idreclamo,$resolucion,$estado,$canal){
		$idreclamo = (int)$idreclamo;
		$sql= "INSERT INTO `msj_reclamos`(`id_reclamo`, tipo,`fecha`, `mensaje`, respondido,`estado`,`canal`) VALUES ($idreclamo,1,NOW(),'$resolucion',0,'$estado',$canal )";
		return ejecutarConsulta($sql);
	}

	public function eliminar($idpersona){
		$sql="DELETE FROM persona WHERE idpersona='$idpersona'";
		return ejecutarConsulta($sql);
	}

	public function mostrar($idreclamo){
		$idreclamo = (int)$idreclamo;
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
		$idventa = (int)$idventa;
		$sql="SELECT * FROM `msj_reclamos` WHERE `id_reclamo` = $idventa ";
		return ejecutarConsulta($sql) ;
	}
	public function listarChats($id_reclamo){
		$id_reclamo = (int)$id_reclamo;
		$sql = "SELECT
					fecha,DATE_FORMAT(fecha, '%e de %M %Y ') as fechaMensaje,
					DATE_FORMAT(fecha, ' %H:%i')  as hora, mensaje,
					id_reclamo, canal, CASE WHEN canal= '-1' THEN 'Cliente' 
					WHEN canal = '0' THEN 'Administrativo' 
					WHEN canal = '1' THEN 'Supervisor' END AS usuario 
				FROM `msj_reclamos` WHERE `id_reclamo` = $id_reclamo ";
		return ejecutarConsulta($sql);
	}

	// ============================================================
	//  Server-side processing (DataTables) — escala a millones de filas
	// ============================================================

	// Whitelist: índice de columna DataTables → columna REAL de `reclamos`.
	// Solo estas columnas pueden ordenarse/filtrarse (evita SQL injection por nombre de columna).
	// Col 0 (botón editar), col 6 (razonSocial: viene de JOIN clientes) y col 10 (#: calculada)
	// NO son columnas reales de `reclamos` → no figuran.
	// Col 1 (badge Estado) → columna base `estado`; col 3 (Fecha) → `fecha_ingreso`;
	// col 8 (Area) → columna base `area`; col 9 (Fecha Res.) → `fecha_resolucion`.
	public function columnasReclamoServerSide(){
		return array(
			1=>'estado',
			2=>'reclamoId',
			3=>'fecha_ingreso',
			4=>'telefono',
			5=>'clienteId',
			7=>'motivo',
			8=>'area',
			9=>'fecha_resolucion'
		);
	}

	// Devuelve solo la página pedida + totales (delega en el helper genérico Datatable).
	// fetch 'assoc' para poder decorar cada fila con las columnas calculadas (badge, fechas, etc.).
	public function listarpServerSide($start, $length, $buscar, $order, $columns){
		global $conexion;
		require_once dirname(__DIR__).'/config/Datatable.php';
		$map = $this->columnasReclamoServerSide();
		$req = array('start'=>$start, 'length'=>$length, 'search'=>array('value'=>$buscar), 'order'=>$order, 'columns'=>$columns);
		// Búsqueda global sobre las columnas de texto visibles de `reclamos`.
		$searchCols = array('reclamoId','telefono','clienteId','motivo','area','estado');
		return Datatable::serverSide($conexion, 'reclamos', $map, $searchCols, array(
			'select'       => '`reclamoId`,`fecha_ingreso`,`clienteId`,`telefono`,`nick`,`motivo`,`area`,`fecha_resolucion`,`resolucion`,`estado`',
			'fetch'        => 'assoc',
			'defaultOrder' => '`reclamoId` DESC',
			'request'      => $req
		));
	}

	// Valores distintos de una columna de `reclamos` (para poblar los dropdowns de filtro)
	public function distinctReclamo($colNombre){
		global $conexion;
		require_once dirname(__DIR__).'/config/Datatable.php';
		return Datatable::distinct($conexion, 'reclamos', $colNombre, array_values($this->columnasReclamoServerSide()));
	}

	// Mapa id_area → nombre de área (tabla `areas` es chica). Para decorar la col 8 con el
	// mismo valor que daba el LEFT JOIN areas del listarp() original.
	public function mapaAreas(){
		global $conexion;
		$out = array();
		if ($r = $conexion->query("SELECT `id`,`area` FROM `areas`")) {
			while ($row = $r->fetch_assoc()) { $out[(string)$row['id']] = $row['area']; }
		}
		return $out;
	}

	// Pares {id, area} para el dropdown de filtro de Área (muestra el NOMBRE, filtra por el ID
	// que es lo que guarda reclamos.area). Solo áreas presentes evita opciones muertas no es crítico.
	public function listaAreas(){
		global $conexion;
		$out = array();
		if ($r = $conexion->query("SELECT `id`,`area` FROM `areas` ORDER BY `area` ASC")) {
			while ($row = $r->fetch_assoc()) { $out[] = array('id'=>$row['id'], 'area'=>$row['area']); }
		}
		return $out;
	}

	// Mapa clienteId → razonSocial (tabla `clientes` es chica). La clave de join depende del
	// modo del tenant (b2c/mix usan clientes.id; b2b usa clientes.codigo), igual que listarp().
	public function mapaClientes(){
		global $conexion;
		$out = array();
		$key = ($this->responseWebMaster['data']['b2b']) ? 'codigo' : 'id';
		if ($r = $conexion->query("SELECT `$key` k, `razonSocial` rs FROM `clientes`")) {
			while ($row = $r->fetch_assoc()) { $out[(string)$row['k']] = $row['rs']; }
		}
		return $out;
	}

	public function listarRespReclamo($reclamoId){
		$reclamoId = (int)$reclamoId;
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
