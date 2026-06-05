<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['idusuario'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Protección CSRF: lazy-init del token per-sesión. Generarlo acá (y no en el
// login) cubre las sesiones ya activas durante el deploy sin forzar re-login.
require_once __DIR__ . '/csrf.php';
ensureCsrfToken();
