<?php
require_once '../config/auth.php';
require_once "../modelos/Reclamo.php";

$reclamo=new Reclamo();

$idreclamo=isset($_POST["idreclamo"])? limpiarCadena($_POST["idreclamo"]):"";
$resolucion=isset($_POST["resolucion"])? limpiarCadena($_POST["resolucion"]):"";
$estado=isset($_POST["estado"])? limpiarCadena($_POST["estado"]):"";
$canal=isset($_POST["canal"])? limpiarCadena($_POST["canal"]):"";


$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp', 'listarMensajes']);

switch ($_GET["op"]) {
	case 'guardaryeditar':

        $rspta=$reclamo->editar($idreclamo,$resolucion,$estado,$canal);

        if ($rspta && $estado === 'En analisis') {
            $idReclamo = intval($idreclamo);
            $recRow = ejecutarConsultaSimpleFila("SELECT telefono FROM reclamos WHERE reclamoId = $idReclamo");
            if ($recRow && !empty($recRow['telefono'])) {
                $telefono = preg_replace('/\D/', '', $recRow['telefono']);
                $antJson = addslashes(json_encode(['type' => 'reclamo_pregunta', 'idReclamo' => $idReclamo], JSON_UNESCAPED_UNICODE));
                ejecutarConsulta("UPDATE contactos SET anterior='$antJson', esperaRespuesta=0 WHERE id='$telefono'");
            }
        }

		echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";

		break;
	case 'guardarMensaje':
		
		echo $rspta=$reclamo->insertarMensaje($idreclamo,$resolucion,"",$canal);
        echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
		break;
	

	case 'eliminar':
		$rspta=$reclamo->eliminar($idpersona);
		echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
		break;
	
	case 'mostrar':
		$rspta=$reclamo->mostrar($idreclamo);
		echo json_encode($rspta);
		break;

    case 'listarp':
		$rspta=$reclamo->listarp();
		
		$lista=$reclamo->listarRespuesta();
		$array = array();
		while ($reg=$lista->fetch_object()) {
			$array[]=$reg->id_reclamo;
        }
		
		$data=Array();
//SELECT `reclamoId`, `empresa`, `fecha_ingreso`, `clienteId`, `telefono`, `nick`, `motivo`, `area`, `detalle`, `fecha_resolucion`, `resolucion`, `estado`, `notificado`, `anulado` FROM `reclamos` WHERE 1
		while ($reg=$rspta->fetch_object()) {
		$disabled="";
		$clave = array_search($reg->reclamoId, $array);
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
				"0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar('.$reg->reclamoId.')" '.$disabled.' ><i class="mdi mdi-lead-pencil m-n2"></i></button>',
				"1"=>"<div class='tbdato' style='font-size: 13px;'>". $estado."</div>",
            	"2"=>$reg->reclamoId,
            	"3"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->_fecha."</div>",
            	"4"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->telefono."</div>",
				"5"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->clienteId."</div>",
				"6"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->razonSocial."</div>",
				"7"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->motivo."</div>",
				"8"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->_area."</div>",
//				"9"=>"<div style='font-size: 11px;' class='tbdato'>".substr($reg->detalle,0,40)."...</div>",
//				"10"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->resolucion."</div>",
				"9"=>"<div style='font-size: 11px;' class='tbdato'>".$reg->_fecha_resolucion."</div>",
				"10"=> $clave,
			 );
		//'<button class="btn btn-warning btn-xs" onclick="mostrar('.$reg->idpersona.')"><i class="fa fa-pencil"></i></button>'.' '.'<button class="btn btn-danger btn-xs" onclick="eliminar('.$reg->idpersona.')"><i class="fa fa-trash"></i></button>'	  
	//	Codigo	Fecha	Telefono Cliente	Nick	Motivo	Area	Detalle	Resolucion	Estado	Editar	  
		}
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		echo json_encode($results);
		break;

	case 'listarMensajes':
			
		$rspta=$reclamo->listarMensajes($_GET["idreclamo"]);
		//style="background-color:#A9D0F5"
		  echo ' <thead style="background-color:#8b74d2c7;color:white">
			<th >ID</th>
			<th width="10%">Tipo</th>
			<th width="15%">Fecha</th>
			<th width="55%" >Mensaje</th>
			<th width="10%">Respondido por</th>
			<th width="8%">Estado</th>
		   </thead>';
		while ($reg=$rspta->fetch_object()) {
            $canal = null;

            switch ($reg->canal) {
                case 0:
                    $canal = "Administración";
                    break;
                case 1:
                    $canal = "Supervisor";
                    break;
                case -1:
                    $canal = "Cliente";
                    break;
            }
		 $tipo='<span class="mdi mdi-rotate-315 mdi-send-check"></span> Enviado';
		  if($reg->tipo=="1")
		   $tipo='<span class="mdi mdi-reply"></span> Recibido';
			echo '<tr class="filas">
				<td>'.$reg->id.'</td>
				<td>'.$tipo.'</td>
				<td>'.$reg->fecha.'</td>
				<td>'.$reg->mensaje.'</td>
				<td>'.$canal.'</td>
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