<?php
namespace App;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
            self::logout();
        }
        $_SESSION['last_activity'] = time();
    }

    public static function attempt(string $email, string $password): bool
    {
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare(
            'SELECT id, password_hash, rol, tenant_id, activo FROM platform_users WHERE email = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !$user['activo'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['rol']       = $user['rol'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $pdo->prepare('UPDATE platform_users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        session_destroy();
    }

    public static function requireSuperadmin(): void
    {
        self::start();
        if (($_SESSION['rol'] ?? '') !== 'superadmin') {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void
    {
        self::start();
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }
    }
}
