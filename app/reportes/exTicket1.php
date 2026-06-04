<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Ticket</title>
        <meta charset="utf-8">
        <!--<meta name="viewport" content="width=device-width, initial-scale=1">-->
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=0.7">
      <!--  <meta http-equiv="content-type" content="text/html;" />-->
    <!--    <link rel="stylesheet" href="../public/css/bootstrap.min.css">-->
        <link rel="stylesheet" href="../public/css/ticket.css">
		<style media="print">
			.zona_impresion{
				position: absolute;
				box-sizing: border-box;
				width: 60%;
				-webkit-box-shadow: 7px 6px 21px -2px rgba(0,0,0,0.58);
				-moz-box-shadow: 7px 6px 21px -2px rgba(0,0,0,0.58);
				box-shadow: 7px 6px 21px -2px rgba(0,0,0,0.58);	
			}

			.zona_impresion table{		  
					width: 300px !important;
				}    
		</style>    
    </head>
    <body>
        <?php
        // incluimos la clase venta
        require_once "../modelos/Venta.php";
        include_once("../config/Connection.php");
        $venta = new Venta();

        //en el objeto $rspta obtenemos los valores devueltos del metodo ventacabecera del modelo
        $rspta = $venta->ventacabecera($_GET["id"]);
        $reg=$rspta->fetch_object();

        //establecemos los datos de la empresa
        $empresa = "Atiende ";
        $documento = "30-21548798-5";
        $direccion = "Calle los alpes 120";
        $telefono = "3764329554";
        $email = "leandro@atiende.lat";
	    ?>
        <div class="zona_impresion">
	<!--codigo imprimir-->
	
	<table border="0" align="center" width="300px" class="header">
		<tr>
			<td align="center">
				<!--mostramos los datos de la empresa en el doc HTML-->
				.::<strong> <?php echo $empresa; ?></strong>::.<br>
				<?php echo $documento; ?><br>
				<?php echo $direccion . '-'.$telefono; ?><br>
			</td>
		</tr>
		<tr>
			<td align="center"><?php echo $reg->fecha; ?></td>
		</tr>
		<tr> 
			<td align="center"></td>
		</tr>
		<tr>
			<!--mostramos los datos del cliente -->
			<td>Codigo: <?php echo $reg->codigo; ?>
			</td>
		</tr>
		<tr>
			<!--mostramos los datos del cliente -->
			<td>Cliente: <?php echo $reg->cliente; ?>
			</td>
		</tr>
		<tr>
			<!--mostramos los datos del cliente -->
			<td>Dirección: <?php echo $reg->direccion; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo "Localidad: ".$reg->localidad; ?>
			</td>
		</tr>
		<tr>
			<td>
				N° de venta: <?php echo $reg->pedidoid; ?>
			</td>
		</tr>
	</table>
	

	<!--mostramos lod detalles de la venta -->

	<table border="0" align="center" width="300px">
		<tr>
		   <td>CANT.</td>
			<td>COD.</td>
			<td>DESCRIPCION</td>
			<td align="right">IMPORTE</td>
		</tr>
		<tr>
			<td colspan="4">=============================================</td>
		</tr>
		<?php
		$rsptad = $venta->ventadetalles($_GET["id"]);
		$cantidad=0;$totales=0.00;
		while ($regd = $rsptad->fetch_object()) {
		 	echo "<tr>";
			echo "<td>".$regd->cantidad."</td>";
		 	echo "<td>".$regd->codigo."</td>";
		 	echo "<td>".ucwords(strtolower($regd->articulo))."</td>";
		 	echo "<td align='right'>$ ".$regd->subtotal."</td>";
		 	echo "</tr>";
			if(strlen ($regd->dato9)>0){
					echo "<tr>";
					echo "<td>Obs</td>";
					echo "<td colspan='2'>".$regd->dato9."</td>";
				   echo "</tr>";
			
			}
			
			$totales=$totales+floatval($regd->subtotal);
		 	$cantidad+=$regd->cantidad;
		 } 

		 ?>
		 <!--mostramos los totales de la venta-->
		<tr>
			<td>&nbsp;</td>
			<td align="right"><b>TOTAL:</b></td>
			<td align="right"><b>$ <?php echo $totales; ?></b></td>
		</tr>
		<tr>
			<td colspan="4">N° de articulos: <?php echo $cantidad; ?> </td>
		</tr>
		<tr>
		
			<td colspan="4">&nbsp;</td>
		</tr>
	     <tr>
		    <td colspan="4"><strong>Observación:</strong></td>
		</tr>
		
		 <tr>
		    <td colspan="4">
			<?php 
			$result=Connection::runQuery("SELECT * FROM `pedidos` WHERE `pedidoid` like '".$_GET["id"]."' and `producto` like '.001'" );
		
			 while($row=mysqli_fetch_array($result)){
	 		
			 echo $row["descripcion"];
       			
			 }
			?>
			</td>
		</tr>
		<tr>
			<td colspan="4" align="center"><hr>¡Gracias por su compra!<br>Utilizamos Atiende</td>
		</tr>
		<tr>
			<td colspan="4" align="center">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4" align="center">&nbsp;</td>
		</tr>
	</table>
	
</div>
        
    </body>
</html>