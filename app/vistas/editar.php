<?php
//header('Content-Type: application/json; charset=utf-8');
//header('Content-Type: application/json; charset=utf-8');
header ('Content-type: text/html; charset=utf-8');

include_once("../config/Connection.php");
$request=Connection::runQuery("SELECT `id`, titulo,`mensaje`, `fecha`, `cantidad`, `destino`, `estado`  FROM `mensajes` WHERE `id` like '".$_GET["id"]."'  ");
if (false === $request) {
    echo mysqli_error();
}


if($request)
 while ($row =  mysqli_fetch_assoc($request)){
	$rows[] =    $row ;  
}


$request=Connection::runQuery("SELECT destino FROM `mensajes` WHERE `id` like '".$_GET["id"]."' ");
if( mysqli_num_rows ($request )>0){
   $row = mysqli_fetch_assoc($request);
   $rows[]=$row ;
  
}




echo json_encode($rows,JSON_UNESCAPED_UNICODE);

?>