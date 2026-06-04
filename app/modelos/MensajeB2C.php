<?php
//incluir la conexion de base de datos
define('__ROOT__', dirname(dirname(__FILE__)));
require (__ROOT__.'/config/Conexion.php');
require (__ROOT__.'/config/Connection.php');
class MensajeB2C{

    public function listarContactos($id){
        $rows['contactos'] = array();
        $request = Connection::runQuery("SELECT * FROM `mensajeb2c_contactob2c` where mensajeb2c_id=".$id);

        while ($row = mysqli_fetch_assoc($request)) {
            $rows['contactos'][] = $row['contactob2c_id'];
        }
        $clientes=str_replace('"','',substr(json_encode($rows['contactos']), 1, -1)) ;

        $sql = 'SELECT      telefono,
                            codigo,
							razonSocial,
							direccion,
							zona,
							ramo,
							localidad,
							cuit
							FROM contactosb2c
							where id  IN ('.$clientes.')';
        return ejecutarConsulta($sql);
    }

    public function listarMensajes(){
        $sql = "SELECT *,  DATE_FORMAT(fecha, '%d/%m/%Y') as fechaFormato FROM `mensajesb2c`";
        return ejecutarConsulta($sql);
    }

    public function show($id){
        $sql = 'SELECT * FROM `mensajesb2c` where id='.$id;
        return ejecutarConsultaSimpleFila($sql);
    }

    public function getContactsToSendMsg($id){
        $data =[];
        $contactos= $this->listarContactos($id);
        while ($reg=$contactos->fetch_object()) {
            $data[]=$reg->telefono;
        }
        $mensaje = $this->show($id);
        return [
                   "contacts" => $data,
                    "menssages" => $mensaje
        ];
    }

    public function eliminar($id){
        $sql = 'DELETE mensaje, contacto from contactosb2c contacto INNER JOIN  mensajeb2c_contactob2c mensaje ON  contacto.id = mensaje.contactob2c_id WHERE mensaje.mensajeb2c_id ='.$id ;
        ejecutarConsulta($sql);
        $sql = 'DELETE  FROM `mensajesb2c` where id='.$id ;
        return ejecutarConsulta($sql);
    }
}
?>
