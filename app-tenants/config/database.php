<?php
require_once __DIR__ . '/bootstrap.php';

function getPlatformPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']);
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function getProvisionerPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // No dbname: provisioner needs to CREATE/DROP tenant databases
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4',
            $_ENV['DB_HOST'], $_ENV['DB_PORT']);
        $pdo = new PDO($dsn, $_ENV['DB_PROVISIONER_USER'], $_ENV['DB_PROVISIONER_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
