<?php 
require_once dirname(__DIR__).'/config/auth.php';   // endpoint legacy: exige sesión
include_once '../Connection.php';

$colores=array("#f7a547","#3bf796","#2775b5","#d65a31","#3b0175","#fcd8c4","#387093","#6e89d8","#3ed81c","#96e2e8","#92b9dd","#99b72c","#5700f9","#ffeead","#079632","#16207c","#00a81e","#6b74d3","#f9cfc0","#ffcce3","#4c33a0","#b399ef","#f4aaf7","#3a7a06","#eaa1cf","#93f2ae","#89d358","#95eda9","#d8020c","#08567a","#01821b","#8ce5f7","#eaa448","#1788ea","#bde567","#91ffac","#e096df","#2bc681","#b23937","#ed97bb","#e8b0f2","#6fdb8a","#8afcf2","#8559db","#e5b995","#c2d5f9","#39d834","#efb1df","#f4723a","#edada3","#186d9b","#fc1ea7","#67e0b8","#edfca1","#71fce5","#5c8bc9","#9ff9e9","#fff87a","#bbe246","#4946f2");
$colores1=array('#14bee0','#c5d159','#db9872','#1bf707','#a49eff','#804cbf','#ed8ed5','#c1c928','#01425e','#1d8893','#ce6c37','#d3623d','#7affa4','#1d207f','#96f7b7','#d3f470','#cbbfff','#3bceae','#dc37e8','#f04bfc','#9edced','#0b4da3','#a82848','#97f4e6','#fcb0d7','#ddb606','#e886bf','#f2c8a7','#74dbd7','#1c3bc9','#d87627','#c933ce','#f4f7a0','#c90494','#97ef53','#584cb2','#acef88','#dd4fc8','#eaa285','#37f2fc','#68d8a6','#edaad7','#f90254','#b2881e','#c3bbf7','#47e5e0','#d6b8f9','#b6aded','#486df2','#59bc34','#49f47f','#fc19e9','#e0677d','#0e346d','#1fef83','#efb353','#516eff','#f48d96','#fffcbf','#293cb5');

$Meses = array('','Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio','Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

//En analisis
//Finalizado
//Pendiente
$result=Connection::runQuery("SELECT flag, count(*) FROM `pedidos` GROUP BY `flag`");
$rows =array(array("Pendientes","0"),array("Pendiente","0"),array("Descargados","0"));
while($row=mysqli_fetch_row($result)){
	//echo  $row[0];
 if($row[0]=="0")
 	$rows[0]=array("Confirmados",$row[1]);
 if($row[0]=="-1")
 	$rows[1]=array("Pendiente",$row[1]);
 if($row[0]=="1")
	$rows[2]=array("Descargados",$row[1]);


}


$result=Connection::runQuery("SELECT COUNT(*),NOW() FROM `pedidos` WHERE DATE(fecha)= DATE(NOW())");
$rows1 ;
while($row=mysqli_fetch_row($result)){

$rows1= $row [0]; 
$rows2= $row [1]; 

}

$result=Connection::runQuery("SELECT COUNT(*) FROM `pedidos`  WHERE DATE(fecha)= DATE(NOW()) ");
$rows1 ;
while($row=mysqli_fetch_row($result)){

$rows3= $row [0]; 
}
//graficos 


$result=Connection::runQuery("SELECT articulos.rubro, sum(`cantidad`) FROM `pedidos`, articulos WHERE articulos.codigo= pedidos.producto GROUP BY articulos.rubro");
//$rowsArea =array();
$areaRaiz = array();
$areaData = array();
$row_cnt = mysqli_num_rows($result);



//******************************************** */

   $requestArea=Connection::runQuery("SELECT id, area FROM areas  ");
   if($requestArea)
   while($row=mysqli_fetch_array($requestArea)){
      $areas[$row["area"]] = $row["id"];
   }
//*************************

 $areaData['labels'][]='\'\'';
 $i=0;
while($row=mysqli_fetch_row($result)){

   $area=$row[0];
   if(array_search($row[0], $areas) !== false)
      $area=array_search($row[0], $areas);

 $areaData['datasets'][] ='{data:['.$row[1].'],fillColor:\''.$colores[$i].'\',title:\''.$area.'\',}';
$i++;

}
$areaRaiz= $areaData;

//informacion por motivo
$result=Connection::runQuery("SELECT flag, count(*) FROM `pedidos` GROUP BY `flag` ");
//$rowsArea =array();
$motivoRaiz = array();
$motivoData = array();


 $motivoData['labels'][]='\'\'';
 $i=0;
while($row=mysqli_fetch_row($result)){

 $motivoData['datasets'][] ='{data:['.$row[1].'],fillColor:\''.$colores1[$i].'\',title:\''.$row[0].'\',}';
$i++;

}
$motivoRaiz= $motivoData;

//******INFO DF HISTORICO
$result=Connection::runQuery("SELECT DATE_FORMAT(fecha, '%d'),count(*) FROM `pedidos` WHERE MONTH(fecha)=MONTH(now()) GROUP BY DATE_FORMAT(fecha, '%d') ORDER BY DATE_FORMAT(fecha, '%d') ASC");
//$rowsArea =array();

$labels=array();
$data=array();
while($row=mysqli_fetch_row($result)){
   $labels[]=$row[0];
   $data[]=$row[1];
}
$evolucion= implode(",",$labels).";".implode(',',$data);
//-------------------------------------------
//******INFO DF HISTORICO mensual
$result=Connection::runQuery("SELECT flag, count(*) FROM `pedidos` GROUP BY `flag` ");
$labels =array();
$data =array();
$labels;
$data;
while($row=mysqli_fetch_row($result)){
   $labels[]= strtolower ($row[0]);
   $data[]=$row[1];
}
$evolucionMes= implode(",",$labels).";".implode(',',$data);

//--------------------------------------------------------------------




$data =json_encode($rows,JSON_UNESCAPED_UNICODE);
$graficoArea=json_encode($areaRaiz,JSON_UNESCAPED_UNICODE);
$graficoMotivo=json_encode($motivoRaiz,JSON_UNESCAPED_UNICODE);

echo $data."|".$rows1."|".$rows2."|".$rows3."|".$graficoArea."|".$graficoMotivo."|".$evolucion."|".$evolucionMes."|";

?>
