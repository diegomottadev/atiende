-- ============================================================
--  ATIENDE / ATIENDE  -  MySQL Setup Script
--  Ejecutar ANTES de importar atiende.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Crear bases de datos
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `atiende`
  CHARACTER SET utf8
  COLLATE utf8_spanish_ci;

-- ------------------------------------------------------------
-- Crear usuario de aplicacion (ajustar password segun entorno)
-- ------------------------------------------------------------
-- Desarrollo:
CREATE USER IF NOT EXISTS 'atiende'@'localhost' IDENTIFIED BY 'Atiende#*2022';
GRANT ALL PRIVILEGES ON `atiende`.* TO 'atiende'@'localhost';

-- Acceso desde cualquier host (Docker / red local):
CREATE USER IF NOT EXISTS 'atiende'@'%' IDENTIFIED BY 'Atiende#*2022';
GRANT ALL PRIVILEGES ON `atiende`.* TO 'atiende'@'%';

FLUSH PRIVILEGES;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SIGUIENTE PASO:
--   mysql -u root -p atiende < _docker/mariadb/atiende.sql
--   mysql -u root -p atiende  < sql/clientes.sql
-- (O usar install.bat / install.sh que hacen todo automatico)
-- ============================================================
