<?php
require_once dirname(__DIR__).'/config/auth.php';
include_once '../config/Connection.php';

// Mutación (import CSV por POST, sin switch) → exige token CSRF.
requireCsrf();

// --- Validación de archivo subido (whitelist estricta) ---------------------
// El nombre del CSV define la tabla destino (DROP/CREATE/LOAD DATA), por eso
// debe limitarse a un conjunto cerrado. Solo se aceptan exactamente estos
// nombres base (las únicas tablas que crearTabla() sabe crear) y extensión csv.
$orig = (string) $_FILES['uploaded_file']['name'];
$ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
$base = strtolower(pathinfo($orig, PATHINFO_FILENAME));
$tablasPermitidas = ['vendedores', 'clientes', 'articulos'];
if ($ext !== 'csv' || !in_array($base, $tablasPermitidas, true)) {
    http_response_code(400);
    exit('archivo no permitido');
}

//ini_set('upload_max_filesize', '200M');
$ruta= "";
//echo ini_get('upload_max_filesize'), ", " , ini_get('post_max_size');
$cont=0;
//$resu=Connection::runQuery("select @@datadir;");
//$fila = mysqli_fetch_row($resu);
	  
// Nombre de archivo controlado: siempre <base>.csv con $base ya validado
// contra la whitelist. Nunca se usa el nombre crudo del cliente.
$target_path1 = $base . ".csv";
//echo $_FILES['uploaded_file']['tmp_name'].":::".$target_path1;
if(move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $target_path1)) {

	//echo  "OK";
	$LINES=1;

	Connection::runQuery("DROP TABLE IF EXISTS ".$base);
	crearTabla($base);
	$sql = "LOAD DATA  LOCAL INFILE '".$base.".csv'
        REPLACE INTO TABLE ".$base."
	   CHARACTER SET UTF8 
       FIELDS TERMINATED BY ','
       OPTIONALLY ENCLOSED BY '\"' 
	   LINES TERMINATED BY '\r\n' 
       IGNORE ".$LINES." LINES;";
	  
	   $request=Connection::runQuery($sql);
	 //  echo $request;
	if($request=="1"){
	  //rename( $empresa.$array[0].".csv" , $empresa."backup/".$array[0].".csv" );
	 echo  "OK";
	}else{
	  echo  $request;
	}
	
		
}else{
echo "error #".$_FILES['uploaded_file']['name'];
} ///--------------------------------------
	//echo $cont;

 function  crearTabla($op){
  
     switch ($op) {
	   case 'vendedores':
	   
		   Connection::runQuery("CREATE TABLE `vendedores` (
			  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
			  `nombre` varchar(250) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `telefono` varchar(120) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `version` varchar(120) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `supervisor` varchar(10) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;");
			Connection::runQuery("ALTER TABLE `vendedores`ADD PRIMARY KEY (`codigo`);");
			
	   	break;
		
	  case 'clientes':
	 // codigo,razon_social,direccion,zona,vendedor,telefono,lista,orden,ramo,localidad,depositoc,latitud,longitud

	   Connection::runQuery("CREATE TABLE `clientes` (
			  `codigo` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
			  `razonSocial` varchar(500) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `direccion` varchar(500) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `zona` varchar(50) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `vendedor` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
			  `telefono` varchar(50) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `lista` varchar(10) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `orden` varchar(50) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `ramo` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `localidad` varchar(300) COLLATE utf8_spanish_ci DEFAULT NULL,
			  `deposito` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
			  `latitud` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
			  `longitud` varchar(100) COLLATE utf8_spanish_ci NOT NULL
			  
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;");
			
			 Connection::runQuery("ALTER TABLE `clientes` ADD PRIMARY KEY (`codigo`)");
	   	break;
		
	  case 'articulos':
	  
	  			$fp = fopen("articulos.csv","r");
				$columnas = explode(",", fgets($fp));
				
				$camposLista="";$l=1;
				for($i=2; $i< count($columnas); $i++){
				
				 if (strncasecmp(str_replace("\"","",$columnas[$i]), "lista", 5) === 0){
					 $camposLista.="`lista".$l."`  DECIMAL(10,2),";
					   $l++;
					 }
				}
				fclose($fp);
	 // codigo,descripcion,lista1,lista2,lista3,lista4,lista5,lista6,lista7,linea,rubro,capacidad,pack,impint,codbarra,topescant,iva,depositoa
	          Connection::runQuery("CREATE TABLE `articulos` (
				  `codigo` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
				  `descripcion` varchar(250) DEFAULT NULL,".$camposLista."
				  `linea` varchar(150)  DEFAULT NULL,
				  `rubro` varchar(150)  DEFAULT NULL,
				  `capacidad` varchar(20)  DEFAULT NULL,
				  `pack` varchar(20)  DEFAULT NULL,
				  `impInt` varchar(20)  DEFAULT NULL,
				  `codBarra` varchar(120)  DEFAULT NULL,
				  `topecant` varchar(10) NOT NULL,
				  `iva` varchar(10) NOT NULL,
				  `deposito` varchar(50)  DEFAULT NULL
				) ENGINE=InnoDB CHARACTER SET = utf8 , COLLATE = utf8_general_ci ;");
				
				Connection::runQuery("ALTER TABLE `articulos` ADD PRIMARY KEY (`codigo`)");

	   	break;
	}
	
	
	
  } 

?>