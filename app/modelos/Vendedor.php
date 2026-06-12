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
		$sql="INSERT INTO `vendedores`(codigo, `nombre`, `telefono`, `supervisor`) VALUES ('$codigo','$nombre','$telefono','')";
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

	// ============================================================
	//  Clientes del vendedor (solapa "Clientes") — server-side, paginado
	// ============================================================

	// Whitelist columna DataTables → columna real de `clientes`. Col 0 = botones (no mapeada).
	private function columnasClienteVendedor(){
		return array(1=>'codigo', 2=>'razonSocial', 3=>'localidad', 4=>'telefono', 5=>'vendedor');
	}

	// $modo: 'asignados' (vendedor = $vendedor) | 'disponibles' (vendedor <> $vendedor).
	// El filtro por vendedor es fijo del backend (extraWhere, prepared statement).
	public function clientesServerSide($vendedor, $modo, $req){
		global $conexion;
		require_once dirname(__DIR__).'/config/Datatable.php';
		$map = $this->columnasClienteVendedor();
		$searchCols = array('codigo','razonSocial','localidad','telefono');
		$extraWhere = ($modo === 'asignados')
			? array('sql'=>'`vendedor` = ?',  'params'=>array($vendedor), 'types'=>'s')
			: array('sql'=>'`vendedor` <> ?', 'params'=>array($vendedor), 'types'=>'s');
		return Datatable::serverSide($conexion, 'clientes', $map, $searchCols, array(
			'select'       => '`codigo`,`razonSocial`,`localidad`,`telefono`,`vendedor`',
			'fetch'        => 'assoc',
			'defaultOrder' => '`codigo` DESC',
			'extraWhere'   => $extraWhere,
			'request'      => $req
		));
	}

	// Reasigna un cliente a un vendedor (mover). Devuelve filas afectadas.
	public function asignarCliente($codigoCliente, $vendedor){
		global $conexion;
		$stmt = $conexion->prepare("UPDATE clientes SET vendedor = ? WHERE codigo = ?");
		$stmt->bind_param('ss', $vendedor, $codigoCliente);
		$stmt->execute();
		$aff = $stmt->affected_rows;
		$stmt->close();
		return $aff;
	}

	// Quita un cliente de un vendedor (lo deja sin vendedor). Solo si hoy pertenece a ese vendedor.
	public function quitarCliente($codigoCliente, $vendedor){
		global $conexion;
		$stmt = $conexion->prepare("UPDATE clientes SET vendedor = '' WHERE codigo = ? AND vendedor = ?");
		$stmt->bind_param('ss', $codigoCliente, $vendedor);
		$stmt->execute();
		$aff = $stmt->affected_rows;
		$stmt->close();
		return $aff;
	}
	//listar y mostrar en selct
	public function select(){
		$sql="SELECT * FROM vendedores WHERE codigo=1";
		return ejecutarConsulta($sql);
	}
}

 ?>
