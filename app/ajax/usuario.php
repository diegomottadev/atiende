<?php
// verificar (login) y salir (logout) no requieren sesión previa
$_op = $_GET['op'] ?? $_POST['op'] ?? '';
if ($_op !== 'verificar' && $_op !== 'salir') {
    require_once '../config/auth.php';
}
require_once "../modelos/Usuario.php";

$usuario=new Usuario();

$idusuario=isset($_POST["idusuario"])? limpiarCadena($_POST["idusuario"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$tipo_documento=isset($_POST["tipo_documento"])? limpiarCadena($_POST["tipo_documento"]):"";
$num_documento=isset($_POST["num_documento"])? limpiarCadena($_POST["num_documento"]):"";
$direccion=isset($_POST["direccion"])? limpiarCadena($_POST["direccion"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";
$email=isset($_POST["email"])? limpiarCadena($_POST["email"]):"";
$cargo=isset($_POST["cargo"])? limpiarCadena($_POST["cargo"]):"";
$login=isset($_POST["login"])? limpiarCadena($_POST["login"]):"";
$clave=isset($_POST["clave"])? limpiarCadena($_POST["clave"]):"";
$imagen=isset($_POST["imagen"])? limpiarCadena($_POST["imagen"]):"";

// CSRF: 'verificar' (login) y 'salir' (logout) van exentos — corren sin sesión
// previa y no pasaron por auth.php, así que no hay token todavía.
// 'permisos' es mutación (asigna permisos) pese al nombre → exige token.
if ($_op !== 'verificar' && $_op !== 'salir') {
    csrfGuard($_op, ['mostrar', 'listar']);
}

switch ($_GET["op"]) {
	case 'guardaryeditar':

	$MAX_IMG_BYTES = 2 * 1024 * 1024; // 2 MB
	$allowedImg    = ['image/jpeg' => 'jpg', 'image/png' => 'png']; // MIME real => extensión forzada
	if (!file_exists($_FILES['imagen']['tmp_name'])|| !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
		$imagen=$_POST["imagenactual"];
	} elseif ($_FILES['imagen']['size'] > $MAX_IMG_BYTES) {
		// Imagen supera el máximo permitido: se conserva la actual y no se reemplaza
		$imagen=$_POST["imagenactual"];
	}else{
		// Validar por contenido real (no por el Content-Type del cliente, que es falsificable)
		$finfo    = new finfo(FILEINFO_MIME_TYPE);
		$realMime = $finfo->file($_FILES['imagen']['tmp_name']);
		$isImage  = @getimagesize($_FILES['imagen']['tmp_name']) !== false;
		if ($isImage && isset($allowedImg[$realMime])) {
			// Nombre aleatorio + extensión forzada desde el MIME real (evita .php disfrazado / path traversal)
			$imagen           = bin2hex(random_bytes(16)) . '.' . $allowedImg[$realMime];
			$destination_path = '/var/www/'.DB_NAME.'/files/usuarios/'.$imagen;
			move_uploaded_file($_FILES["imagen"]["tmp_name"], $destination_path);
		} else {
			$imagen=$_POST["imagenactual"];
		}
	}

	// Contraseña: se hashea con password_hash (sobre el valor crudo, sin htmlspecialchars).
	$claveRaw = trim($_POST['clave'] ?? '');
	if (empty($idusuario)) {
		$clavehash = password_hash($claveRaw, PASSWORD_DEFAULT);
		$rspta=$usuario->insertar($nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$login,$clavehash,$imagen,$_POST['permiso']);
		echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar todos los datos del usuario";
	}else{
		// En edición, solo se actualiza la clave si se ingresó una nueva (campo vacío = no se toca)
		$clavehash = ($claveRaw !== '') ? password_hash($claveRaw, PASSWORD_DEFAULT) : '';
		$rspta=$usuario->editar($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$login,$clavehash,$imagen,$_POST['permiso']);
		// Si el usuario editó su propio perfil, refrescar la sesión para que el avatar del topbar cambie
		if ($rspta && isset($_SESSION['idusuario']) && $idusuario == $_SESSION['idusuario']) {
			$_SESSION['imagen'] = $imagen;
			$_SESSION['nombre'] = $nombre;
		}
		echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
	}
	break;
	

	case 'desactivar':
	$rspta=$usuario->desactivar($idusuario);
	echo $rspta ? "Datos desactivados correctamente" : "No se pudo desactivar los datos";
	break;

	case 'activar':
	$rspta=$usuario->activar($idusuario);
	echo $rspta ? "Datos activados correctamente" : "No se pudo activar los datos";
	break;
	
	case 'mostrar':
	$rspta=$usuario->mostrar($idusuario);
	if (is_object($rspta)) { unset($rspta->clave); }
	elseif (is_array($rspta)) { unset($rspta['clave']); }
	echo json_encode($rspta);
	break;

	case 'listar':
	$rspta=$usuario->listar();
	$data=Array();

	while ($reg=$rspta->fetch_object()) {
		$imgFile = (strlen($reg->imagen) > 0 && file_exists('../files/usuarios/'.$reg->imagen))
			? '../files/usuarios/'.$reg->imagen
			: '../files/usuarios/user.png';
		$img = "<img src='".$imgFile."' alt='avatar' class='rounded-circle sombra-logo' style='width:40px;height:40px;object-fit:cover;' onerror=\"this.src='../files/usuarios/user.png'\">";
		
		$data[]=array(
			"0"=>($reg->condicion)?'<button class="btn btn-warning btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Editar" onclick="mostrar('.$reg->idusuario.')"><i class="mdi mdi-lead-pencil m-n2"></i></button>'.' '.'<button class="btn btn-danger btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Desactivar" onclick="desactivar('.$reg->idusuario.')"><i class="uil uil-times-circle m-n2"></i></button>':'<button class="btn btn-warning btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Editar" onclick="mostrar('.$reg->idusuario.')"><i class="mdi mdi-lead-pencil m-n2"></i></button>'.' '.'<button class="btn btn-primary btn-sm btn-icon-line" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Activar" onclick="activar('.$reg->idusuario.')"><i class="mdi mdi-checkbox-marked-circle-outline m-n2"></i></button>',
			"1"=>$reg->nombre,
			"2"=>$reg->tipo_documento,
			"3"=>$reg->num_documento,
			"4"=>$reg->telefono,
			"5"=>$reg->email,
			"6"=>$reg->login,
			"7"=> $img,
			"8"=>($reg->condicion)?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-danger">Inactivo</span>'
		);
	}

	$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
	echo json_encode($results);
	break;

	case 'permisos':
	require_once "../modelos/Permiso.php";
	$permiso=new Permiso();
	$rspta=$permiso->listar();
	$id=(int)($_GET['id'] ?? 0);
	$marcados=$usuario->listarmarcados($id);
	$valores=array();
	while ($per=$marcados->fetch_object()) {
		array_push($valores, $per->idpermiso);
	}
	// Mapa permiso id → session key (mismo mapeo que usa 'verificar')
	$sessionMap = [
		1  => 'escritorio',
		2  => 'reclamos',
		3  => 'consultas',
		4  => 'ventas',
		5  => 'seguridad',
		6  => 'mensajes',
		8  => 'bd',
		9  => 'vendedores',
		10 => 'repartos',
		11 => 'configuracion',
	];
	while ($reg=$rspta->fetch_object()) {
		$sessionKey = $sessionMap[$reg->idpermiso] ?? null;
		// Solo mostrar si el tenant/usuario logueado tiene este módulo habilitado
		if ($sessionKey !== null && empty($_SESSION[$sessionKey])) continue;
		$sw=in_array($reg->idpermiso,$valores)?'checked':'';
		$chkId='permisoChk'.$reg->idpermiso;
		echo '<div class="col-12 col-md-6">'
			.'<div class="form-check">'
			.'<input class="form-check-input" type="checkbox" '.$sw.' name="permiso[]" value="'.$reg->idpermiso.'" id="'.$chkId.'">'
			.'<label class="form-check-label" for="'.$chkId.'">'.$reg->nombre.'</label>'
			.'</div></div>';
	}
	break;

	case 'verificar':
	$logina  = trim($_POST['logina']  ?? '');
	$clavea  = trim($_POST['clavea']  ?? '');
	$empresa = trim($_POST['empresa'] ?? '');

	// Determinar qué DB usar
	$dbName = DB_NAME;
	if ($empresa !== '') {
		try {
			$ppPdo = new PDO(
				'mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4',
				DB_USERNAME, DB_PASSWORD,
				[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
			);
			$ppStmt = $ppPdo->prepare('SELECT db_name FROM tenants WHERE slug = ? AND estado = "activo" AND deleted_at IS NULL LIMIT 1');
			$ppStmt->execute([$empresa]);
			$ppRow = $ppStmt->fetch();
			if (!$ppRow) { http_response_code(401); echo 'null'; break; }
			$dbName = $ppRow['db_name'];
		} catch (Exception $e) {
			echo 'null'; break;
		}
	}

	// Conectar a la DB del tenant y verificar credenciales
	$tenantConn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, $dbName);
	if ($tenantConn->connect_errno) { echo 'null'; break; }
	$loginEsc = $tenantConn->real_escape_string($logina);
	$rspta = $tenantConn->query("SELECT idusuario,nombre,tipo_documento,num_documento,telefono,email,cargo,imagen,login,clave FROM usuario WHERE login='$loginEsc' AND condicion='1'");
	$fetch  = $rspta->fetch_object();
	$authOk = false;
	if ($fetch) {
		$stored = (string) $fetch->clave;
		if ($stored !== '' && password_verify($clavea, $stored)) {
			$authOk = true;
			if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
				$nuevo = password_hash($clavea, PASSWORD_DEFAULT);
				$tenantConn->query("UPDATE usuario SET clave='" . $tenantConn->real_escape_string($nuevo) . "' WHERE idusuario='" . (int)$fetch->idusuario . "'");
			}
		} elseif ($stored !== '' && hash_equals($stored, $clavea)) {
			// Clave legacy en texto plano → aceptar y migrar a hash en el acto
			$authOk = true;
			$nuevo = password_hash($clavea, PASSWORD_DEFAULT);
			$tenantConn->query("UPDATE usuario SET clave='" . $tenantConn->real_escape_string($nuevo) . "' WHERE idusuario='" . (int)$fetch->idusuario . "'");
		}
	}
	if ($authOk) {
		unset($fetch->clave); // nunca exponer el hash al cliente
		session_regenerate_id(true); // nuevo ID de sesión: evita fijación de sesión
		$_SESSION['tenant_db']   = $dbName;
		$_SESSION['idusuario']   = $fetch->idusuario;
		$_SESSION['nombre']      = $fetch->nombre;
		$_SESSION['imagen']      = $fetch->imagen;
		$_SESSION['login']       = $fetch->login;

		$marcados = $tenantConn->query("SELECT idpermiso FROM usuario_permiso WHERE idusuario='{$fetch->idusuario}'");
		$valores  = [];
		while ($per = $marcados->fetch_object()) { $valores[] = $per->idpermiso; }

		in_array(1,  $valores) ? $_SESSION['escritorio']=1 : $_SESSION['escritorio']=0;
		in_array(2,  $valores) ? $_SESSION['reclamos']=1   : $_SESSION['reclamos']=0;
		in_array(3,  $valores) ? $_SESSION['consultas']=1  : $_SESSION['consultas']=0;
		in_array(4,  $valores) ? $_SESSION['ventas']=1     : $_SESSION['ventas']=0;
		in_array(5,  $valores) ? $_SESSION['seguridad']=1  : $_SESSION['seguridad']=0;
		in_array(6,  $valores) ? $_SESSION['mensajes']=1   : $_SESSION['mensajes']=0;
		in_array(8,  $valores) ? $_SESSION['bd']=1         : $_SESSION['bd']=0;
		in_array(9,  $valores) ? $_SESSION['vendedores']=1 : $_SESSION['vendedores']=0;
		in_array(10, $valores) ? $_SESSION['repartos']=1   : $_SESSION['repartos']=0;
		in_array(11, $valores) ? $_SESSION['configuracion']=1 : $_SESSION['configuracion']=0;
	}
	if (!$authOk) { http_response_code(401); } // señal para fail2ban (jail atiende-login): solo los fallos van 401
	echo $authOk ? json_encode($fetch) : 'null';
	break;
	case 'salir':
	   //limpiamos la variables de la secion
	session_unset();

	  //destruimos la sesion
	session_destroy();
		  //redireccionamos al login
	header("Location: ../index.php");
	break;

	


	
}
?>

