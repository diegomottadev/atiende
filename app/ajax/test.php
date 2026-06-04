<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Documento sin t&iacute;tulo</title>
</head>

<body>


<?php 

$fp = fopen("articulos.csv","r");
$columnas = explode(",", fgets($fp));

$camposLista="";$l=1;
for($i=2; $i< count($columnas); $i++){

 if (strncasecmp(str_replace("\"","",$columnas[$i]), "lista", 5) === 0){
     $camposLista.="`lista".$l."` varchar(50) COLLATE utf8_spanish_ci DEFAULT NULL,";
	   $l;
	 }
}
fclose($fp);


?>
</body>
</html>
