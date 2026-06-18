<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');

class Consulta{

    private $responseWebMaster;

    public function __construct(){
        $this->responseWebMaster = getWebMasterConfig();
    }

    public function editar($idConsulta,$resolucion,$estado){
        $idConsulta = (int)$idConsulta;
        $sql="UPDATE consultas SET resolucion='$resolucion', estado='$estado' , fecha_resolucion =NOW() WHERE consultaId='$idConsulta'";
        ejecutarConsulta("INSERT INTO `msj_consultas`(`id_consulta`, tipo,`fecha`, `mensaje`,respondido, `estado`) VALUES ($idConsulta,0,NOW(),'$resolucion',1,'$estado')");
        ejecutarConsulta("UPDATE `msj_consultas` SET `respondido`=1  WHERE `id_consulta` = ".$idConsulta);
        return ejecutarConsulta($sql);
    }
    public function insertarMensaje($idConsulta,$resolucion,$estado,$canal){
        $idConsulta = (int)$idConsulta;
        $sql = "INSERT INTO `msj_consultas`(`id_consulta`, tipo,`fecha`, `mensaje`, respondido,`estado`,canal) VALUES ($idConsulta,1,NOW(),'$resolucion',0,'$estado',$canal)";
        return ejecutarConsulta($sql);

    }
    public function listarChats($id_consulta){
		$id_consulta = (int)$id_consulta;
		$sql = "SELECT fecha, DATE_FORMAT(fecha, '%e de %M %Y ') as fechaMensaje, 
                    DATE_FORMAT(fecha, ' %H:%i') as hora, mensaje, id_consulta, canal, 
                    CASE WHEN canal= '-1' THEN 'Cliente' ELSE 'Administrativo' END AS usuario 
                FROM `msj_consultas` 
                WHERE `id_consulta` = $id_consulta ";
		return ejecutarConsulta($sql);
	}
    public function mostrar($idConsulta){
        $idConsulta = (int)$idConsulta;
        $sql="SELECT consultas.*, consultas.area as _area FROM `consultas` WHERE consultaId='$idConsulta' ";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listarp(){
        $sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
            $sql="SELECT consultas.*, clientes.razonSocial,  CONCAT(DATE_FORMAT(consultas.fecha_ingreso, '%d/%m/%Y %H:%i'),' hs') AS _fecha,CONCAT(DATE_FORMAT(consultas.fecha_resolucion, '%d/%m/%Y %H:%i'),' hs') AS _fecha_resolucion, areas.area as _area FROM `consultas`  LEFT JOIN  areas ON  consultas.area = areas.id LEFT JOIN clientes ON clientes.id = consultas.clienteId order by consultas.fecha_ingreso desc";
        }else if($this->responseWebMaster['data']['b2b']){
            $sql="SELECT consultas.*, clientes.razonSocial,  CONCAT(DATE_FORMAT(consultas.fecha_ingreso, '%d/%m/%Y %H:%i'),' hs') AS _fecha,CONCAT(DATE_FORMAT(consultas.fecha_resolucion, '%d/%m/%Y %H:%i'),' hs') AS _fecha_resolucion, areas.area as _area FROM `consultas`  LEFT JOIN  areas ON  consultas.area = areas.id LEFT JOIN clientes ON clientes.codigo = consultas.clienteId order by consultas.fecha_ingreso desc";
        }
        return ejecutarConsulta($sql);
    }
    public function listarRespuesta(){
        $sql="SELECT `id_consulta` FROM msj_consultas WHERE id IN (SELECT MAX(id) FROM msj_consultas GROUP BY `id_consulta`) and `respondido` = 0 ORDER BY id  ASC";
        return ejecutarConsulta($sql);
    }

    public function listarMensajes($idventa){
        $idventa = (int)$idventa;
        $sql="SELECT * FROM `msj_consultas` WHERE `id_consulta` = $idventa ";
        return ejecutarConsulta($sql) ;
    }

    public function listarMensaje($idventa){
        $idventa = (int)$idventa;
        $sql="SELECT * FROM `msj_consultas` WHERE `id_consulta` = $idventa ";
        return ejecutarConsulta($sql) ;
    }

    // ============================================================
    //  Server-side processing (DataTables) para CONSULTAS — escala a millones de filas
    // ============================================================

    // Whitelist: índice de columna DataTables → columna REAL de `consultas`.
    // Col 0 (botón editar), 6 (razonSocial: viene de JOIN clientes) y 10 (clave/respondido: lookup)
    // NO son columnas reales de consultas → no figuran.
    // El badge "Estado" (col 1) mapea a la columna real `estado`; las fechas a sus columnas crudas.
    // Col 8 (_area) muestra el NOMBRE del área (JOIN), pero filtra/ordena por la columna real `area` (id):
    // el dropdown de filtro envía el id del área como valor exacto.
    public function columnasConsultaServerSide(){
        return array(
            1=>'estado',        // badge Estado → columna estado
            2=>'consultaId',    // N° Con
            3=>'fecha_ingreso', // Fecha (se muestra formateada)
            4=>'telefono',      // Telefono
            5=>'clienteId',     // Cod.Cliente
            7=>'motivo',        // Motivo
            8=>'area',          // Area → filtra por id (real); muestra nombre vía mapaAreas()
            9=>'fecha_resolucion' // Fecha Res. (se muestra formateada)
        );
    }

    // Devuelve solo la página pedida + totales (delega en el helper genérico Datatable).
    // TODO input del usuario va por prepared statement. El select preserva el formato de fecha
    // EXACTO del listado original (CONCAT + DATE_FORMAT '%d/%m/%Y %H:%i' + ' hs').
    public function listarpServerSide($start, $length, $buscar, $order, $columns){
        global $conexion;
        require_once dirname(__DIR__).'/config/Datatable.php';
        $map = $this->columnasConsultaServerSide();
        $req = array('start'=>$start, 'length'=>$length, 'search'=>array('value'=>$buscar), 'order'=>$order, 'columns'=>$columns);
        // Columnas reales por las que aplica la búsqueda global (substring LIKE).
        $searchCols = array('consultaId','telefono','clienteId','motivo','estado');
        $select = "consultaId, empresa, fecha_ingreso, clienteId, telefono, nick, motivo, area, detalle, "
                . "fecha_resolucion, resolucion, estado, "
                . "CONCAT(DATE_FORMAT(fecha_ingreso, '%d/%m/%Y %H:%i'),' hs') AS _fecha, "
                . "CONCAT(DATE_FORMAT(fecha_resolucion, '%d/%m/%Y %H:%i'),' hs') AS _fecha_resolucion";
        return Datatable::serverSide($conexion, 'consultas', $map, $searchCols, array(
            'select'       => $select,
            'fetch'        => 'assoc',
            'defaultOrder' => '`consultaId` DESC',
            'request'      => $req
        ));
    }

    // Valores distintos de una columna (para poblar los dropdowns de filtro: estado / area)
    public function distinctConsulta($colNombre){
        global $conexion;
        require_once dirname(__DIR__).'/config/Datatable.php';
        // Whitelist: columnas reales de `consultas` permitidas para distinct (incluye 'area', que no
        // está en el mapa de orden/filtro porque en la grilla se muestra el NOMBRE del área vía JOIN).
        $permitidas = array_merge(array_values($this->columnasConsultaServerSide()), array('area'));
        return Datatable::distinct($conexion, 'consultas', $colNombre, $permitidas);
    }

    // Mapa id→nombre de áreas (tabla chica) para reconstruir la columna _area por fila de la página.
    public function mapaAreas(){
        $out = array();
        $r = ejecutarConsulta("SELECT id, area FROM `areas`");
        if ($r) { while ($reg = $r->fetch_object()) { $out[$reg->id] = $reg->area; } }
        return $out;
    }

    // Lista de áreas (id + nombre) para poblar el dropdown de filtro de la grilla.
    // El <option value> es el id (lo que filtra el helper sobre la columna real `area`).
    public function areasParaFiltro(){
        $out = array();
        $r = ejecutarConsulta("SELECT id, area FROM `areas` WHERE area IS NOT NULL AND area <> '' ORDER BY area ASC");
        if ($r) { while ($reg = $r->fetch_object()) { $out[] = array('id'=>$reg->id, 'nombre'=>$reg->area); } }
        return $out;
    }

    // razonSocial por clienteId, para las filas de la página (respeta b2b/b2c/mix como el listado original).
    public function razonSocialPorClientes($ids){
        $out = array();
        $ids = array_filter(array_map('intval', $ids), function($v){ return $v !== 0; });
        if (!$ids) return $out;
        $in = implode(',', array_unique($ids));
        if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
            $sql = "SELECT id AS k, razonSocial FROM `clientes` WHERE id IN ($in)";
        } else {
            $sql = "SELECT codigo AS k, razonSocial FROM `clientes` WHERE codigo IN ($in)";
        }
        $r = ejecutarConsulta($sql);
        if ($r) { while ($reg = $r->fetch_object()) { $out[$reg->k] = $reg->razonSocial; } }
        return $out;
    }

    public function listarRespCons($idConsulta){
        $sql = null;
		if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
            $sql = "SELECT consultas.*,clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `consultas` LEFT JOIN clientes ON consultas.clienteId = clientes.id WHERE consultaId like '$idConsulta' and estado <> 'Finalizado'";            
        }else if($this->responseWebMaster['data']['b2b']){
            $sql = "SELECT consultas.*,clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `consultas` LEFT JOIN clientes ON consultas.clienteId = clientes.codigo WHERE consultaId like '$idConsulta' and estado <> 'Finalizado'";            
        }
        return ejecutarConsulta($sql) ;
    }

}

?>
