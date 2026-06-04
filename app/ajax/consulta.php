<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require (__ROOT__.'/modelos/Consulta.php');

$consulta=new Consulta();

$idconsulta=isset($_POST["idconsulta"])? limpiarCadena($_POST["idconsulta"]):"";
$resolucion=isset($_POST["resolucion"])? limpiarCadena($_POST["resolucion"]):"";
$estado=isset($_POST["estado"])? limpiarCadena($_POST["estado"]):"";
$canal=isset($_POST["canal"])? limpiarCadena($_POST["canal"]):"";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp', 'listarMensajes']);

switch ($_GET["op"]) {
    case 'guardaryeditar':
        if (!empty($idconsulta)) {
            $rspta=$consulta->editar($idconsulta,$resolucion,$estado);

            if ($rspta && $estado === 'En analisis') {
                $idConsulta = intval($idconsulta);
                $recRow = ejecutarConsultaSimpleFila("SELECT telefono FROM consultas WHERE consultaId = $idConsulta");
                if ($recRow && !empty($recRow['telefono'])) {
                    $telefono = preg_replace('/\D/', '', $recRow['telefono']);
                    $antJson = addslashes(json_encode(['type' => 'consulta_pregunta', 'idConsulta' => $idConsulta], JSON_UNESCAPED_UNICODE));
                    ejecutarConsulta("UPDATE contactos SET anterior='$antJson', esperaRespuesta=0 WHERE id='$telefono'");
                }
            }

            echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
        }
        break;
    case 'guardarMensaje':

        echo $rspta=$consulta->insertarMensaje($idconsulta,$resolucion,"",$canal);
        break;


    case 'eliminar':
        $rspta=$consulta->eliminar($idpersona);
        echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
        break;

    case 'mostrar':
        $rspta=$consulta->mostrar($idconsulta);
        echo json_encode($rspta);
        break;

    case 'listarp':
        $rspta=$consulta->listarp();

        $lista=$consulta->listarRespuesta();
        $array = array();
        while ($reg=$lista->fetch_object()) {
            $array[]=$reg->id_consulta;
        }

        $data=Array();
        while ($reg=$rspta->fetch_object()) {
            $disabled="";
            $clave = array_search($reg->consultaId, $array);
            if(strlen ($clave)>0)$clave=$clave+1;
            $estado= '<span class="badge badge-danger-lighten rounded-pill">Pendiente</span>';
            if($reg->estado=='Finalizado'){
                $estado= '<span class="badge badge-success-lighten rounded-pill">Finalizado</span>';
                // $disabled="disabled";
            }
            if($reg->estado=='En analisis')
                $estado= '<span class="badge badge-warning-lighten rounded-pill">En analisis</span>';
            if(strlen ($reg->_area)==0)
                $reg->_area=$reg->area;
            $data[]=array(
                "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrarConsulta('.$reg->consultaId.')" '.$disabled.' ><i class="mdi mdi-lead-pencil m-n2"></i></button>',
                "1"=>"<div style='font-size: 13px;'class='tbdato'>". $estado."</div>",
                "2"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->consultaId."</div>",
                "3"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->_fecha."</div>",
                "4"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->telefono."</div>",
                "5"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->clienteId."</div>",
                "6"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->razonSocial."</div>",
                "7"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->motivo."</div>",
                "8"=>"<div style='font-size: 13px;'class='tbdato'>".$reg->_area."</div>",
//                "9"=>"<div class='tbdato'>".substr($reg->detalle,0,20)."...</div>",
//                "10"=>"<div class='tbdato'>".$reg->resolucion."</div>",
                "9"=>"<div style='font-size: 13px;' class='tbdato'>".$reg->_fecha_resolucion."</div>",
                "10"=> $clave,
            );

        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

    case 'listarMensajes':

        $rspta=$consulta->listarMensajes($_GET["idconsulta"]);



        echo ' <thead style="background-color:#8b74d2c7;color:white">
			<th >ID</th>
			<th >Tipo</th>
			<th>Fecha</th>
			<th width="65%" >Mensaje</th>
			<th >Estado</th>
		   </thead>';
        while ($reg=$rspta->fetch_object()) {
            $tipo='<span class="mdi mdi-rotate-315 mdi-send-check"></span> Enviado';
            if($reg->tipo=="1")
                $tipo='<span class="mdi mdi-reply"></span> Recibido';
            echo '<tr class="filas">
				<td>'.$reg->id.'</td>
				<td>'.$tipo.'</td>
				<td>'.$reg->fecha.'</td>
				<td>'.$reg->mensaje.'</td>
				<td>'.$reg->estado.'</td>
				</tr>';

        }
        if($rspta->num_rows==0){
			echo '<tr style="text-align:center">
				<td colspan="6">No se encontraron registros</td>		
				</tr>';
		}
        break;
}
?>