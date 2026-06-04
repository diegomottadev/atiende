<?php
require_once __DIR__ . '/database.php';

// Infraestructura — igual para todos los tenants
define("__TENANT_DOMAIN__", "atiende.localhost:81");
define("__TENANT_SCHEME__", "http");   // dev: http · prod: https (se setea por entorno en global.docker.*)
define("TENANT_MODE",       "b2b");  // default; atiende_* tenants override via Conexion.php

// WhatsApp — config global (no son del tenant)
define('WA_VERIFY_TOKEN', 'dev_verify_token_local');
define('WA_API_VERSION',  'v25.0');
define('WA_DEBUG',        true);   // dev: vuelca payload a ws/json_.txt. En prod debe ser false.

// Clave AES-256-GCM usada por pedidos-platform para cifrar tokens WA
define('PLATFORM_ENCRYPTION_KEY', '0000000000000000000000000000000000000000000000000000000000000000');

// WA_PHONE_NUMBER_ID, WA_ACCESS_TOKEN, WA_APP_SECRET se definen en Conexion.php
// una vez que se conoce el tenant (session). No van hardcodeados aquí.

if (!function_exists('tenantScheme')) {
    function tenantScheme() {
        // Explícito vía constante; si no está, se infiere del dominio (localhost → http, resto → https)
        if (defined('__TENANT_SCHEME__') && __TENANT_SCHEME__ !== '') return __TENANT_SCHEME__;
        $dom = defined('__TENANT_DOMAIN__') ? __TENANT_DOMAIN__ : '';
        return (strpos($dom, 'localhost') !== false || strpos($dom, '127.0.0.1') !== false) ? 'http' : 'https';
    }
}

if (!function_exists('tenantUrl')) {
    function tenantUrl($slug = null, $path = '') {
        if ($slug === null || $slug === '') {
            // Priorizar el tenant de la sesión (atiende_corp → corp); fallback a DB_NAME
            if (session_status() === PHP_SESSION_NONE) { @session_start(); }
            $db = !empty($_SESSION['tenant_db']) ? $_SESSION['tenant_db'] : (defined('DB_NAME') ? DB_NAME : '');
            $slug = strncmp($db, 'atiende_', 8) === 0 ? substr($db, 8) : $db;
        }
        $url = tenantScheme() . '://' . $slug . '.' . __TENANT_DOMAIN__;
        if ($path !== '') $url .= '/' . ltrim($path, '/');
        return $url;
    }
}

if (!function_exists('getWebMasterConfig')) {
    function getWebMasterConfig() {
        static $config = null;
        if ($config !== null) return $config;

        // 1. Admin flow: Conexion.php sets $GLOBALS['_tenant_mode'] per atiende_* tenant
        if (isset($GLOBALS['_tenant_mode'])) {
            $mode = $GLOBALS['_tenant_mode'];
        } else {
            // 2. Pedidos flow (Connection.php): resolve DB from Connection::getDatabase()
            $dbName = (class_exists('Connection') && method_exists('Connection', 'getDatabase'))
                ? Connection::getDatabase()
                : (defined('DB_NAME') ? DB_NAME : 'atiende');
            if (strncmp($dbName, 'atiende_', 8) === 0) {
                $slug = substr($dbName, 8);
                $mode = 'b2b';
                try {
                    $pdo  = new PDO('mysql:host='.DB_HOST.';dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $stmt = $pdo->prepare('SELECT tenant_mode FROM tenants WHERE slug=? AND deleted_at IS NULL LIMIT 1');
                    $stmt->execute([$slug]);
                    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['tenant_mode'])) $mode = $row['tenant_mode'];
                } catch (Exception $e) {}
            } else {
                // 3. Legacy DB or no tenant context: use global constant
                $mode = defined('TENANT_MODE') ? TENANT_MODE : 'b2b';
            }
        }

        $config = ['data' => [
            'mix' => $mode === 'mix',
            'b2b' => $mode === 'mix' || $mode === 'b2b',
            'b2c' => $mode === 'mix' || $mode === 'b2c',
        ]];

        // telefono del tenant desde bot_config (solo DBs atiende_*)
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        $dbForPhone = !empty($_SESSION['tenant_db'])
            ? $_SESSION['tenant_db']
            : ((class_exists('Connection') && method_exists('Connection', 'getDatabase'))
                ? Connection::getDatabase()
                : (defined('DB_NAME') ? DB_NAME : 'atiende'));
        if (strncmp($dbForPhone, 'atiende_', 8) === 0) {
            try {
                $link = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, $dbForPhone);
                if ($link) {
                    $r = mysqli_query($link, 'SELECT telefono FROM bot_config LIMIT 1');
                    if ($r && ($row = mysqli_fetch_assoc($r)) && !empty($row['telefono'])) {
                        $config['data']['empresa']  = ['telefono' => $row['telefono']];
                        $config['data']['telefono'] = $row['telefono'];
                        $config['empresa']          = ['telefono' => $row['telefono']];
                    }
                    mysqli_close($link);
                }
            } catch (Exception $e) {}
        }

        return $config;
    }
}
