<?php
//incluir la conexion de base de datos
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
class Reparto{

    private $responseWebMaster;

    public function __construct(){
        $this->responseWebMaster = getWebMasterConfig();
    }

    public function insertar($idcliente,$idusuario,$tipo_comprobante,$serie_comprobante,$num_comprobante,$fecha_hora,$impuesto,$total_venta,$idarticulo,$cantidad,$precio_venta,$descuento){
        $sql="INSERT INTO venta (idcliente,idusuario,tipo_comprobante,serie_comprobante,num_comprobante,fecha_hora,impuesto,total_venta,estado) VALUES ('$idcliente','$idusuario','$tipo_comprobante','$serie_comprobante','$num_comprobante','$fecha_hora','$impuesto','$total_venta','Aceptado')";
        $idventanew=ejecutarConsulta_retornarID($sql);
        $num_elementos=0;
        $sw=true;
        while ($num_elementos < count($idarticulo)) {

            $sql_detalle="INSERT INTO detalle_venta (idventa,idarticulo,cantidad,precio_venta,descuento) VALUES('$idventanew','$idarticulo[$num_elementos]','$cantidad[$num_elementos]','$precio_venta[$num_elementos]','$descuento[$num_elementos]')";

            ejecutarConsulta($sql_detalle) or $sw=false;

            $num_elementos=$num_elementos+1;
        }
        return $sw;
    }

    public function anular($idventa){
        $sql="UPDATE `pedidos` SET `flag`=3 WHERE `pedidoid` ='$idventa'";
        return ejecutarConsulta($sql);
    }

    public function editarEstado($idventa){
        $sql="UPDATE pedidos SET flag=2 WHERE pedidoid='$idventa'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idventa){
        $sql = null;
        if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
            $sql="SELECT p.pedidoid,p.fecha AS fecha,p.clienteId,c.razonSocial,c.direccion,flag as estado, SUM(CAST(p.subtotal AS DECIMAL(25,2))) AS total, p.telefono FROM pedidos p INNER JOIN clientes c ON c.id=p.clienteId WHERE p.pedidoid='$idventa' GROUP BY p.pedidoid";
        }else if ($this->responseWebMaster['data']['b2b'] ){
            $sql="SELECT p.pedidoid,p.fecha AS fecha,p.clienteId,c.razonSocial,c.direccion,flag as estado, SUM(CAST(p.subtotal AS DECIMAL(25,2))) AS total, p.telefono FROM pedidos p INNER JOIN clientes c ON c.codigo=p.clienteId WHERE p.pedidoid='$idventa' GROUP BY p.pedidoid";
        }
        return ejecutarConsultaSimpleFila($sql);
    }

    public function traerTelefono($idventa){
        $sql="SELECT telefonos.telefono FROM `pedidos`,telefonos WHERE pedidos.clienteId=telefonos.clienteId and pedidos.pedidoid= $idventa LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listarDetalle($idventa){
        $sql="SELECT p.pedidoid,p.producto,a.descripcion,p.cantidad,p.precio,p.descuento, p.subtotal FROM pedidos p INNER JOIN articulos a ON p.producto=a.codigo WHERE p.pedidoid='$idventa'";
        return ejecutarConsulta($sql) ;
    }

    public function listarMensajes($idventa){
        $sql="SELECT * FROM `fidelizar` WHERE `pedidoid` = $idventa order by id desc ";
        //return "SELECT * FROM `fidelizar` WHERE `pedidoid` = $idventa ";
        return ejecutarConsulta($sql) ;
    }

    public function listarMensajesNoLeidos($idventa){
        $sql="SELECT count(id) as noleidos FROM `fidelizar` WHERE `pedidoid` = $idventa AND `estado`=0 AND `tipo`=0 ";
        return ejecutarConsultaSimpleFila($sql) ;
    }

    public function marcarLeido($idventa){
        $sql="UPDATE fidelizar SET estado=1 WHERE pedidoid=$idventa and tipo =1";
        return "ok";// ejecutarConsulta($sql);
    }

    public function listarPedidos($filter){
        $sql = null;
        if(!empty($filter)){
            $where = "";
            switch ($filter){
                case 1:
                    $where = "GROUP BY p.pedidoid ORDER BY p.fecha DESC";
                    break;
                case 2:
                    $where = "and p.repartidor_id IS NOT NULL  and p.fecha_notificacion IS  NULL GROUP BY p.pedidoid ORDER BY  p.fecha_asignacion DESC";
                    break;
                case 3:
                    $where = "and p.repartidor_id IS NULL GROUP BY p.pedidoid ORDER BY p.fecha DESC";
                    break;
                case 4:
                    $where = "and p.repartidor_id IS NOT NULL and p.fecha_notificacion IS NOT NULL GROUP BY p.pedidoid ORDER BY p.fecha_notificacion DESC";
                    break;
                default:
                    $where = "GROUP BY p.pedidoid ORDER BY p.fecha DESC";
            }
            if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
                $sql="SELECT p.fecha_notificacion, p.repartidor_id as repartidor, vendedorId as vendedor, p.pedidoid, CONCAT(DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha ,p.clienteId,c.razonSocial,flag as estado, ROUND(sum(p.subtotal),2) AS total, p.telefono,pagado, p.fecha_asignacion FROM pedidos p INNER JOIN clientes c ON c.id=p.clienteId $where  ";
            }else if ($this->responseWebMaster['data']['b2b'] ){
                $sql="SELECT p.fecha_notificacion, p.repartidor_id as repartidor, vendedorId as vendedor, p.pedidoid, CONCAT(DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha ,p.clienteId,c.razonSocial,flag as estado, ROUND(sum(p.subtotal),2) AS total, p.telefono,pagado, p.fecha_asignacion FROM pedidos p INNER JOIN clientes c ON c.codigo=p.clienteId $where  ";
            }
        }else{

            if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
                $sql="SELECT p.repartidor_id as repartidor, vendedorId as vendedor, p.pedidoid, CONCAT(DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha,p.clienteId,c.razonSocial,flag as estado, ROUND(sum(p.subtotal),2) AS total, p.telefono,pagado FROM pedidos p INNER JOIN clientes c ON c.id=p.clienteId where p.repartidor_id IS NULL GROUP BY p.pedidoid ORDER BY p.fecha DESC ";
            }else if ($this->responseWebMaster['data']['b2b'] ){
                $sql="SELECT p.repartidor_id as repartidor, vendedorId as vendedor, p.pedidoid, CONCAT(DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha,p.clienteId,c.razonSocial,flag as estado, ROUND(sum(p.subtotal),2) AS total, p.telefono,pagado FROM pedidos p INNER JOIN clientes c ON c.codigo=p.clienteId where p.repartidor_id IS NULL GROUP BY p.pedidoid ORDER BY p.fecha DESC ";
            }

        }
        return ejecutarConsulta($sql);
    }

    public function listarObs(){
        $sql="SELECT pedidoid, MIN(`descripcion`) AS descripcion FROM `pedidos` WHERE `producto` like '.001' GROUP by `pedidoid`";
        return ejecutarConsulta($sql);
    }

    public function ventacabecera($idventa){
        $sql = null;
        if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
            $sql= "SELECT v.pedidoid, v.clienteId, p.razonSocial AS cliente,p.direccion, p.localidad, p.telefono, v.pedidoid, v.pedidoid, v.fecha AS fecha, v.subtotal, p.codigo FROM pedidos v INNER JOIN clientes p ON p.id=v.clienteId WHERE v.pedidoid='$idventa'";
        }else if ($this->responseWebMaster['data']['b2b'] ){
            $sql= "SELECT v.pedidoid, v.clienteId, p.razonSocial AS cliente,p.direccion, p.localidad, p.telefono, v.pedidoid, v.pedidoid, v.fecha AS fecha, v.subtotal, p.codigo FROM pedidos v INNER JOIN clientes p ON p.codigo=v.clienteId WHERE v.pedidoid='$idventa'";
        }
        return ejecutarConsulta($sql);
    }

    public function ventadetalles($idventa){
        $sql="SELECT a.descripcion AS articulo, a.codigo, d.cantidad, d.precio, d.descuento, ROUND((d.cantidad*d.precio-d.descuento),2) AS subtotal , dato9 FROM pedidos d INNER JOIN articulos a ON d.producto=a.codigo WHERE d.pedidoid='$idventa'";
        return ejecutarConsulta($sql);
    }

    public function insertarMensaje($clienteid,$pedidoid,$mensaje,$tipo){

        $sql="INSERT INTO `fidelizar`(`fecha`, `clienteid`, `pedidoid`, `tipo`, `mensaje`, `estado`) VALUES (NOW(),'$clienteid',$pedidoid,$tipo,'$mensaje',0)";
        ejecutarConsulta("UPDATE fidelizar SET estado=1 WHERE pedidoid=$pedidoid ");
        return ejecutarConsulta($sql) or $sw=false;
    }

    public function asignar($repartidor, $pedidosId){
        $sql="UPDATE pedidos SET repartidor_id='$repartidor', fecha_asignacion=CURRENT_TIMESTAMP() WHERE pedidoid in ($pedidosId)";
        return ejecutarConsulta($sql);
    }

    public function obtenerTelefono($pedidosId){
        $sql="SELECT repartidor.telefono as telefono FROM `pedidos` as pedido INNER JOIN `repartidores` as repartidor ON  pedido.repartidor_id = repartidor.id   where pedido.pedidoid in ($pedidosId) GROUP BY repartidor.telefono";

        return ejecutarConsulta($sql);
    }

    public function obtenerPedidos($pedidosId){
        $sql = "UPDATE pedidos SET fecha_notificacion=CURRENT_TIMESTAMP() where pedidoid in (".$pedidosId.")";
        ejecutarConsulta($sql);

        if($this->responseWebMaster['data']['mix'] || $this->responseWebMaster['data']['b2c'] ){
            $sql = "SELECT repartidor.telefono as telefonoR,cliente.telefono as telefonoC, pedido.*, cliente.* , SUM(CAST(pedido.subtotal AS DECIMAL(25,2))) AS total ,  SUM(pedido.cantidad) as cantidadTotal  FROM `pedidos` as pedido INNER JOIN `repartidores` as repartidor ON  pedido.repartidor_id = repartidor.id INNER JOIN clientes as cliente on pedido.clienteId = cliente.id  where pedido.pedidoid in  ($pedidosId) GROUP BY pedidoid, repartidor.telefono";
        }else if ($this->responseWebMaster['data']['b2b'] ){
            $sql = "SELECT repartidor.telefono as telefonoR,cliente.telefono as telefonoC, pedido.*, cliente.* , SUM(CAST(pedido.subtotal AS DECIMAL(25,2))) AS total ,  SUM(pedido.cantidad) as cantidadTotal  FROM `pedidos` as pedido INNER JOIN `repartidores` as repartidor ON  pedido.repartidor_id = repartidor.id INNER JOIN clientes as cliente on pedido.clienteId = cliente.codigo  where pedido.pedidoid in  ($pedidosId) GROUP BY pedidoid, repartidor.telefono";
        }
        return ejecutarConsulta($sql);
    }

    public function obtenerProductosPorPedido($id){
        $sql = "SELECT  pedido.*  FROM `pedidos` as pedido where pedido.pedidoid=$id";
        return ejecutarConsulta($sql);
    }

    public function desasignar($repartidor,$pedido){
        $sql="UPDATE pedidos SET repartidor_id=NULL, fecha_asignacion=NULL WHERE pedidoid in ($pedido) and repartidor_id='$repartidor' ";
        ejecutarConsulta($sql);
        $sql="SELECT repartidor.telefono as telefono FROM `repartidores` as repartidor   where repartidor.id ='$repartidor'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function getNombreRepartidor($repartidor){
        $sql="SELECT *  FROM `repartidores` WHERE id='$repartidor' ";
        $result = ejecutarConsulta($sql);
        $objRepartidor =$result->fetch_object();
        return $objRepartidor->nombre;
    }
}
?>
