<?php
/**
 * Resuelve el tenant desde el SUBDOMINIO (HTTP_X_TENANT, que setea nginx en el fastcgi_param)
 * y apunta la conexión a la DB de ese tenant: setea $_SESSION['tenant_db'] (lo lee Conexion.php)
 * y Connection::setDatabase() (lo usa Connection::runQuery).
 *
 * Necesario para páginas SIN login abiertas desde un link de WhatsApp (ej. la vista del
 * supervisor /responder/...): sin sesión logueada caían a la base `atiende` y no encontraban
 * el reclamo/consulta del tenant. Mismo criterio que el login (ajax/usuario.php) y webhook.php.
 *
 * Requiere que database.php (DB_HOST/DB_USERNAME/DB_PASSWORD) ya esté cargado. Si no hay
 * subdominio (HTTP_X_TENANT vacío) no hace nada → conserva el comportamiento previo (sesión/DB_NAME).
 */
if (!function_exists('resolverTenantPorSubdominio')) {
    function resolverTenantPorSubdominio()
    {
        $slug = $_SERVER['HTTP_X_TENANT'] ?? '';
        if ($slug === '') {
            return;
        }
        try {
            $ppPdo = new PDO(
                'mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4',
                DB_USERNAME, DB_PASSWORD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $stmt = $ppPdo->prepare('SELECT db_name FROM tenants WHERE slug = ? AND estado = "activo" AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            if ($row && !empty($row['db_name'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['tenant_db'] = $row['db_name'];
                if (class_exists('Connection')) {
                    Connection::setDatabase($row['db_name']);
                }
            }
        } catch (Exception $e) {
            error_log('[tenant_subdominio] ' . $e->getMessage());
        }
    }
}
