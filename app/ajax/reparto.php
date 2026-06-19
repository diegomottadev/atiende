<?php
require_once '../config/auth.php';
require_once "../modelos/Reparto.php";

$reparto = new Reparto();

$idventa=isset($_POST["idventa"])? limpiarCadena($_POST["idventa"]):"";
$mensaje=isset($_POST["mensaje"])? limpiarCadena($_POST["mensaje"]):"";
$idcliente=isset($_POST["clienteid"])? limpiarCadena($_POST["clienteid"]):"";
$tipo=isset($_POST["tipo"])? limpiarCadena($_POST["tipo"]):"";
$repartidor=isset($_POST["repartidor"])? limpiarCadena($_POST["repartidor"]):"";
$pedidosId=isset($_POST["pedidosId"])? limpiarCadena($_POST["pedidosId"]):"";
$filter=isset($_GET["filter"])? limpiarCadena($_GET["filter"]):"";
$pedidosIdToSendMsj=isset($_POST["pedidosIdToSendMsj"])? limpiarCadena($_POST["pedidosIdToSendMsj"]):"";

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'traerTelefono', 'listarDetalle', 'listar', 'listarArticulos', 'listarMensajes', 'selectCliente', 'repartidores', 'obtenerPedidos']);

switch ($_GET["op"]) {
    case 'guardaryeditar':
        if (empty($idventa)) {
            $rspta=$reparto->insertar($idcliente,$idusuario,$tipo_comprobante,$serie_comprobante,$num_comprobante,$fecha_hora,$impuesto,$total_venta,$_POST["idarticulo"],$_POST["cantidad"],$_POST["precio_venta"],$_POST["descuento"]);
            echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
        }else{

        }
        break;
    case 'anular':
        $rspta=$reparto->anular($idventa);
        echo $rspta ? "Ingreso anulado correctamente" : "No se pudo anular el ingreso";
        break;

    case 'asignar':

        if (!empty($repartidor) && !empty($pedidosId)  ) {
            $rspta = $reparto->asignar($repartidor,$pedidosId);
            echo $rspta ? "Asignación del repartidor ha sido registrados correctamente" : "No se pudo asignar el repartidor a los pedidos seleccionados";
        }else{
            if (empty($repartidor)) {
                echo "No se pudo asignar el repartidor a los pedidos seleccionados por que no ha seleccionado un repartidor";
            }elseif(empty($pedidosId)  ){
                echo "No se pudo asignar el repartidor a los pedidos seleccionados por que no ha seleccionado pedidos";
            }
        }
        break;

    case 'desasignar':

        if (!empty($repartidor) && !empty($pedidosId)  ) {
            $rspta = $reparto->desasignar($repartidor,$pedidosId);
            echo $rspta ?  json_encode($rspta) : "No se pudo desasignar el repartidor a los pedido seleccionado";
        }else{
            if (empty($repartidor)) {
                echo "No se pudo desasignar el repartidor a los pedidos seleccionados por que no ha seleccionado un reparto";
            }
        }
        break;

    case 'editarEstado':
        $pedidoid = (int)($_GET["pedidoid"] ?? 0);
        $rspta=$reparto->editarEstado($pedidoid);
        if ($rspta) {
            $datos = $reparto->mostrar($pedidoid);
            echo json_encode([
                "ok"       => true,
                "pedidoid" => $datos["pedidoid"]   ?? $pedidoid,
                "fecha"    => $datos["fecha"]      ?? "",
                "cliente"  => $datos["razonSocial"] ?? "",
                "total"    => $datos["total"]      ?? ""
            ]);
        } else {
            echo json_encode(["ok" => false]);
        }
        break;

    case 'mostrar':
        $rspta=$reparto->mostrar($idventa);
        echo json_encode($rspta);
        break;

    case 'traerTelefono':
        $rspta=$reparto->traerTelefono($idventa);
        echo json_encode($rspta);
        break;

    case 'guardarMensaje':
        $idventa=isset($_GET["idventa"])? limpiarCadena($_GET["idventa"]):"";
        $mensaje=isset($_GET["mensaje"])? limpiarCadena($_GET["mensaje"]):"";
        $idcliente=isset($_GET["clienteid"])? limpiarCadena($_GET["clienteid"]):"";
        $tipo=isset($_GET["tipo"])? limpiarCadena($_GET["tipo"]):"";
        $rspta=$reparto->insertarMensaje($idcliente,$idventa,$mensaje,$tipo);
        echo json_encode($rspta);
        break;

    case 'listarDetalle':
        //recibimos el idventa
        $id=(int)($_GET['id'] ?? 0);

        $rspta=$reparto->listarDetalle($id);
        $total=0;
        echo ' <thead style="background-color:#8b74d2c7;color:white">
        <th>Codigo</th>
        <th>Articulo</th>
        <th align="center">Cantidad</th>
        <th align="right">Precio Venta</th>
        <th align="center">Descuento</th>
        <th align="right">Subtotal</th>
       </thead>';

        while ($reg=$rspta->fetch_object()) {
            echo '<tr class="filas">
			<td>'.$reg->producto.'</td>
			<td>'.$reg->descripcion.'</td>
			<td>'.$reg->cantidad.'</td>
			<td>$'.$reg->precio.'</td>
			<td>'.$reg->descuento.'</td>
			<td>$'.$reg->subtotal.'</td></tr>';
            $total=$total+($reg->precio*$reg->cantidad-$reg->descuento);
        }
        echo '<tfoot>
         
         <th colspan="5" align="right">TOTAL</th>
         <th><h4 id="total">$. '.$total.'</h4><input type="hidden" name="total_venta" id="total_venta"></th>
       </tfoot>';
        break;

    case 'listar':

        $rspta=$reparto->listarObs();
        $obs= array();
        $array= array();
        while ($reg=$rspta->fetch_object()) {
            $array[]=$reg->pedidoid;
            $obs[]=$reg->descripcion;
        }

        $rspta=$reparto->listarPedidos($filter);
        $data=Array();

        while ($reg=$rspta->fetch_object()) {
            $modal="";
            $key = array_search($reg->pedidoid, $array);
            if($key)
                $modal='<a href="#" class="text-info" data-bs-toggle="tooltip" title="Ver observación" onclick="comentario(\''.htmlspecialchars($obs[$key], ENT_QUOTES, 'UTF-8').'\')" ><i class="mdi mdi-comment-processing" style="font-size:1rem;vertical-align:middle;"></i></a>';

            $url='/ticket/';
            if($reg->estado==-1)
                $estado="<span class='badge bg-primary'>Pendiente</span>";
            if($reg->estado==0 || $reg->estado==1)
                $estado="<span class='badge bg-primary'>Pendiente</span>";
            if($reg->estado==2)
                $estado="<span class='badge bg-success'>Entregado</span>";
            if($reg->estado==3)
                $estado="<span class='badge bg-danger'>Anulado</span>";
            if ($reg->pagado === '0')
                $formaPago = '<span class="badge bg-secondary text-light">Pendiente</span>';
            if ($reg->pagado === '1')
                $formaPago = '<span class="badge bg-primary">M. Pago/span>';
            if ($reg->pagado === '2')
                $formaPago = '<span class="badge bg-primary">Efectivo</span>';
            if ($reg->pagado === '3')
                $formaPago = '<span class="badge bg-primary">T. Bancaria</span>';
            if ($reg->pagado === '4')
                $formaPago = '<span class="badge bg-primary">Cta cte</span>';

            if ($reg->pagado === '1'){
                $pagoElectronico = '<span class="badge bg-primary">Pagado</span>';
            }else{
                $pagoElectronico = '<span class="badge bg-secondary text-light">Pendiente</span>';
            }
            $fecha = new DateTime();
            $currentDay = $fecha->format('Y-m-d' ) === $reg->fecha ? '&nbsp;<span class="uil uil-asterisk"><span>' : "";
            $repartidorAsignador = $reg->repartidor !== null? '<span class="badge badge-success-lighten uil uil-truck"><span>'  : "";
            $noRepartidor =  $reg->repartidor !== null ? '': '<input class="" type="checkbox" id="checkPedido"  name="ckxPedido[]" value="'.$reg->pedidoid.'"/>';
            $msj =$reg->repartidor !== null ? '<input class="" type="checkbox" id="checkMsj-'.$reg->repartidor.'" name="ckxMsj[]" data-id="'.$reg->repartidor.'" value="'.$reg->pedidoid.'"/>' :"";
            $repartidor = $reg->repartidor !== null ? $reg->repartidorNombre . " ". '('.$reg->repartidor.')' : "";
            $desasignar =$reg->repartidor !== null ? '<button class="btn btn-danger btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Desasignar repartidor" onclick="desasignar('.$reg->pedidoid.','.$reg->repartidor.')" ><i class="mdi mdi-minus-circle m-n2"></i></button>' :"";
            $sended   = $reg->fecha_notificacion !== null ? '&nbsp;<i class="uil uil-envelope"></i>' : "";
            $reenviar = $reg->fecha_notificacion !== null
                ? '<button class="btn btn-primary btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Reenviar mensaje al repartidor" onclick="reenviarMensaje('.$reg->pedidoid.')"><i class="mdi mdi-send m-n2"></i></button>'
                : '';
            $rs = htmlspecialchars($reg->razonSocial, ENT_QUOTES, 'UTF-8');
            $data[]=array(
                "0"=> $noRepartidor . $msj,
                "1"=>'<div style="display:flex;gap:2px;"><button class="btn btn-warning btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Ver pedido" onclick="mostrar('.$reg->pedidoid.')"><i class="mdi mdi-eye m-n2"></i></button>'.'<a target="_blank" href="'.$url.$reg->pedidoid.'"><button class="btn btn-info btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Imprimir ticket"><i class="mdi mdi-printer m-n2"></i></button></a>'.'<button class="btn btn-success btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Enviar mensaje al cliente" onclick="sendMessageCustomizer('."'".$reg->telefono."'".','."'".$rs."'".')"><i class="uil uil-envelope m-n2"></i></button>'.'<button class="btn btn-secondary btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Cambiar estado" onclick="enProceso('.$reg->pedidoid.')"><i class="mdi mdi-cog m-n2"></i></button>'.$desasignar.$reenviar.'</div>',
                "2"=> $reg->pedidoid. $currentDay,
                "3"=> $reg->fecha,
                "4"=> $reg->clienteId,
                "5"=> $rs,
                "6"=> $reg->telefono,
                "7"=> "$".$reg->total,
                "8"=> strval($reg->fecha_asignacion),
                "9"=> strval($reg->fecha_notificacion),
                "10"=> $estado,
                "11"=> $repartidor,
                "12"=> $repartidorAsignador,
                "13"=> $sended,
            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

    case 'listarArticulos':
        require_once "../modelos/Articulo.php";
        $articulo=new Articulo();

        $rspta=$articulo->listarActivosVenta();
        $data=Array();

        while ($reg=$rspta->fetch_object()) {
            $data[]=array(
                "0"=>'<button class="btn btn-warning" onclick="agregarDetalle('.$reg->idarticulo.',\''.$reg->nombre.'\','.$reg->precio_venta.')"><span class="fa fa-plus"></span></button>',
                "1"=>$reg->nombre,
                "2"=>$reg->categoria,
                "3"=>$reg->codigo,
                "4"=>$reg->stock,
                "5"=>$reg->precio_venta,
                "6"=>"<img src='../files/articulos/".$reg->imagen."' height='50px' width='50px'>"

            );
        }
        $results=array(
            "sEcho"=>1,//info para datatables
            "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
        break;

    //listarMensajes
    case 'listarMensajes':

        $rspta=$reparto->listarMensajes((int)($_GET["idventa"] ?? 0));

        echo ' <thead style="background-color:#8b74d2c7;color:white">
        <th width="5%">ID</th>
        <th width="8%">Tipo</th>
        <th width="15%">Fecha</th>
        <th>Mensaje</th>	
       </thead>';


        while ($reg=$rspta->fetch_object()) {

            $reparto->marcarLeido($reg->pedidoid);

            $color="";
            $tipo='<span class="mdi mdi-rotate-315 mdi-send-check" ></span> Enviado';
            if($reg->tipo=="0"){
                $tipo='<span class="mdi mdi-reply" ></span> Recibido';
                if($reg->estado=="0")$color='bgcolor="#cccccc"';
            }

            $mensajeEsc = htmlspecialchars($reg->mensaje, ENT_QUOTES, 'UTF-8');
            echo '<tr class="filas"  >
			<td '.$color.' >'.$reg->id.'</td>
			<td '.$color.'  >'.$tipo.'</td>
			<td '.$color.'  >'.$reg->fecha.'</td>
			<td '.$color.'  >'.$mensajeEsc.'</td>
			
			</tr>';

        }
        if($rspta->num_rows==0){
			echo '<tr style="text-align:center">
				<td colspan="6">No se encontraron mensajes</td>		
				</tr>';
		}
        break;

    case 'selectCliente':
        require_once "../modelos/Persona.php";
        $persona = new Persona();

        $rspta = $persona->listarc();
        echo '<option ></option>';
        while ($reg = $rspta->fetch_object()) {
            echo '<option value='.$reg->codigo.'>'.$reg->razonSocial.'</option>';
        }
        break;
    case 'repartidores':
        require_once "../modelos/Repartidor.php";
        $repartidor = new Repartidor();
        $tipo = '';
        $result = $repartidor->listar();
        while ($reg = $result->fetch_object()) {
            $tipo = $tipo . '<option value='.$reg->id.'>'.$reg->nombre.'</option>';
        }
        echo json_encode($tipo);
        break;
    case 'obtenerPedidos':
        if ( !empty($pedidosIdToSendMsj)  ) {
            $rspta=$reparto->obtenerTelefono($pedidosIdToSendMsj);
            $telefonos= array();
            while ($reg=$rspta->fetch_object()) {
                $telefonos[]=$reg->telefono;
            }
            // Pre-cargar los productos de TODOS los pedidos en una sola query y agruparlos
            // por pedidoid (antes: 1 query por pedido dentro del loop → N+1).
            $prodsPorPedido = array();
            $prodsAll = $reparto->obtenerProductosPorPedidos($pedidosIdToSendMsj);
            if ($prodsAll) {
                while ($p = $prodsAll->fetch_object()) {
                    $prodsPorPedido[$p->pedidoid][] = ['cantidad'=> $p->cantidad,'codProd' => $p->producto, 'producto' =>$p->descripcion];
                }
            }

            $pedidos= array();
            $rspta=$reparto->obtenerPedidos($pedidosIdToSendMsj);
            while ($reg=$rspta->fetch_object()) {
                $productos = isset($prodsPorPedido[$reg->pedidoid]) ? $prodsPorPedido[$reg->pedidoid] : [];
                $pedidos[]= ['cantidad'=> $reg->cantidadTotal,'telefono' => $reg->telefonoR, 'cliente' =>$reg->razonSocial,  "total" =>  $reg->total, "pedido" => $reg->pedidoid, "latitud" =>  $reg->latitud, "longitud" => $reg->longitud, "direccion" => $reg->direccion, "telCliente" => $reg->telefonoC, "productos" => $productos];

            }


            $ordenados = array();
            foreach ($telefonos as $telefono){
                foreach ($pedidos as $pedido){
                    if($pedido['telefono'] === $telefono){
                        $ordenados[] = $pedido;
                    }
                }
            }

            echo json_encode($ordenados);
        }
        break;
}
?>