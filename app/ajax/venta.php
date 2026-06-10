<?php
require_once '../config/auth.php';
require_once "../modelos/Venta.php";
require_once "../modelos/Vendedor.php";
require_once "../modelos/Persona.php";

$venta = new Venta();

$idventa=isset($_POST["idventa"])? limpiarCadena($_POST["idventa"]):"";
$mensaje=isset($_POST["mensaje"])? limpiarCadena($_POST["mensaje"]):"";
$idcliente=isset($_POST["clienteid"])? limpiarCadena($_POST["clienteid"]):"";
$tipo=isset($_POST["tipo"])? limpiarCadena($_POST["tipo"]):"";



$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'traerTelefono', 'listarDetalle', 'listar', 'listarArticulos', 'listarMensajes', 'selectCliente']);

switch ($_GET["op"]) {
	case 'guardaryeditar':
	if (empty($idventa)) {
			$rspta=$venta->insertar($idcliente,$idusuario,$tipo_comprobante,$serie_comprobante,$num_comprobante,$fecha_hora,$impuesto,$total_venta,$_POST["idarticulo"],$_POST["cantidad"],$_POST["precio_venta"],$_POST["descuento"]); 
			echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
		}else{
			
		}
		break;
	case 'anular':
			$rspta=$venta->anular($idventa);
			echo $rspta ? "Ingreso anulado correctamente" : "No se pudo anular el ingreso";
	break;

	case 'editarEstado':
		$nuevo=$venta->editarEstado($_GET["pedidoid"]);
		if ($nuevo === null)            echo "No se pudo cambiar el estado";
		elseif ((string)$nuevo === '2') echo "Pedido marcado como Entregado";
		else                            echo "Pedido vuelto a Pendiente";
		break;
	
	case 'mostrar':
		$rspta=$venta->mostrar($idventa);
		echo json_encode($rspta);
		break;
		
 	case 'traerTelefono':
		 $rspta=$venta->traerTelefono($idventa);
		echo json_encode($rspta);
		break;

    case 'guardarMensajeCliente':
        $idventa=isset($_POST["idventa"])? limpiarCadena($_POST["idventa"]):"";
        $mensaje=isset($_POST["mensaje"])? limpiarCadena($_POST["mensaje"]):"";
        $idcliente=isset($_POST["clienteid"])? limpiarCadena($_POST["clienteid"]):"";
        $tipo=isset($_POST["tipo"])? limpiarCadena($_POST["tipo"]):"";

        $rspta=$venta->insertarMensaje($idcliente,$idventa,$mensaje,$tipo);
        echo json_encode($rspta);
        break;
		
	case 'guardarMensaje':
        $idventa=isset($_GET["idventa"])? limpiarCadena($_GET["idventa"]):"";
        $mensaje=isset($_GET["mensaje"])? limpiarCadena($_GET["mensaje"]):"";
        $idcliente=isset($_GET["clienteid"])? limpiarCadena($_GET["clienteid"]):"";
        $tipo=isset($_GET["tipo"])? limpiarCadena($_GET["tipo"]):"";

        $rspta=$venta->insertarMensaje($idcliente,$idventa,$mensaje,$tipo);
		echo json_encode($rspta);
		break;	
		
	case 'listarDetalle':
		//recibimos el idventa
		$id=$_GET['id'];

		$rspta=$venta->listarDetalle($id);
		$total=0;
		echo ' <thead style="background-color:#8b74d2c7;color:white">
        <th>Codigo</th>
        <th>Articulo</th>
        <th align="center">Cantidad</th>
        <th align="right">Precio Venta</th>
        <th align="center">Descuento</th>
        <th align="right">Subtotal</th>
        <th align="right">Obs</th>
        <th align="right">Imagen</th>
       </thead>';
	
		while ($reg=$rspta->fetch_object()) {

            if (file_exists("../files/articulos/".$reg->producto.".jpg")) {
                $imagen="../files/articulos/".$reg->producto.".jpg?".date("YmdHis");
            } else{
                $imagen="../files/articulos/camara.jpg";
            }


			echo '<tr class="filas">
			<td>'.$reg->producto.'</td>
			<td>'.$reg->descripcion.'</td>
			<td>'.$reg->cantidad.'</td>
			<td>$'.$reg->precio.'</td>
			<td>'.$reg->descuento.'</td>
			<td>$'.$reg->subtotal.'</td>
			<td>'.$reg->comment.'</td>
			<td><img src="'. $imagen.'" height="50px" width="50px"></td></tr>';
			$total=$total+($reg->precio*$reg->cantidad-$reg->descuento);
		}

		echo '<tfoot>
         
         <th colspan="5" align="right">TOTAL</th>
         <th><h4 id="total">&#36;'.number_format($total,2,',','.').'</h4><input type="hidden" name="total_venta" id="total_venta"></th>
       </tfoot>';
		break;

    case 'listar':

	  $rspta=$venta->listarObs();
	  $obs= array();
	  $array= array();
	  while ($reg=$rspta->fetch_object()) {
	      $array[]=$reg->pedidoid;
		  $obs[]=$reg->descripcion;
	  }

		$rspta=$venta->listarPedidos();

		$data=Array();
          

		while ($reg=$rspta->fetch_object()) {
            $mensajesSinLeer=$venta->listarMensajesNoLeidos($reg->pedidoid);
            $modal="";
            $key = array_search($reg->pedidoid, $array);

            if($key > -1){
                $modal='<a href="#" class="text-info" data-bs-toggle="tooltip" title="Ver observación" onclick="comentario(\''.$obs[$key].'\')" ><i class="mdi mdi-comment-processing" style="font-size:1rem;vertical-align:middle;"></i></a>';
            }
            $nombreVendedor = '';
            $url='/ticket/';
            if($reg->estado==-1)
              $estado="<span class='badge bg-primary'>Pendiente</span>";
            if($reg->estado==0 || $reg->estado==1)
              $estado="<span class='badge bg-primary'>Pendiente</span>";
            if($reg->estado==2)
               $estado="<span class='badge bg-success'>Entregado</span>";
            if($reg->estado==3)
                $estado="<span class='badge bg-danger'>Anulado</span>";
            if($reg->fechaAsignacion==! null && $reg->fechaNotificacion === null){
                $estado="<span class='badge bg-primary'>Asignado</span>";
            }
            if($reg->fechaAsignacion==! null && $reg->fechaNotificacion !== null){
                $estado="<span class='badge bg-success'>Enviado</span>";
            }
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
            // Pedido Entregado (flag=2) → la columna "Pagado" pasa a "Confirmado".
            if ($reg->estado == 2) {
                $pagoElectronico = '<span class="badge bg-success">Confirmado</span>';
            }
            if($reg->vendedor!= null){
                $ved = new Vendedor();

                $vendedorExist = $ved->mostrar($reg->vendedor);

                $nombreVendedor = $vendedorExist["nombre"];
                $telVendedor = $vendedorExist["telefono"];

                $cliente = new Persona();
                $clienteExist = $cliente->mostrar($reg->clienteId);
                $telefonoPedido = $reg->telefono;
//                die(json_encode($reg->telefono,$telVendedor));
                if ($reg->telefono == $telVendedor ){
                    $telefonoPedido = $reg->telefono . ' <strong> (V) </strong>';
                }
                $clienteTelExit = $clienteExist["telefono"] !=null ? $clienteExist["telefono"]: $telefonoPedido  ;

            }else{
                $clienteTelExit =  $reg->telefono  ;
            }



            $fecha = new DateTime();
            //$fecha->sub(new DateInterval('P1D'));
                $currentDay = $fecha->format('Y-m-d' ) === $reg->fecha ? ' <span class="fa fa-asterisk"><span>' : "";

                $data[]=array(
                "0"=>'<button class="btn btn-warning btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Ver pedido" onclick="mostrar('.$reg->pedidoid.')"><i class="mdi mdi-eye m-n2"></i></button>'.'<a target="_blank" href="'.$url.$reg->pedidoid.'"> <button class="btn btn-info btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Imprimir ticket"><i class="mdi mdi-printer m-n2"></i></button></a> '.'<button class="btn btn-success btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Enviar mensaje al cliente" onclick="sendMessageCustomizer('."'".$reg->telefono."'".','."'".$reg->razonSocial."'".')"><i class="uil uil-envelope m-n2"></i></button> '.'<button class="btn btn-secondary  btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Cambiar estado" onclick="enProceso('.$reg->pedidoid.','.intval($reg->estado).')" ><i class="mdi mdi-cog m-n2"></i></button></button></a> <button class="btn btn-danger btn-sm btn-icon-line" data-bs-toggle="tooltip" title="Anular pedido" onclick="anular('.$reg->pedidoid.')" ><i class="mdi mdi-minus-circle m-n2"></i></button>',
                "1"=>$reg->pedidoid. $currentDay,
                "2"=>$reg->fecha,
                "3"=>$reg->clienteId,
                "4"=>$reg->razonSocial,
                "5"=>$clienteTelExit,
                "6"=>"$".$reg->total,
                "7"=>$pagoElectronico,
                "8"=>$formaPago,
                "9"=> $modal,
                "10"=>$estado,
                "11"=>  $reg->vendedor== null ? '<span class="badge bg-dark text-light">Cliente<span>' : '<span class="badge bg-warning">Vendedor<span>' ,
                "12"=>  $nombreVendedor,
                "13"=>$mensajesSinLeer['noleidos'] > 0,

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
			
	$rspta=$venta->listarMensajes($_GET["idventa"]);
		
	  echo ' <thead style="background-color:#8b74d2c7;color:white">
        <th width="5%">ID</th>
        <th width="8%">Tipo</th>
        <th width="15%">Fecha</th>
        <th>Mensaje</th>
	
       </thead>';
	
	     
		while ($reg=$rspta->fetch_object()) {
		
		 $venta->marcarLeido($reg->pedidoid);
		
		 $color="";
		 $tipo='<span class="mdi mdi-rotate-315 mdi-send-check"></span> Enviado';
		  if($reg->tipo=="0"){
		   $tipo='<span class="mdi mdi-reply"></span> Recibido';
		    if($reg->estado=="0")$color='bgcolor="#cccccc"';
		   }
		   
			echo '<tr class="filas"  >
			<td '.$color.' >'.$reg->id.'</td>
			<td '.$color.'  >'.$tipo.'</td>
			<td '.$color.'  >'.$reg->fecha.'</td>
			<td '.$color.'  >'.$reg->mensaje.'</td>
			
			</tr>';
			
		}
		if($rspta->num_rows==0){
			echo '<tr style="text-align:center">
				<td colspan="4">No se encontraron mensajes</td>		
				</tr>';
		}
		break;
			
	case 'selectCliente':
			require_once "../modelos/Persona.php";
			$persona = new Persona();

			$rspta = $persona->listarc();
				echo '<option ></option>';
			while ($reg = $rspta->fetch_object()) {
				echo '<option value='.$reg->id.'>'.$reg->razonSocial.'</option>';
			}
			break;

		
}
 ?>