-- ============================================================
--  Crea las bases de datos y permisos en el primer arranque.
--  Los datos se importan luego desde startup.sh
-- ============================================================

SET NAMES utf8;

CREATE DATABASE IF NOT EXISTS `atiende`
  DEFAULT CHARACTER SET utf8
  DEFAULT COLLATE utf8_spanish_ci;

-- Permitir conexion de root desde cualquier host (necesario para docker exec)
GRANT ALL PRIVILEGES ON `atiende`.* TO 'root'@'%';
FLUSH PRIVILEGES;
