<?php
// Helper de conexión para las migraciones. Resuelve credenciales con prioridad:
//   1) config/migrations.php  → PROD: usuario migrador dedicado (DDL, native_password)
//   2) config/database.php    → DEV : root/root con privilegios plenos
// Así la MISMA migración corre en dev y en prod sin credenciales hardcodeadas.
// (Antes cada migración tenía 'root'/'root' fijo → no conectaba en prod porque
//  root usa caching_sha2 y el mysqli de PHP 7.3 no lo negocia.)
function mig_connect($db) {
    static $host = null, $user = null, $pass = null;
    if ($host === null) {
        $cfg = __DIR__ . '/../../config';
        if (is_file($cfg . '/migrations.php')) {
            require $cfg . '/migrations.php';
            $host = MIG_HOST; $user = MIG_USER; $pass = MIG_PASS;
        } else {
            require $cfg . '/database.php';
            $host = DB_HOST; $user = DB_USERNAME; $pass = DB_PASSWORD;
        }
    }
    $m = new mysqli($host, $user, $pass, $db);
    if ($m->connect_errno) {
        fwrite(STDERR, "Conexión a '$db' falló: {$m->connect_error}\n");
        exit(1);
    }
    $m->set_charset('utf8mb4');
    return $m;
}
