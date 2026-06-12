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
csrfGuard($op, ['mostrar', 'listarp', 'listarMensajes', 'filtros']);

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
        // Server-side processing (DataTables): solo la página pedida + totales → escala a millones de filas.
        $draw    = isset($_REQUEST['draw'])   ? intval($_REQUEST['draw'])   : 1;
        $start   = isset($_REQUEST['start'])  ? intval($_REQUEST['start'])  : 0;
        $length  = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
        $buscar  = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : '';
        $orden   = (isset($_REQUEST['order'])   && is_array($_REQUEST['order']))   ? $_REQUEST['order']   : array();
        $columns = (isset($_REQUEST['columns']) && is_array($_REQUEST['columns'])) ? $_REQUEST['columns'] : array();

        // Lookup secundario (chico): ids cuya ÚLTIMA respuesta sigue sin responder → para la columna "clave".
        // Se sigue usando tal cual y se hace el array_search por fila de la PÁGINA (preserva el comportamiento).
        $lista=$consulta->listarRespuesta();
        $array = array();
        while ($reg=$lista->fetch_object()) {
            $array[]=$reg->id_consulta;
        }

        $res = $consulta->listarpServerSide($start, $length, $buscar, $orden, $columns);

        // Mapa de áreas (tabla chica) + razonSocial de los clientes de ESTA página (reconstruye los JOIN).
        $areas = $consulta->mapaAreas();
        $clienteIds = array();
        foreach ($res['rows'] as $reg) { if ($reg['clienteId'] !== null && $reg['clienteId'] !== '') $clienteIds[] = $reg['clienteId']; }
        $razones = $consulta->razonSocialPorClientes($clienteIds);

        $data = array();
        foreach ($res['rows'] as $reg) {
            $disabled="";
            $clave = array_search($reg['consultaId'], $array);
            if(strlen ($clave)>0)$clave=$clave+1;
            $estado= '<span class="badge badge-danger-lighten rounded-pill">Pendiente</span>';
            if($reg['estado']=='Finalizado'){
                $estado= '<span class="badge badge-success-lighten rounded-pill">Finalizado</span>';
                // $disabled="disabled";
            }
            if($reg['estado']=='En analisis')
                $estado= '<span class="badge badge-warning-lighten rounded-pill">En analisis</span>';
            // _area: nombre del área vía mapa; fallback al valor crudo de `area` (igual que el listado original).
            $_area = isset($areas[$reg['area']]) ? $areas[$reg['area']] : '';
            if(strlen ($_area)==0)
                $_area=$reg['area'];
            $razonSocial = isset($razones[$reg['clienteId']]) ? $razones[$reg['clienteId']] : '';
            $data[]=array(
                "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrarConsulta('.$reg['consultaId'].')" '.$disabled.' ><i class="mdi mdi-lead-pencil m-n2"></i></button>',
                "1"=>"<div class='tbdato'>". $estado."</div>",
                "2"=>"<div class='tbdato'>".$reg['consultaId']."</div>",
                "3"=>"<div class='tbdato'>".$reg['_fecha']."</div>",
                "4"=>"<div class='tbdato'>".$reg['telefono']."</div>",
                "5"=>"<div class='tbdato'>".$reg['clienteId']."</div>",
                "6"=>"<div class='tbdato'>".$razonSocial."</div>",
                "7"=>"<div class='tbdato'>".$reg['motivo']."</div>",
                "8"=>"<div class='tbdato'>".$_area."</div>",
//                "9"=>"<div class='tbdato'>".substr($reg['detalle'],0,20)."...</div>",
//                "10"=>"<div class='tbdato'>".$reg['resolucion']."</div>",
                "9"=>"<div class='tbdato'>".$reg['_fecha_resolucion']."</div>",
                "10"=> $clave,
            );

        }
        echo json_encode(array(
            "draw"            => $draw,
            "recordsTotal"    => $res['recordsTotal'],
            "recordsFiltered" => $res['recordsFiltered'],
            "data"            => $data
        ), JSON_UNESCAPED_UNICODE);
        break;

    case 'filtros':
        // Valores distintos para los dropdowns de filtro: Estado (string real) y Área (id+nombre).
        echo json_encode(array(
            "estado" => $consulta->distinctConsulta('estado'),
            "area"   => $consulta->areasParaFiltro()
        ), JSON_UNESCAPED_UNICODE);
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
            $mensajeEsc = htmlspecialchars($reg->mensaje, ENT_QUOTES, 'UTF-8');
            echo '<tr class="filas">
				<td>'.$reg->id.'</td>
				<td>'.$tipo.'</td>
				<td>'.$reg->fecha.'</td>
				<td>'.$mensajeEsc.'</td>
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