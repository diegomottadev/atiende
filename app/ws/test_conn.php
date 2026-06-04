<?php
define('__ROOT__', dirname(__DIR__));
define('DB_HOST',     'mysql8');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME',     'atiende');
require __ROOT__ . '/config/Connection.php';

$pushname = "Test User";
$user     = "5493764278402";
$menu     = "0";
$espera   = "1";

$sql = "INSERT INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`)
        VALUES ('{$user}','{$pushname}','{$user}','{$menu}','{$espera}', now())
        ON DUPLICATE KEY UPDATE nombre='{$pushname}' ,menu='{$menu}', esperaRespuesta='{$espera}',fechaHora=now()";

$r = Connection::runQuery($sql);
echo $r ? "OK\n" : "FAIL\n";
