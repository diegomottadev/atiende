<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT);
$dotenv->load();
$dotenv->required(['APP_URL', 'APP_ENV', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DB_PROVISIONER_USER', 'DB_PROVISIONER_PASS', 'PLATFORM_ENCRYPTION_KEY']);

date_default_timezone_set('UTC');

define('APP_URL', rtrim($_ENV['APP_URL'], '/'));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (APP_ENV !== 'development') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.gc_maxlifetime', 7200);

/**
 * Retorna el slug del tenant extraído del subdominio (seteado por Nginx).
 * Ej: corp.atiende.localhost → "corp"
 * Retorna null si no hay subdominio (acceso directo a atiende.localhost).
 */
function getTenantSlug(): ?string
{
    $tenant = $_SERVER['HTTP_X_TENANT'] ?? '';
    if ($tenant === '') {
        return null;
    }
    return preg_replace('/[^a-z0-9_]/', '', strtolower($tenant)) ?: null;
}
