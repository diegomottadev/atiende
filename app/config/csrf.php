<?php
/**
 * Protección CSRF para los handlers de ajax/.
 *
 * Mecanismo: token per-sesión (no per-request, para no romper DataTables ni
 * la concurrencia). El token vive en $_SESSION['csrf_token'] y lo genera
 * config/auth.php (lazy-init). El front lo adjunta como header X-CSRF-Token
 * en todo POST/PUT/DELETE/PATCH vía $.ajaxSetup (ver vistas/headerv1.php).
 *
 * Precondición: auth.php ya corrió y pobló $_SESSION['csrf_token'].
 */

if (!function_exists('ensureCsrfToken')) {
    /**
     * Garantiza que exista un token CSRF per-sesión y lo devuelve.
     *
     * Único punto de generación del token: lo llaman tanto config/auth.php
     * (ruta AJAX) como vistas/headerv1.php (render de vistas), de modo que el
     * token SIEMPRE esté disponible al emitir `globalCsrfToken` en el front.
     * Sin esto, una sesión recién logueada renderiza la vista sin token (el
     * login `op=verificar` no pasa por auth.php) y toda mutación devuelve 403.
     */
    function ensureCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('requireCsrf')) {
    /**
     * Exige un token CSRF válido. Si falta o no coincide → 403 JSON + exit.
     */
    function requireCsrf(): void
    {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        $real = $_SESSION['csrf_token'] ?? '';
        if ($real === '' || $sent === '' || !hash_equals($real, $sent)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'CSRF token inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

if (!function_exists('csrfGuard')) {
    /**
     * Exige token CSRF salvo que $op esté en la allowlist explícita de
     * operaciones de LECTURA del handler que llama.
     *
     * @param string   $op          operación recibida (típicamente $_GET['op'])
     * @param string[] $readOnlyOps lista de ops de solo-lectura del handler
     */
    function csrfGuard(string $op, array $readOnlyOps): void
    {
        if (!in_array($op, $readOnlyOps, true)) {
            requireCsrf();
        }
    }
}
