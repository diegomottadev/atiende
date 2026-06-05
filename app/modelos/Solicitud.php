<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');

class Solicitud{

    public function __construct(){

    }

    public function insertar($nombre, $direccion,$localidad,$telefono){
        $sql="INSERT INTO `solicitudes`(
                            `nombre`
                            ,`direccion`
                            ,`localidad`
                            ,`telefono`
                            ,`fecha`
                            ) 
              VALUES ('$nombre'
                     ,'$direccion'
                     ,'$localidad'
                     ,'$telefono'
                     ,NOW())";
        return ejecutarConsulta($sql);
    }

    public function guardarNuevoCliente($id,$codigo,$nombre, $direccion,$localidad,$telefono,$zona,$ramo,$latitud,$longitud,$lista,$cuit,$deposito,$vendedor){

        $sql="SELECT codigo,
                    telefono,
                    razonSocial AS nombre 
            FROM clientes 
            WHERE codigo = '$codigo'";

        $existeCliente =  ejecutarConsultaSimpleFila($sql);

        if($existeCliente == null){
            $sql="INSERT INTO clientes (
                        codigo
                        ,razonSocial
                        ,direccion
                        ,zona
                        ,vendedor
                        ,localidad
                        ,telefono
                        ,lista
                        ,ramo
                        ,latitud
                        ,longitud
                        ,deposito) 
                VALUES ('$codigo',
                        '$nombre',
                        '$direccion',
                        '$zona',
                        '$vendedor',
                        '$localidad',
                        '$telefono',
                        '$lista',
                        '$ramo',
                        '$latitud',
                        '$longitud',
                        '$deposito')";
            ejecutarConsulta($sql);
            $sql="UPDATE solicitudes SET estado=1 WHERE id='$id'";
            ejecutarConsulta($sql);
            $sql="SELECT codigo,telefono,razonSocial as nombre FROM clientes WHERE codigo = '$codigo'";
            $data = ejecutarConsultaSimpleFila($sql);
            return  ["msg"=>"Datos actualizados correctamente","data"=>$data,"status" => 200, "sql"=>$sql];
        }

        return ["error"=>"Código del cliente existe, ingrese otro nuevamente","status" => 404];
    }

    public function mostrar($id){
        $sql="SELECT * FROM solicitudes WHERE id='$id' and estado=0";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar($filter){

        $sql = null;
        if(!empty($filter)){
            $where = "";
            switch ($filter){
                case 1:
                    $where = " where estado=0";
                    break;
                case 2:
                    $where = " where estado=1";
                    break;
                case 3:
                    $where = "";
                    break;
                default:
                    $where = "";
            }
            $sql="SELECT id,
                    CONCAT(DATE_FORMAT(fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha, 
                    nombre,
                    direccion,
                    localidad,
                    estado,
                    cuit,
                    latitud,
                    longitud,
                    telefono 
            FROM solicitudes $where
            ORDER BY id DESC";
            //die($sql);
        }else{

            $sql="SELECT id,
                    CONCAT(DATE_FORMAT(fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha, 
                    nombre,
                    direccion,
                    localidad,
                    estado,
                    cuit,
                    latitud,
                    longitud,
                    telefono 
            FROM solicitudes 
            ORDER BY id DESC";

        }
        return ejecutarConsulta($sql);





        return ejecutarConsulta($sql);
    }

    // ============================================================
    //  Server-side processing (DataTables) — escala a millones de filas
    // ============================================================

    // Whitelist: índice de columna DataTables → columna real de la tabla.
    // Solo estas columnas pueden ordenarse/filtrarse (evita SQL injection por nombre de columna).
    // La col 0 (botones de acción) NO es una columna real → no figura.
    // thead: 0=Acciones 1=Nombre 2=CUIT/DNI 3=Dirección 4=Localidad 5=Teléfono 6=Fecha 7=Estado
    public function columnasSolicitudServerSide(){
        return array(
            1=>'nombre',
            2=>'cuit',
            3=>'direccion',
            4=>'localidad',
            5=>'telefono',
            6=>'fecha',
            7=>'estado'
        );
    }

    // Devuelve solo la página pedida + totales (delega en el helper genérico Datatable).
    public function listarServerSide($start, $length, $buscar, $order, $columns){
        global $conexion;
        require_once dirname(__DIR__).'/config/Datatable.php';
        $map = $this->columnasSolicitudServerSide();
        // Búsqueda global solo sobre columnas de texto (no estado, que es 0/1 ni fecha)
        $searchCols = array('nombre','cuit','direccion','localidad','telefono');
        $req = array(
            'start'  => $start,
            'length' => $length,
            'search' => array('value'=>$buscar),
            'order'  => $order,
            'columns'=> $columns
        );
        return Datatable::serverSide($conexion, 'solicitudes', $map, $searchCols, array(
            // fecha formateada para mostrar; se ordena/filtra por la columna real `fecha`
            'select'       => "id, nombre, cuit, direccion, localidad, telefono, estado, latitud, longitud, CONCAT(DATE_FORMAT(fecha, '%d/%m/%Y %H:%i'),' hs') AS fecha",
            'fetch'        => 'assoc',
            'defaultOrder' => '`id` DESC',
            'request'      => $req
        ));
    }

    // Valores distintos de una columna (para poblar los dropdowns de filtro)
    public function distinctSolicitud($colNombre){
        global $conexion;
        require_once dirname(__DIR__).'/config/Datatable.php';
        return Datatable::distinct($conexion, 'solicitudes', $colNombre, array_values($this->columnasSolicitudServerSide()));
    }

    public function eliminar($id){
        $sql="DELETE FROM solicitudes WHERE id='$id'";
        return ejecutarConsulta($sql);
    }

    public function nextCodigo(){
        $sql="SELECT LPAD(COALESCE(MAX(CAST(codigo AS UNSIGNED)), 0) + 1, 4, '0') AS next_codigo
              FROM clientes
              WHERE codigo REGEXP '^[0-9]+$'";
        $row = ejecutarConsultaSimpleFila($sql);
        return $row ? $row['next_codigo'] : '0001';
    }
}

?>
