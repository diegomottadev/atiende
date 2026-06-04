@echo off
:: ============================================================
::  ATIENDE / ATIENDE  -  Instalador MySQL para Windows
:: ============================================================

set /p MYSQL_HOST=Host MySQL [default: localhost]:
if "%MYSQL_HOST%"=="" set MYSQL_HOST=localhost

set /p MYSQL_PORT=Puerto MySQL [default: 3306]:
if "%MYSQL_PORT%"=="" set MYSQL_PORT=3306

set /p MYSQL_ROOT=Usuario root [default: root]:
if "%MYSQL_ROOT%"=="" set MYSQL_ROOT=root

set /p MYSQL_PWD=Password root:

echo.
echo [1/3] Creando bases de datos y usuario...
mysql -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_ROOT% -p%MYSQL_PWD% < sql\setup_databases.sql
if %errorlevel% neq 0 (
    echo ERROR: No se pudo conectar a MySQL. Verificar host, puerto y credenciales.
    pause
    exit /b 1
)

echo [2/3] Importando base de datos principal (atiende)...
mysql -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_ROOT% -p%MYSQL_PWD% atiende < _docker\mariadb\atiende.sql
if %errorlevel% neq 0 (
    echo ERROR al importar atiende.sql
    pause
    exit /b 1
)

echo [3/3] Importando tabla clientes adicional...
mysql -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_ROOT% -p%MYSQL_PWD% atiende < sql\clientes.sql
if %errorlevel% neq 0 (
    echo ADVERTENCIA: No se pudo importar clientes.sql (puede que ya exista la tabla)
)

echo.
echo ============================================================
echo  Instalacion completada exitosamente!
echo.
echo  Bases de datos creadas:
echo    - atiende  (tablas principales, pedidos, clientes...)
echo.
echo  Usuario de aplicacion: atiende
echo  Password:              Atiende#*2022
echo.
echo  Configurar en config/global.docker.dev:
echo    DB_HOST=%MYSQL_HOST%
echo    DB_NAME=atiende
echo    DB_USERNAME=atiende
echo    DB_PASSWORD=Atiende#*2022
echo ============================================================
pause
