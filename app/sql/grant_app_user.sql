-- ============================================================
--  Usuario MySQL de aplicación (NO root) para Atiende.
--  Hoy la app corre como root/root (config/database.php), lo que convierte
--  cualquier SQLi en compromiso cross-tenant total. Este usuario acota el daño.
--
--  PASOS:
--   1) Editá la contraseña de abajo (fuerte, no la guardes en el repo).
--   2) Ejecutá:  mysql -uroot -p < grant_app_user.sql
--   3) Poné las credenciales en variables de entorno (no en el código):
--        DB_USERNAME=atiende_app
--        DB_PASSWORD=<la-que-pusiste>
--   4) Cambiá config/database.php para leerlas del entorno:
--        define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
--        define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
--   5) Reiniciá el contenedor app y verificá login + alta de pedido.
-- ============================================================

CREATE USER IF NOT EXISTS 'atiende_app'@'%' IDENTIFIED BY 'CAMBIAR_POR_PASSWORD_FUERTE';

-- DBs de tenants (atiende_<slug>): operación normal de la app.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES
    ON `atiende\_%`.* TO 'atiende_app'@'%';

-- Catálogo de la plataforma: la app solo necesita LEER para resolver el tenant.
GRANT SELECT ON `pedidos_platform`.* TO 'atiende_app'@'%';

FLUSH PRIVILEGES;

-- Para revertir:
--   DROP USER 'atiende_app'@'%';
