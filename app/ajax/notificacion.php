<?php
require_once '../config/auth.php';

header('Content-Type: application/json');//cabecera json

include_once("../config/Connection.php");
 $json= array();
 $rows= array();
$request=Connection::runQuery("SELECT * FROM `fidelizar` WHERE `estado` = 0 and `tipo` = 0 ORDER by fecha  DESC" );
	
while ($row = mysqli_fetch_assoc($request)){
    
	 $rows[] =  $row  ; 
	 
    }
$json[]=	$rows;
$rows= array();
$request=Connection::runQuery("SELECT * FROM `msj_reclamos` WHERE `respondido` = 0 and `tipo` = 1 ORDER BY `fecha` DESC" );
	
while ($row = mysqli_fetch_assoc($request)){
    
	 $rows[] =  $row  ; 
	 
    }
$json[]=	$rows;	

$data =json_encode($json,JSON_UNESCAPED_UNICODE);
header( 'Content-Length: ' . strlen($data) );

echo $data   ;

?>