<?php
require_once '../config/auth.php';
require_once "../modelos/Consultas.php";

$consulta = new Consultas();

$op = $_GET['op'] ?? '';
csrfGuard($op, ['comprasfecha', 'ventasfechacliente']);

switch ($_GET["op"]) {
	

    case 'comprasfecha':
    $fecha_inicio=$_REQUEST["fecha_inicio"];
    $fecha_fin=$_REQUEST["fecha_fin"];

		$rspta=$consulta->comprasfecha($fecha_inicio,$fecha_fin);
		$data=Array();

		while ($reg=$rspta->fetch_object()) {
			$data[]=array(
            "0"=>$reg->fecha,
            "1"=>$reg->usuario,
            "2"=>$reg->proveedor,
            "3"=>$reg->tipo_comprobante,
            "4"=>$reg->serie_comprobante.' '.$reg->num_comprobante,
            "5"=>$reg->total_compra,
            "6"=>$reg->impuesto,
            "7"=>($reg->estado=='Aceptado')?'<span class="label bg-green">Aceptado</span>':'<span class="label bg-red">Anulado</span>'
              );
		}
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		echo json_encode($results);
		break;

     case 'ventasfechacliente':
        $fecha_inicio=$_REQUEST["fecha_inicio"];
        $fecha_fin=$_REQUEST["fecha_fin"];
        $idcliente=$_REQUEST["idcliente"];
	
	   $totalPedido=0;
	   $rspta=$consulta->pedidosfechacliente($fecha_inicio,$fecha_fin,$idcliente);
	  
	    while ($reg=$rspta->fetch_object()) {
		  $totalPedido++;   
		}

        $rspta=$consulta->ventasfechacliente($fecha_inicio,$fecha_fin,$idcliente);
        $data=Array();

		$totalImporte=0.00;
        while ($reg=$rspta->fetch_object()) {
            $data[]=array(
            "0"=>$reg->fechaPedido,
            "1"=>$reg->codigoCliente,
            "2"=>$reg->razonSocial,
            "3"=>$reg->ramo,
            "4"=>$reg->localidad,
            "5"=>$reg->pedidoid,
            "6"=>$reg->producto,
            "7"=>$reg->descripcion,
            "8"=>$reg->cantidad,
            "9"=>$reg->precio ,
            "10"=>$reg->subtotal,
            "11"=>$reg->listaPrecio
			);
			//$totalPedido++;
			$totalImporte=$totalImporte+$reg->subtotal;
        }
        $results=array(
		     "totalPedido"=>$totalPedido,
			 "totalImporte"=>'$'.round($totalImporte,0),
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
        echo json_encode($results);
        break;
}
 ?>