# Session Log: 12-05-2026 17:54 - mysql8-fixes-and-new-project

## Quick Reference (for AI scanning)
**Confidence keywords:** atiende, atiende, mysql8, slim4, twig, eloquent, php-di, docker, fastroute, group-by, web-master, tenant-mode, port-82, port-81, composer, atiende_net
**Projects:** whatsbus2021-main (legacy, port 81), atiende (new arch, port 82)
**Outcome:** Fixed all MySQL 8 GROUP BY ASC/DESC errors and WEB_MASTER API timeouts in legacy project; bootstrapped a complete new Slim 4 + Twig + Eloquent project at `C:\Users\ACER\Documents\Develop\atiende` with 70+ files; got it running on port 82 after fixing FastRoute static-vs-variable route conflicts.

## Decisions Made
- **Stack del nuevo proyecto:** Slim 4 (PSR-7) + PHP-DI 7 + Twig 3 + Eloquent 10 (illuminate/database) + Monolog + PhpSpreadsheet + Dompdf. PHP 8.2-fpm-alpine.
- **Puerto del nuevo proyecto: 82.** El legacy se queda con el 81 para no romper accesos existentes.
- **TenantService unificado:** reemplaza el patrón de cURL repetido en cada modelo del legacy. Lee `TENANT_MODE` env (`mix|b2b|b2c|api`) o cae al `WEB_MASTER` con timeout corto y fallback a `mix:true`.
- **Auth con bcrypt y auto-upgrade:** soporta passwords legacy en plaintext y los re-hashea al primer login exitoso. `session_regenerate_id(true)` en login.
- **Volumen Docker monta `.:/var/www/atiende`:** sobreescribe el `vendor/` del build, así que `composer install` debe ejecutarse en el host (o vía container temporal) para que el directorio local lo tenga.
- **`.env` con `TENANT_MODE=mix`** por defecto en development para no depender del API externo `api.atiende.com`.
- **Rutas estáticas antes que variables en Slim/FastRoute:** `/api/clientes/export` debe definirse antes que `/api/clientes/{id}` para evitar el error "Static route is shadowed by previously defined variable route".

## Key Learnings
- **MySQL 8 removió `GROUP BY ... ASC/DESC`:** sintaxis válida en MariaDB pero fatal en MySQL 8. Hay que separar en `GROUP BY col` + `ORDER BY col ASC/DESC`.
- **`fetch_object()` on bool:** ocurre cuando `mysqli_query()` devuelve `false` por SQL inválido. La cadena de fallos típica fue: WEB_MASTER inalcanzable → `responseWebMaster=null` → `$sql=null` → `ejecutarConsulta(false)` → fatal en `fetch_object()`.
- **FastRoute (motor de Slim 4) rechaza** rutas estáticas declaradas después de variables que las matchearían. No es como Express.js; el orden de definición sí importa para detectar el conflicto.
- **`docker compose` warning sobre `version: "3.9"`** en docker-compose.yml — atributo obsoleto en Compose v2, se puede remover sin consecuencia.
- **Volume mount overrides container files:** `volumes: - .:/var/www/atiende` esconde el `vendor/` que el Dockerfile generó. Para dev hay que tener `vendor/` en el host.
- **PHP 7.2.5 en host (Windows):** demasiado viejo para Slim 4 (requiere ≥8.1). Solución: `docker run --rm -v "${PWD}:/app" -w /app composer:2 install --no-interaction --ignore-platform-reqs`.

## Solutions & Fixes

### Legacy (whatsbus2021-main)

**1. MySQL 8 `GROUP BY ASC/DESC` fix** — 7 archivos:
```sql
-- Antes (MariaDB ok, MySQL 8 fatal):
GROUP BY DATE_FORMAT(fecha, '%d') ASC
-- Después:
GROUP BY DATE_FORMAT(fecha, '%d') ORDER BY DATE_FORMAT(fecha, '%d') ASC
```
Aplicado en: `modelos/Consultas.php`, `modelos/Venta.php`, `modelos/Reparto.php`, `ajax/mapa.php`, `pedidos/reporteAjax.php`, `pedidos/indexbis.php`, `pedidos/index.php`.

**2. WEB_MASTER API centralizado** en `config/Conexion.php`:
```php
function getWebMasterConfig() {
    static $config = null;
    if ($config !== null) return $config;
    // cURL con CONNECTTIMEOUT 3, TIMEOUT 5
    // fallback a ['data' => ['mix' => true, 'b2b' => false, 'b2c' => false]]
}
```
Reemplazó 9-line cURL block con timeout 1000s en 12 archivos.

**3. `startup.ps1` legacy:** agregó `SET GLOBAL sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,...'` (sin `ONLY_FULL_GROUP_BY`) tras seed, persistente entre reinicios de mysql8.

### New project (atiende)

**4. routes.php — orden de rutas:** mover todas las estáticas antes de `/{id}`:
```php
// BIEN:
$g->post('/api/clientes/import', [ClienteController::class, 'import']);
$g->get('/api/clientes/export',  [ClienteController::class, 'exportExcel']);
$g->get('/api/clientes/{id}',    [ClienteController::class, 'show']);   // después
```

**5. Composer install vía Docker temporal:**
```powershell
docker run --rm -v "${PWD}:/app" -w /app composer:2 install --no-interaction --ignore-platform-reqs
```

**6. Conflicto de puerto 81:** legacy usaba 81 → nuevo proyecto pasa a puerto 82 vía `APP_PORT=82` en `.env` y `${APP_PORT:-82}` en docker-compose.

## Files Modified

### Legacy project (`C:\Users\ACER\Downloads\whatsbus2021-main\whatsbus2021-main\`)
- `modelos/Consultas.php` — 4 GROUP BY DESC + 2 GROUP BY ASC fix; constructor cURL → `getWebMasterConfig()`
- `modelos/Venta.php` — GROUP BY DESC fix; constructor cURL refactor
- `modelos/Reparto.php` — GROUP BY DESC fix; constructor cURL refactor
- `modelos/Reclamo.php` — constructor cURL refactor
- `modelos/Persona.php` — constructor cURL refactor
- `modelos/Consulta.php` — constructor cURL refactor
- `ajax/mapa.php` — GROUP BY desc fix
- `pedidos/reporteAjax.php` — GROUP BY ASC fix
- `pedidos/indexbis.php` — GROUP BY ASC fix
- `pedidos/index.php` — GROUP BY ASC fix; cURL refactor inline
- `config/Conexion.php` — agregada función `getWebMasterConfig()` con caché estática y fallback
- `ws/post.php`, `ws/npost.php`, `ws/m/finaliza.php`, `ws/m/finalizac.php`, `pedidos/finaliza.php`, `pedidos/S_Pedidos_bis.php` — cURL block reemplazado
- `startup.ps1` — sql_mode persistente

### New project (`C:\Users\ACER\Documents\Develop\atiende\`)
**70+ archivos creados, estructura:**
- `composer.json` — slim/slim ^4.12, php-di ^7.0, twig ^3.8, illuminate/database ^10.0, monolog ^3.5, phpoffice/phpspreadsheet ^2.1, dompdf, vlucas/phpdotenv
- `public/index.php` — front controller (DI bootstrap, Eloquent, Slim, error middleware)
- `config/settings.php`, `config/container.php`, `config/routes.php`
- `app/Controllers/` — 18 controllers (Auth, Dashboard, Venta, Reclamo, Consulta, Cliente, Articulo, Area, Motivo, Vendedor, Repartidor, Reparto, Solicitud, Usuario, Mapa, Analytics, Base)
- `app/Models/` — 17 modelos Eloquent (Usuario, Cliente, Pedido, Reclamo, Articulo, Area, Motivo, Vendedor, Repartidor, Solicitud, Consulta, MsjReclamo, MsjConsulta, AreaConsulta, MotivoConsulta, UsuarioPermiso)
- `app/Services/` — TenantService, AuthService, UploadService, ExportService
- `app/Middleware/` — AuthMiddleware, ErrorHandler
- `app/Views/` — 17 Twig templates (layout/base, layout/sidebar, auth/login, error.html.twig, y vistas index para cada entidad incluyendo `reparto/index.html.twig` creada en esta sesión)
- `Dockerfile` — PHP 8.2-fpm-alpine + composer 2 + extensiones (pdo_mysql, mysqli, gd, intl, mbstring, zip, opcache)
- `docker-compose.yml` — atiende_app + atiende_nginx en `atiende_net` (external) — versión "3.9" eliminada en esta sesión
- `_docker/nginx/default.conf`
- `.env`, `.env.example`, `.gitignore`, `startup.ps1`

### Modificados específicamente en esta sesión:
- `app/Views/reparto/index.html.twig` — **CREADO** (faltaba el directorio)
- `config/routes.php` — **REORDENADO** rutas estáticas antes de variables (clientes/export, ventas/export, reclamos/export, clientes/import)
- `.env` — `APP_URL=http://localhost:82` y `APP_PORT=82`
- `docker-compose.yml` — puerto cambiado a 82 (default), `version: "3.9"` removido

## Setup & Config

### Containers actualmente
```
atiende_nginx     0.0.0.0:82->80/tcp     (nuevo proyecto, RUNNING)
atiende_app       9000/tcp                (php-fpm, RUNNING)
mysql8                                         (RUNNING, externo, network atiende_net)
demo_atiende_*                            (legacy, STOPPED — port 81 libre)
```

### URLs
- **http://localhost:82** — nuevo proyecto (Slim 4)
- **http://localhost:81** — legacy (cuando se inicia con su `startup.ps1`)

### Credenciales DB
- Host: `mysql8` (network `atiende_net`)
- User/pass: `root/root`
- DB: `atiende` (compartida entre ambos proyectos)

### Comandos clave
```powershell
# Levantar nuevo proyecto (sin rebuild):
cd C:\Users\ACER\Documents\Develop\atiende
docker compose up -d

# Reinstalar dependencies (host PHP es 7.2, demasiado viejo):
docker run --rm -v "${PWD}:/app" -w /app composer:2 install --no-interaction --ignore-platform-reqs

# Ver errores PHP en el container:
docker exec atiende_app php /var/www/atiende/public/index.php

# Test rápido HTTP:
$ProgressPreference='SilentlyContinue'; (Invoke-WebRequest http://localhost:82/login -UseBasicParsing).StatusCode
```

### Variables de entorno relevantes
- `TENANT_MODE=mix` — default en dev (no consulta API externo)
- `WEB_MASTER_URL=https://api.atiende.com` — API externo (timeout corto + fallback)
- `DB_HOST=mysql8`, `DB_DATABASE=atiende`

## Pending Tasks

1. **Probar login end-to-end** en http://localhost:82/login — verificar que el AuthService valida contra la tabla `axbot` (legacy) o `usuarios` (nueva).
2. **Verificar conexión Eloquent → mysql8** desde el container `atiende_app` (probar un endpoint `/api/clientes`).
3. **Completar stub views:** `consultas/`, `areas/`, `motivos/`, `vendedores/`, `repartidores/`, `solicitudes/`, `mapa/` — la mayoría son índices DataTables genéricos creados con estructura básica, pueden necesitar campos específicos.
4. **Copiar/symlinkear assets estáticos** del legacy (`public/css`, `public/img`, librerías como mapboxgl) al `public/` del nuevo proyecto.
5. **Crear `CLAUDE.md`** en `C:\Users\ACER\Documents\Develop\atiende\` documentando la nueva arquitectura.
6. **Considerar `.claude/settings.json`** con `bypassPermissions` para el nuevo directorio (o agregarlo a additionalDirectories).
7. **Migración de datos legacy → nuevo schema** si los modelos Eloquent esperan otros nombres de tablas/columnas.
8. **Tests** — ya hay `phpunit/phpunit ^10.5` en `require-dev` pero no hay tests escritos.

## Errors & Workarounds

| Error | Causa | Fix |
|---|---|---|
| `fetch_object() on bool at escritorio.php:287` | MySQL 8 rechaza `GROUP BY ... ASC` | Separar en `GROUP BY col` + `ORDER BY col ASC` |
| `DataTables warning: Invalid JSON response` | WEB_MASTER API devuelve HTML, no JSON → modelos rotos | Centralizar en `getWebMasterConfig()` con timeout 5s y fallback `mix:true` |
| `port 81 already allocated` | `demo_atiende_nginx` legacy ya usa 81 | Nuevo proyecto pasa a 82 |
| `Static route "/api/clientes/export" is shadowed by previously defined variable route "/api/clientes/([^/]+)"` | FastRoute exige orden static-before-variable | Reordenar `routes.php` |
| `composer install` falla en host | PHP 7.2.5 en host, requiere ≥8.1 | `docker run --rm -v "${PWD}:/app" -w /app composer:2 install --ignore-platform-reqs` |
| Volume mount esconde vendor del build | `volumes: - .:/var/www/atiende` | Asegurar que `vendor/` esté en host (paso previo) |
| `time="...": the attribute version is obsolete` | docker-compose v2 no usa `version:` | Removido del docker-compose.yml |

## Key Exchanges
- Usuario reporta `fetch_object() on bool` → diagnóstico apuntó a MySQL 8 vs MariaDB diff en GROUP BY
- Usuario reporta `Invalid JSON response` en DataTables → traza llegó al WEB_MASTER inalcanzable y constructores que dejaban modelos en estado roto
- Usuario pide análisis arquitectónico → propuesta de Slim 4 + Eloquent + Twig
- Usuario: "hazlo todo en un directorio a C:\Users\ACER\Documents\Develop\atiende dale todos los accesos" → bootstrap completo del nuevo proyecto
- Usuario: "dejalo en 81" → revertir cambio de puerto, detener legacy
- Usuario: "espera para el nuevo proyecto debe usar en php el puerdo 82" → mover nuevo a 82 (legacy en 81 cuando se inicie)

## Custom Notes
None

---

## Quick Resume Context
El nuevo proyecto en `C:\Users\ACER\Documents\Develop\atiende` (Slim 4 + Twig + Eloquent + PHP 8.2) está corriendo en `http://localhost:82` con containers `atiende_app` + `atiende_nginx` conectados a la `atiende_net` para alcanzar `mysql8`. El legacy `whatsbus2021-main` quedó con todos los fixes de MySQL 8 y centralizado el `getWebMasterConfig()`, y se levanta en puerto 81 con su propio `startup.ps1`. Falta probar el login del nuevo proyecto end-to-end y completar las vistas stub (consultas/areas/motivos/vendedores/repartidores/solicitudes/mapa).

---

## Raw Session Log

> Nota: Esta sesión es continuación de una anterior comprimida. El raw log completo está en:
> `C:\Users\ACER\.claude\projects\C--Users-ACER-Downloads-whatsbus2021-main-whatsbus2021-main\1c48aa37-5a67-4ce5-b134-1723a2787420.jsonl`

### Mensajes del usuario en esta sesión
1. (continuación auto del summary previo)
2. "dejalo en 81" — revertir el cambio de puerto, detener legacy primero
3. "que hacias?" — explicar contexto
4. "sigue si perdonde" — continuar testing
5. "espera para el nuevo proyecto debe usar en php el puerdo 82"
6. "luego seguimos" — pausar
7. `/compress` — esta acción

### Acciones del asistente en esta sesión
- Detectó que faltaba `app/Views/reparto/` y creó la vista
- Ejecutó `composer install` vía Docker temporal (host PHP es 7.2)
- Construyó imagen `atiende-app` y levantó containers
- Identificó conflicto de puerto 81 (legacy ocupado)
- Detuvo `demo_atiende_nginx` y `demo_atiende_app`
- Levantó nuevo proyecto en 81 → falló con error 500
- Diagnóstico: FastRoute rechaza static después de variable
- Reescribió `config/routes.php` con orden correcto
- Confirmado login page responde 200 en /login
- Cambió puerto a 82 según pedido del usuario, recreó nginx
- Removió `version: "3.9"` obsoleto del compose
