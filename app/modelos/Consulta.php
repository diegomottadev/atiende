<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');

class Consulta{

    private $responseWebMaster;

    public function __construct(){
        $this->responseWebMaster = getWebMasterConfig();
    }

    public function editar($idConsulta,$resolucion,$estado){
        $sql="UPDATE consultas SET resolucion='$resolucion', estado='$estado' , fecha_resolucion =NOW() WHERE consultaId='$idConsulta'";
        ejecutarConsulta("INSERT INTO `msj_consultas`(`id_consulta`, tipo,`fecha`, `mensaje`,respondido, `estado`) VALUES ($idConsulta,0,NOW(),'$resolucion',1,'$estado')");
        ejecutarConsulta("UPDATE `msj_consultas` SET `respondido`=1  WHERE `id_consulta` = ".$idConsulta);
        return ejecutarConsulta($sql);
    }
    public function insertarMensaje($idConsulta,$resolucion,$estado,$canal){
        $sql = "INSERT INTO `msj_consultas`(`id_consulta`, tipo,`fecha`, `mensaje`, respondido,`estado`,canal) VALUES ($idConsulta,1,NOW(),'$resolucion',0,'$estado',$canal)";
        return ejecutarConsulta($sql);

    }
    public function listarChats($id_consulta){
		$sql = "SELECT fecha, DATE_FORMAT(fecha, '%e de %M %Y ') as fechaMensaje, 
                    DATE_FORMAT(fecha, ' %H:%i') as hora, mensaje, id_consulta, canal, 
                    CASE WHEN canal= '-1' THEN 'Cliente' ELSE 'Administrativo' END AS usuario 
                FROM `msj_consultas` 
                WHERE `id_consulta` = $id_consulta ";
		return ejecutarConsulta($sql);
	}
    public function mostrar($idConsulta){
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
        $sql="SELECT * FROM `msj_consultas` WHERE `id_consulta` = $idventa ";
        return ejecutarConsulta($sql) ;
    }

    public function listarMensaje($idventa){
        $sql="SELECT * FROM `msj_consultas` WHERE `id_consulta` = $idventa ";
        return ejecutarConsulta($sql) ;
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
