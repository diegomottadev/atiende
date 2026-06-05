<?php 
//incluir la conexion de base de datos
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
class Persona{

	private $responseWebMaster;

	public function __construct(){
		$this->responseWebMaster = getWebMasterConfig();
	}

	public function editar($idpersona,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="UPDATE clientes SET vendedor='$vendedor', razonSocial='$nombre',direccion='$direccion',localidad='$localidad',ramo='$ramo',zona='$zona',lista='$lista',telefono= '$telefono' 
			WHERE id='$idpersona'";
		}else if($this->responseWebMaster['data']['b2b'] ){
			$sql="UPDATE clientes SET vendedor='$vendedor', razonSocial='$nombre',direccion='$direccion',localidad='$localidad',ramo='$ramo',zona='$zona',lista='$lista',telefono= '$telefono' 
			WHERE codigo='$idpersona'";
		}
		return ejecutarConsulta($sql);
	}

	public function eliminarCliente($idpersona){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="DELETE FROM clientes WHERE id='$idpersona'";
		}else if($this->responseWebMaster['data']['b2b'] ){
			$sql="DELETE FROM clientes WHERE codigo='$idpersona'";
		}
		return ejecutarConsulta($sql);
	}

	public function mostrar($idpersona){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT * FROM clientes WHERE id='$idpersona'";
		}else if($this->responseWebMaster['data']['b2b'] ){
			$sql="SELECT * FROM clientes WHERE codigo='$idpersona'";
		}
		return ejecutarConsultaSimpleFila($sql);
	}

	public function listarp(){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT * FROM `clientes` order by id DESC";
		}else if($this->responseWebMaster['data']['b2b'] ){
			$sql="SELECT * FROM `clientes` order by codigo DESC";
		}
		return ejecutarConsulta($sql);
	}

	public function listarc(){
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			$sql="SELECT * FROM `clientes` order by id DESC";
		}else if($this->responseWebMaster['data']['b2b'] ){
			$sql="SELECT * FROM `clientes` order by codigo DESC";
		}
		return ejecutarConsulta($sql);
	}

	// ============================================================
	//  Server-side processing (DataTables) para clientes — escala a millones de filas
	// ============================================================

	// Whitelist: índice de columna DataTables → columna real de `clientes`.
	// Col 0 (botones de acción) no figura → no ordenable/buscable.
	public function columnasClienteServerSide(){
		return array(
			1=>'codigo', 2=>'vendedor', 3=>'razonSocial', 4=>'direccion', 5=>'localidad',
			6=>'telefono', 7=>'ramo', 8=>'zona', 9=>'lista', 10=>'latitud', 11=>'longitud', 12=>'deposito'
		);
	}

	// Devuelve solo la página pedida + totales. TODO input del usuario va por prepared statement.
	public function listarcServerSide($start, $length, $buscar, $order, $columns){
		global $conexion;
		require_once dirname(__DIR__).'/config/Datatable.php';
		$map = $this->columnasClienteServerSide();
		$req = array('start'=>$start, 'length'=>$length, 'search'=>array('value'=>$buscar), 'order'=>$order, 'columns'=>$columns);
		$searchCols = array('codigo','vendedor','razonSocial','direccion','localidad','telefono','ramo','zona','lista','deposito');
		return Datatable::serverSide($conexion, 'clientes', $map, $searchCols, array(
			'select'       => '`codigo`,`vendedor`,`razonSocial`,`direccion`,`localidad`,`telefono`,`ramo`,`zona`,`lista`,`latitud`,`longitud`,`deposito`',
			'fetch'        => 'assoc',
			'defaultOrder' => '`codigo` DESC',
			'request'      => $req
		));
	}

	// Valores distintos de una columna (para poblar los dropdowns de filtro)
	public function distinctCliente($colNombre){
		global $conexion;
		require_once dirname(__DIR__).'/config/Datatable.php';
		return Datatable::distinct($conexion, 'clientes', $colNombre, array_values($this->columnasClienteServerSide()));
	}

	public function listarClientes($json){
		$obj = json_decode($json, TRUE);
		$and="";
		$sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
			if(count($obj[4])>0) {
				$clientes=substr(json_encode($obj[4]), 1, -1) ;
				$and.= ' and id IN ('.$clientes.')' ;
			}
			$sql = 'SELECT DISTINCT CLIENTES.`codigo`,
									CLIENTES.`razonSocial`, 
									CLIENTES.`direccion`,
									CLIENTES.`zona`,
									CLIENTES.`vendedor`,
									CLIENTES.`telefono` ,
									CLIENTES.`lista` ,
									CLIENTES.`orden`,
									CLIENTES.`ramo`,
									CLIENTES.`localidad` ,
									CLIENTES.`deposito`,
									CLIENTES.`latitud` ,
									CLIENTES.`longitud` 
									FROM `clientes` as CLIENTES 
									where CLIENTES.`id`  IS NOT NULL'.$and;
		}else if ($this->responseWebMaster['data']['b2b'] ){
			if(count($obj[4])>0) {
				$clientes=substr(json_encode($obj[4]), 1, -1) ;
				$and.= ' and codigo IN ('.$clientes.')' ;
			}
			$sql = 'SELECT DISTINCT CLIENTES.`codigo`,
									CLIENTES.`razonSocial`, 
									CLIENTES.`direccion`,
									CLIENTES.`zona`,
									CLIENTES.`vendedor`,
									CLIENTES.`telefono` ,
									CLIENTES.`lista` ,
									CLIENTES.`orden`,
									CLIENTES.`ramo`,
									CLIENTES.`localidad` ,
									CLIENTES.`deposito`,
									CLIENTES.`latitud` ,
									CLIENTES.`longitud` 
									FROM `clientes` as CLIENTES 
									where CLIENTES.`codigo`  IS NOT NULL'.$and;
		}
		return ejecutarConsulta($sql);
	}




}

 ?>
