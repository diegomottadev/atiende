#!/bin/bash
set -e

DB_CONTAINER="demo_atiende_mysql"
APP_CONTAINER="demo_atiende_app"
DB_USER="root"
DB_PASS="root"

echo "============================================"
echo "  ATIENDE / ATIENDE  -  Dev Setup"
echo "============================================"

# ---- 1. Config: copiar archivos de config si no existen ----
if [ ! -f config/global.php ]; then
    echo "[1/7] Copiando config/global.docker.dev -> config/global.php"
    cp config/global.docker.dev config/global.php
else
    echo "[1/7] config/global.php ya existe, omitiendo copia"
fi
if [ ! -f config/database.php ]; then
    echo "[1/7] Copiando config/database.docker.dev -> config/database.php"
    cp config/database.docker.dev config/database.php
else
    echo "[1/7] config/database.php ya existe, omitiendo copia"
fi

# ---- 1b. Instalar dependencias PHP ----
echo "[1b/7] Instalando dependencias Composer..."
command -v composer >/dev/null 2>&1 || { echo "ERROR: composer no encontrado. Instalarlo desde https://getcomposer.org o usar: docker run --rm -v \$(pwd):/app composer:latest install"; exit 1; }
composer install --no-dev --optimize-autoloader

# ---- 2. Crear archivos/directorios requeridos ----
echo "[2/7] Creando archivos y directorios necesarios..."
mkdir -p ajax/excel files csv
touch ws/json_.txt
touch ajax/excel/.gitkeep
touch files/.gitkeep

# ---- 3. Bajar containers y limpiar imagen anterior ----
echo "[3/7] Deteniendo containers anteriores..."
docker compose down --remove-orphans
docker image rm $(docker compose images -q app 2>/dev/null) 2>/dev/null || true

# ---- 4. Build y levantar ----
echo "[4/7] Construyendo imagen y levantando containers..."
docker compose up -d --build

# ---- 5. Esperar a que MySQL este lista ----
echo "[5/7] Esperando que MySQL este lista..."
until docker exec "$DB_CONTAINER" mysqladmin ping -h localhost -u "$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; do
    printf "."
    sleep 3
done
echo " OK"

# Pausa extra: MySQL 8 a veces responde al ping pero aun no proceso init.sql
sleep 3

# ---- 6. Importar bases de datos ----
echo "[6/7] Importando bases de datos..."

# Resetear atiende
docker exec -i "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" -e \
    "DROP DATABASE IF EXISTS atiende; CREATE DATABASE atiende DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_spanish_ci;"

# Importar esquema y datos principales (db context pasado explicitamente)
docker exec -i "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" atiende \
    < _docker/mariadb/atiende.sql

# Configuracion global de MySQL
docker exec -i "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" -e \
    "SET GLOBAL time_zone = '-3:00'; SET GLOBAL lc_time_names = 'es_ES';"

# Truncar tablas operativas y sembrar datos de desarrollo
docker exec -i "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" atiende <<SQL
TRUNCATE TABLE areas;
TRUNCATE TABLE areas_consultas;
TRUNCATE TABLE pedidos;
TRUNCATE TABLE reclamos;
TRUNCATE TABLE consultas;
TRUNCATE TABLE link_pedidos;
TRUNCATE TABLE vendedores;
TRUNCATE TABLE telefonos;
TRUNCATE TABLE solicitudes;
TRUNCATE TABLE repartidores;
TRUNCATE TABLE msj_reclamos;
TRUNCATE TABLE msj_consultas;
TRUNCATE TABLE motivo_consultas;
TRUNCATE TABLE menuitem;
TRUNCATE TABLE fidelizar;
TRUNCATE TABLE contactos;
TRUNCATE TABLE contactosb2c;

INSERT INTO areas (id, area, telefono, activo) VALUES
  (1, 'Comercial',        '5491132980398', 1),
  (2, 'Logistica',        '5491132980398', 1),
  (3, 'Gerencia',         '5491132980398', 1),
  (4, 'Facturación',      '5491132980398', 1),
  (5, 'Producción',       '5491132980398', 1),
  (6, 'Recursos humanos', '5491132980398', 1),
  (7, 'Finanzas',         '5491132980398', 1);

INSERT INTO areas_consultas (id, area, telefono, activo) VALUES
  (1, 'Comercial',        '5491132980398', 1),
  (2, 'Logistica',        '5491132980398', 1),
  (3, 'Gerencia',         '5491132980398', 1),
  (4, 'Facturación',      '5491132980398', 1),
  (5, 'Producción',       '5491132980398', 1),
  (6, 'Recursos humanos', '5491132980398', 1),
  (7, 'Finanzas',         '5491132980398', 1),
  (8, 'CANCELACIONES',    '5491132980398', 1);

INSERT INTO motivo_consultas (id, opcionId, opcion, menuId, guardar, area) VALUES
  (1, 'A', '¿Realizan envios a domicilio?',  '15', 0, '1'),
  (2, 'B', '¿Tienen una lista de productos?', '15', 0, '1'),
  (3, 'C', '¿Que formas de pagos aceptan?',   '15', 0, '1');

INSERT INTO menuitem (id, opcionId, opcion, menuId, guardar, area) VALUES
  (1, 'A', '¿Tu pedido aun no ha llegado?',          '5', 0, '1'),
  (2, 'B', '¿Te llego un producto equivocado?',       '5', 0, '9'),
  (3, 'C', '¿Tu pedido llego con otro importate?',    '5', 0, '9'),
  (4, 'D', '¿Tienes alguna sugerencia?',              '5', 0, '6'),
  (5, 'E', 'Mi vendedor no me visita',                '5', 0, '1');
SQL

# ---- 7. Permisos ----
echo "[7/7] Configurando permisos..."
docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/atiende
docker exec "$APP_CONTAINER" chmod -R 775 /var/www/atiende/ajax/excel
docker exec "$APP_CONTAINER" chmod -R 775 /var/www/atiende/files
docker exec "$APP_CONTAINER" chmod -R 775 /var/www/atiende/csv
docker exec "$APP_CONTAINER" chmod 666     /var/www/atiende/ws/json_.txt
docker exec "$APP_CONTAINER" chmod 644     /var/www/atiende/ajax/subirarchivo.php

echo ""
echo "============================================"
echo "  Setup completado!"
echo "  App:   http://localhost:${APP_PORT:-81}"
echo "  MySQL: localhost:${DB_PORT:-3307}"
echo "         root / $DB_PASS"
echo "         DBs: atiende"
echo "============================================"
