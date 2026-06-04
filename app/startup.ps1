$ErrorActionPreference = "Continue"   # Stop explota con warnings de mysql en stderr

$MYSQL_CONTAINER = "mysql8"           # container MySQL existente
$APP_CONTAINER   = "demo_atiende_app"
$DB_USER         = "root"
$DB_PASS         = "root"

Write-Host "============================================"
Write-Host "  ATIENDE / ATIENDE  -  Dev Setup"
Write-Host "============================================"

# ---- 1. Copiar archivos de config si no existen ----
if (-not (Test-Path "config\global.php")) {
    Write-Host "[1/6] Copiando config\global.docker.dev -> config\global.php"
    Copy-Item "config\global.docker.dev" "config\global.php"
} else {
    Write-Host "[1/6] config\global.php ya existe, omitiendo copia"
}
if (-not (Test-Path "config\database.php")) {
    Write-Host "[1/6] Copiando config\database.docker.dev -> config\database.php"
    Copy-Item "config\database.docker.dev" "config\database.php"
} else {
    Write-Host "[1/6] config\database.php ya existe, omitiendo copia"
}

# ---- 2. Crear archivos y directorios necesarios ----
Write-Host "[2/6] Creando archivos y directorios necesarios..."
New-Item -ItemType Directory -Force "ajax\excel" | Out-Null
New-Item -ItemType Directory -Force "files"       | Out-Null
New-Item -ItemType Directory -Force "csv"         | Out-Null
if (-not (Test-Path "ws\json_.txt"))        { New-Item -ItemType File "ws\json_.txt"        | Out-Null }
if (-not (Test-Path "ajax\excel\.gitkeep")) { New-Item -ItemType File "ajax\excel\.gitkeep" | Out-Null }
if (-not (Test-Path "files\.gitkeep"))      { New-Item -ItemType File "files\.gitkeep"      | Out-Null }

# ---- 3. Bajar containers anteriores y levantar app + nginx ----
Write-Host "[3/6] Levantando containers app y nginx..."
docker compose down --remove-orphans
docker compose up -d --build

# ---- 4. Verificar que mysql8 este corriendo ----
Write-Host "[4/6] Verificando mysql8..." -NoNewline
$status = docker inspect --format="{{.State.Status}}" $MYSQL_CONTAINER 2>$null
if ($status -ne "running") {
    Write-Host ""
    Write-Error "El container '$MYSQL_CONTAINER' no esta corriendo. Inicialo primero."
    exit 1
}
Write-Host " OK (corriendo)"

# ---- 5. Importar bases de datos en mysql8 ----
Write-Host "[5/6] Importando bases de datos en $MYSQL_CONTAINER..."

docker exec $MYSQL_CONTAINER mysql -u $DB_USER -p"$DB_PASS" -e `
    "DROP DATABASE IF EXISTS atiende; CREATE DATABASE atiende DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_spanish_ci;" 2>&1 | Out-Null

Get-Content "_docker\mariadb\atiende.sql" -Raw | `
    docker exec -i $MYSQL_CONTAINER mysql -u $DB_USER -p"$DB_PASS" atiende 2>&1 | Out-Null

# Seed datos de desarrollo
$seedSql = @"
TRUNCATE TABLE areas; TRUNCATE TABLE areas_consultas;
TRUNCATE TABLE pedidos; TRUNCATE TABLE reclamos; TRUNCATE TABLE consultas;
TRUNCATE TABLE link_pedidos; TRUNCATE TABLE vendedores; TRUNCATE TABLE telefonos;
TRUNCATE TABLE solicitudes; TRUNCATE TABLE repartidores; TRUNCATE TABLE msj_reclamos;
TRUNCATE TABLE msj_consultas; TRUNCATE TABLE motivo_consultas; TRUNCATE TABLE menuitem;
TRUNCATE TABLE fidelizar; TRUNCATE TABLE contactos; TRUNCATE TABLE contactosb2c;
INSERT INTO areas (id, area, telefono, activo) VALUES
  (1,'Comercial','5491132980398',1),(2,'Logistica','5491132980398',1),
  (3,'Gerencia','5491132980398',1),(4,'Facturacion','5491132980398',1),
  (5,'Produccion','5491132980398',1),(6,'Recursos humanos','5491132980398',1),
  (7,'Finanzas','5491132980398',1);
INSERT INTO areas_consultas (id, area, telefono, activo) VALUES
  (1,'Comercial','5491132980398',1),(2,'Logistica','5491132980398',1),
  (3,'Gerencia','5491132980398',1),(4,'Facturacion','5491132980398',1),
  (5,'Produccion','5491132980398',1),(6,'Recursos humanos','5491132980398',1),
  (7,'Finanzas','5491132980398',1),(8,'CANCELACIONES','5491132980398',1);
INSERT INTO motivo_consultas (id,opcionId,opcion,menuId,guardar,area) VALUES
  (1,'A','Realizan envios a domicilio?','15',0,'1'),
  (2,'B','Tienen una lista de productos?','15',0,'1'),
  (3,'C','Que formas de pagos aceptan?','15',0,'1');
INSERT INTO menuitem (id,opcionId,opcion,menuId,guardar,area) VALUES
  (1,'A','Tu pedido aun no ha llegado?','5',0,'1'),
  (2,'B','Te llego un producto equivocado?','5',0,'9'),
  (3,'C','Tu pedido llego con otro importe?','5',0,'9'),
  (4,'D','Tienes alguna sugerencia?','5',0,'6'),
  (5,'E','Mi vendedor no me visita','5',0,'1');
"@
$seedSql | docker exec -i $MYSQL_CONTAINER mysql -u $DB_USER -p"$DB_PASS" atiende 2>&1 | Out-Null

# Desactivar ONLY_FULL_GROUP_BY para compatibilidad con queries heredadas
docker exec $MYSQL_CONTAINER mysql -u $DB_USER -p"$DB_PASS" -e `
    "SET GLOBAL sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';" 2>&1 | Out-Null

# ---- 6. Permisos en el container app ----
Write-Host "[6/6] Configurando permisos..."
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/atiende
docker exec $APP_CONTAINER chmod -R 775 /var/www/atiende/ajax/excel
docker exec $APP_CONTAINER chmod -R 775 /var/www/atiende/files
docker exec $APP_CONTAINER chmod -R 775 /var/www/atiende/csv
docker exec $APP_CONTAINER chmod 666    /var/www/atiende/ws/json_.txt

$appPort = if ($env:APP_PORT) { $env:APP_PORT } else { "81" }
Write-Host ""
Write-Host "============================================"
Write-Host "  Setup completado!"
Write-Host "  App:   http://localhost:$appPort"
Write-Host "  MySQL: mysql8 (ya corriendo en puerto 3306)"
Write-Host "============================================"
