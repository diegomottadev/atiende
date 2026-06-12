<?php
require_once "global.php";
if (!function_exists('mysqli_init') && !extension_loaded('mysqli')) {
    echo 'We don\'t have mysqli!!!';
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$__dbName = !empty($_SESSION['tenant_db']) ? $_SESSION['tenant_db'] : DB_NAME;
$conexion = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, $__dbName);

if (mysqli_connect_errno()) {
    printf("Falló en la conexion con la base de datos: %s\n", mysqli_connect_error());
    exit();
}

mysqli_query($conexion, 'SET NAMES "'.DB_ENCODE.'"');
mysqli_query($conexion, "SET GLOBAL lc_time_names = 'es_ES'");
// Compatibilidad MySQL 8: las queries legacy hacen GROUP BY con columnas no
// agregadas (estilo MySQL 5.x). ONLY_FULL_GROUP_BY (default en MySQL 8.4) las
// rechaza con error 1055 → la query devuelve false y el JSON de los endpoints
// se rompe (no cargan pedidos/clientes). Lo desactivamos por conexión.
mysqli_query($conexion, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// Cargar credenciales WhatsApp del tenant desde pedidos_platform
// Se hace aquí porque ya conocemos el DB del tenant (session)
if (strncmp($__dbName, 'atiende_', 8) === 0) {
    $__slug = substr($__dbName, 8);
    try {
        $__ppPdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4',
            DB_USERNAME, DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $__ppStmt = $__ppPdo->prepare(
            'SELECT whatsapp_phone_id, whatsapp_token_enc, whatsapp_app_secret_enc, tenant_mode
             FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1'
        );
        $__ppStmt->execute([$__slug]);
        $__ppRow = $__ppStmt->fetch();
        if ($__ppRow) {
            $__key = hex2bin(PLATFORM_ENCRYPTION_KEY);
            $__dec = function($enc) use ($__key) {
                if (!$enc) return false;
                $d = base64_decode($enc);
                return openssl_decrypt(substr($d, 28), 'aes-256-gcm', $__key, OPENSSL_RAW_DATA, substr($d, 0, 12), substr($d, 12, 16));
            };
            if (!defined('WA_PHONE_NUMBER_ID') && $__ppRow['whatsapp_phone_id']) {
                define('WA_PHONE_NUMBER_ID', $__ppRow['whatsapp_phone_id']);
            }
            if (!defined('WA_ACCESS_TOKEN') && $__ppRow['whatsapp_token_enc']) {
                $__v = $__dec($__ppRow['whatsapp_token_enc']);
                if ($__v !== false) define('WA_ACCESS_TOKEN', $__v);
            }
            if (!defined('WA_APP_SECRET') && $__ppRow['whatsapp_app_secret_enc']) {
                $__v = $__dec($__ppRow['whatsapp_app_secret_enc']);
                if ($__v !== false) define('WA_APP_SECRET', $__v);
            }
        }
        if (!empty($__ppRow['tenant_mode'])) {
            $GLOBALS['_tenant_mode'] = $__ppRow['tenant_mode'];
        }
    } catch (PDOException $__e) { /* silent — sin credenciales WA el bot no responde pero la app funciona */ }
    unset($__slug, $__ppPdo, $__ppStmt, $__ppRow, $__key, $__dec, $__v, $__e);
}

unset($__dbName);


if (!function_exists('ejecutarConsulta')) {
    function ejecutarConsulta($sql){
        global $conexion;
        $query=$conexion->query($sql);
        return $query;
    }

    function ejecutarConsultaSimpleFila($sql){
        global $conexion;
        $query=$conexion->query($sql);
        // Una query fallida (p.ej. columna inexistente por drift de schema entre
        // tenants) devuelve false; sin este guard, false->fetch_assoc() era un
        // Fatal error. Devolvemos false y el caller decide (varios ya chequean
        // is_array()/empty() sobre el resultado).
        if ($query === false) { return false; }
        $row=$query->fetch_assoc();
        return $row;
    }

    function ejecutarConsulta_retornarID($sql){
        global $conexion;
        $query=$conexion->query($sql);
        return $conexion->insert_id;
    }

    function limpiarCadena($str){
        global $conexion;
        $str=mysqli_real_escape_string($conexion,trim($str));
        return htmlspecialchars($str);
    }
}
