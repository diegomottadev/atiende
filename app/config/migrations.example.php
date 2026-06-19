<?php
// Template del config del USUARIO MIGRADOR (DDL) que usa el runner de migraciones.
// Copiar a `migrations.php` (git-ignored) y completar con las credenciales reales.
//
// Por qué un usuario aparte del runtime (config/database.php): menor privilegio.
// El migrador necesita ALTER/CREATE/DROP/INDEX (DDL); el usuario de la app no.
// En PROD debe usar mysql_native_password (root usa caching_sha2 y el mysqli de
// PHP 7.3 no lo negocia). En DEV podés omitir este archivo: _lib.php cae a
// config/database.php (root/root) automáticamente.
//
// Crear el usuario (ejemplo):
//   CREATE USER 'atiende_migrator'@'%' IDENTIFIED WITH mysql_native_password BY '<pass>';
//   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER
//     ON `atiende\_%`.* TO 'atiende_migrator'@'%';
define('MIG_HOST', 'mysql8');
define('MIG_USER', 'atiende_migrator');
define('MIG_PASS', 'CHANGE_ME');
