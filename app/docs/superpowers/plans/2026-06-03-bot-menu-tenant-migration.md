# Migrar el menú del bot a la DB del tenant y eliminar `axbot` — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminar la base de datos global `axbot`, moviendo el menú del bot (JSON) y el `telefono` a una tabla `bot_config` dentro de la DB propia de cada tenant (`wb_<slug>`).

**Architecture:** El menú deja de leerse/escribirse en `axbot.empresa` (tabla global indexada por slug) y pasa a una tabla `bot_config` de fila única, exclusiva de las DBs `wb_*`. Todas las lecturas/escrituras usan la conexión al tenant ya existente. Tras migrar los datos, `axbot` se elimina por completo del código, los scripts de setup y la docs.

**Tech Stack:** PHP 8 + mysqli/PDO, MariaDB, Docker.

**Spec:** `docs/superpowers/specs/2026-06-03-bot-menu-tenant-migration-design.md`

---

## Convenciones de este plan (LEER ANTES DE EMPEZAR)

- **SIN git.** Este repo no es un repositorio git y la convención del proyecto es no correr comandos git durante la implementación. **No hay pasos de commit.** En su lugar, cada tarea cierra con un **Checkpoint** de verificación.
- **SIN PHPUnit.** Este repo no tiene suite de tests. La verificación de cada tarea es: (a) `php -l <archivo>` para validar sintaxis, y (b) queries SQL vía `docker exec` y/o chequeo manual del comportamiento. El container de la app se llama `demo_atiende_app`; el de MySQL es externo (`mysql8` en la red `atiende_net`). Credenciales dev: `root/root`.
- **Orden obligatorio.** Las tareas están en orden seguro: primero el storage + seed (Tarea 1), luego el código que lee/escribe `bot_config` (Tareas 2-5), luego la migración de datos (Tarea 6), y **recién al final** el teardown de `axbot` (Tareas 7-8). No adelantar el teardown.
- **DB de prueba para `wb_*`.** `startup.*` solo crea `atiende`; las DBs `wb_*` las crea la app separada `pedidos-platform`. Para verificar el camino `wb_*` en dev, creá una DB de prueba manualmente cuando la tarea lo indique.

---

## Estructura de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `_docker/mariadb/bot_config_seed.sql` | Crear | Artefacto: `CREATE TABLE bot_config` + `INSERT` del menú default |
| `migrate_axbot_to_bot_config.php` | Crear (temporal) | Script one-time: copia `axbot.empresa` → `wb_*.bot_config` |
| `ws/webhook.php` | Modificar (~282-318) | Leer menú desde `bot_config` del tenant (gate `wb_` + fallback intactos) |
| `ajax/configuracion.php` | Modificar (~94-163) | `getMenuPrincipal` / `saveMenuPrincipal` sobre `bot_config` |
| `config/global.php` | Modificar (~78-89) | `getWebMasterConfig`: `telefono` desde `bot_config` del tenant |
| `config/Connection.php` | Modificar (~57-75) | Eliminar `runQueryLogin` (sin llamadas vivas) |
| `startup.ps1`, `startup.sh`, `install.bat` | Modificar | Quitar creación/seed de `axbot` |
| `sql/setup_databases.sql` | Modificar | Quitar comentario de `axbot` |
| `_docker/mysql/init.sql` | Modificar (líneas 12, 18) | Quitar `CREATE DATABASE axbot` + `GRANT` |
| `_docker/mariadb/00_axbot_schema.sql` | Borrar | Schema axbot, ya sin uso |
| `fix_corp_menu.php` | Borrar | Su menú default se traslada al seed |
| `CLAUDE.md` | Modificar | Sección BotEngine Menu Structure + Entry Points |

---

## Tarea 1: Crear el artefacto seed `bot_config_seed.sql`

**Files:**
- Create: `_docker/mariadb/bot_config_seed.sql`
- Source de referencia: `fix_corp_menu.php` (contiene el array `$menu` default canónico)

- [ ] **Step 1: Generar el JSON del menú default desde `fix_corp_menu.php`**

Dentro del container, ejecutar un snippet que reusa el mismo array `$menu` de `fix_corp_menu.php` y lo emite como JSON de una sola línea:

```bash
docker exec demo_atiende_app php -r '
$nl="\n"; require "/var/www/atiende/fix_corp_menu_array.php";
echo json_encode($menu, JSON_UNESCAPED_UNICODE);
'
```

Si `fix_corp_menu.php` no expone el array por separado, copiar el bloque `$menu = [...]` (líneas 3-47 de `fix_corp_menu.php`) tal cual a un archivo temporal `fix_corp_menu_array.php` que solo defina `$menu`, generar el JSON, y luego borrar el temporal. El objetivo es obtener el string JSON exacto del menú default.

- [ ] **Step 2: Escribir `_docker/mariadb/bot_config_seed.sql`**

```sql
-- ============================================================
--  bot_config — menú del bot por tenant (una fila por DB wb_*)
--  Aplicar dentro de la DB del tenant: mysql ... wb_<slug> < bot_config_seed.sql
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `bot_config` (
  `id`         tinyint(1)   NOT NULL DEFAULT 1,
  `menu_json`  longtext     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono`   varchar(20)  NULL DEFAULT NULL,
  `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_single_row` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menú default (idempotente): inserta la fila 1 solo si no existe.
INSERT INTO `bot_config` (`id`, `menu_json`)
SELECT 1, '<<PEGAR_AQUI_EL_JSON_DEL_STEP_1>>'
WHERE NOT EXISTS (SELECT 1 FROM `bot_config` WHERE `id` = 1);
```

Reemplazar `<<PEGAR_AQUI_EL_JSON_DEL_STEP_1>>` por el JSON del Step 1, escapando comillas simples (`'` → `''`).

- [ ] **Step 3 (Checkpoint): Verificar el seed contra una DB de prueba**

```bash
docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS wb_test; CREATE DATABASE wb_test DEFAULT CHARACTER SET utf8mb4;"
docker exec -i mysql8 mysql -uroot -proot wb_test < _docker/mariadb/bot_config_seed.sql
docker exec mysql8 mysql -uroot -proot wb_test -e "SELECT id, JSON_VALID(menu_json) AS valido, JSON_LENGTH(menu_json) AS items FROM bot_config;"
```

Expected: una fila `id=1`, `valido=1`, `items` = cantidad de entradas del menú (≈18). Dejar `wb_test` para las tareas siguientes.

---

## Tarea 2: Webhook — leer el menú desde `bot_config`

**Files:**
- Modify: `ws/webhook.php` (bloque ~284-318)

- [ ] **Step 1: Reemplazar la lectura desde `axbot` por `bot_config` del tenant**

El bloque actual (dentro de `if (strncmp($tenantDbName, 'wb_', 3) === 0) {`) abre `mysqli_connect(..., 'axbot')` y hace `SELECT json FROM empresa WHERE empresa = '$slug'`. Reemplazar **solo** la parte de conexión a axbot + query por una lectura sobre `$conexion` (mysqli del tenant, ya disponible: `Conexion.php` se incluyó en línea 93 y `global $conexion` está en línea 138). Conservar la obtención de `$ppNombre` desde `pedidos_platform` y el armado de `$responseWebMaster`.

Nuevo bloque (reemplaza líneas ~299-317):

```php
        $menuRow = mysqli_query($conexion, "SELECT menu_json FROM bot_config LIMIT 1");
        if ($menuRow && ($axRow = mysqli_fetch_assoc($menuRow))) {
            $rawMenu = json_decode($axRow['menu_json'], true);
            $responseWebMaster = [
                'data' => [
                    'identificador'  => $slug,
                    'empresa_nombre' => $ppNombre,
                    'b2b' => true, 'b2c' => false, 'mix' => false,
                    'empresa' => ['json' => json_encode(['menu' => $rawMenu])]
                ]
            ];
            error_log('[webhook] menú cargado desde bot_config para slug=' . $slug . ' nombre=' . $ppNombre);
        }
```

**No tocar** el `if (strncmp($tenantDbName, 'wb_', 3) === 0)` (el gate se mantiene) ni el bloque de fallback `ws/dev_tenant_config*.json` (líneas ~320-330).

- [ ] **Step 2 (Checkpoint): Lint + prueba funcional**

```bash
docker exec demo_atiende_app php -l /var/www/atiende/ws/webhook.php
```
Expected: `No syntax errors detected`.

Prueba funcional (requiere `wb_test` con `bot_config` seeded de la Tarea 1, y un tenant `wb_test` en `pedidos_platform`): disparar el webhook simulado o revisar `error_log` buscando `menú cargado desde bot_config para slug=test`. Si no hay tenant `wb_test` provisionado, verificar al menos que el `SELECT menu_json FROM bot_config` corre sin error sobre `wb_test`.

---

## Tarea 3: `configuracion.php` — `getMenuPrincipal` desde `bot_config`

**Files:**
- Modify: `ajax/configuracion.php` (case `getMenuPrincipal`, ~94-128)

- [ ] **Step 1: Reemplazar la lectura axbot por `bot_config`**

Reemplazar el bloque que abre `mysqli_connect(..., 'axbot')` y hace `SELECT json FROM empresa WHERE empresa='$slugEsc'` por una lectura sobre la conexión del tenant `$conexion` (ya disponible vía `Conexion.php`):

```php
    case 'getMenuPrincipal':
        $editable = [
            '200' => 'Menú principal (clientes registrados)',
            '100' => 'Identificación de cliente (primera vez)',
        ];
        $allMenus = [];
        $res = mysqli_query($conexion, "SELECT menu_json FROM bot_config LIMIT 1");
        if ($res && ($jrow = mysqli_fetch_assoc($res))) {
            $decoded  = json_decode($jrow['menu_json'], true);
            $allMenus = isset($decoded['menu']) ? $decoded['menu'] : (is_array($decoded) ? $decoded : []);
        }
        // ... (resto del armado de $groups SIN CAMBIOS) ...
```

Eliminar la derivación de `$slug` y `$slugEsc` de este case (ya no se filtra por slug). Conservar intacto el loop que arma `$groups` y el `usort`.

- [ ] **Step 2 (Checkpoint): Lint**

```bash
docker exec demo_atiende_app php -l /var/www/atiende/ajax/configuracion.php
```
Expected: `No syntax errors detected`.

---

## Tarea 4: `configuracion.php` — `saveMenuPrincipal` sobre `bot_config`

**Files:**
- Modify: `ajax/configuracion.php` (case `saveMenuPrincipal`, ~130-164)

- [ ] **Step 1: Reemplazar lectura+UPDATE de axbot por `bot_config`**

```php
    case 'saveMenuPrincipal':
        $incoming     = json_decode($_POST['items'] ?? '[]', true);
        $targetMenuId = $_POST['menuId'] ?? '';
        $map          = [];
        foreach ($incoming as $it) $map[$it['opcionId']] = $it['opcion'];

        $res  = mysqli_query($conexion, "SELECT menu_json FROM bot_config LIMIT 1");
        $jrow = $res ? mysqli_fetch_assoc($res) : null;
        if (!$jrow) { echo json_encode(['ok' => false, 'error' => 'tenant sin menú']); break; }
        $decoded = json_decode($jrow['menu_json'], true);
        $wrapped = isset($decoded['menu']);
        $menuArr = $wrapped ? $decoded['menu'] : $decoded;
        foreach ($menuArr as &$entry) {
            if (($entry['menuId'] ?? '') === $targetMenuId) {
                foreach ($entry['menuItem'] as &$opt) {
                    if (isset($map[$opt['opcionId']])) { $opt['opcion'] = $map[$opt['opcionId']]; }
                }
                unset($opt);
                break;
            }
        }
        unset($entry);
        if ($wrapped) { $decoded['menu'] = $menuArr; } else { $decoded = $menuArr; }
        $newJson    = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        $newJsonEsc = mysqli_real_escape_string($conexion, $newJson);
        mysqli_query($conexion, "UPDATE bot_config SET menu_json = '$newJsonEsc' WHERE id = 1");
        echo json_encode(['ok' => true]);
        break;
```

Eliminar `$slug`, `$slugEsc`, `mysqli_connect(...,'axbot')` y `mysqli_close($ax)` de este case.

- [ ] **Step 2 (Checkpoint): Lint + round-trip**

```bash
docker exec demo_atiende_app php -l /var/www/atiende/ajax/configuracion.php
```
Expected: `No syntax errors detected`.

Round-trip manual (con sesión admin de un tenant `wb_*`): editar un texto de opción en la UI de configuración, guardar, recargar, y confirmar que persistió — y que `SELECT menu_json FROM wb_<slug>.bot_config` refleja el cambio.

---

## Tarea 5: `global.php` — `getWebMasterConfig` lee `telefono` desde `bot_config`

**Files:**
- Modify: `config/global.php` (función `getWebMasterConfig`, ~78-89)

**Contexto:** `['data']['telefono']` SÍ se consume (`ws/post.php:459` → `$miTelefono`), así que se migra (no se dropea). Hoy lee `SELECT telefono FROM axbot.empresa LIMIT 1`. Pasa a leer `telefono` de `bot_config` en la DB del tenant resuelta.

- [ ] **Step 1: Reemplazar la conexión a axbot por la DB del tenant**

Reemplazar el bloque `try { $link = @mysqli_connect(..., 'axbot'); ... }` por:

```php
        // telefono del tenant desde bot_config (solo DBs wb_*)
        $dbForPhone = (class_exists('Connection') && method_exists('Connection', 'getDatabase'))
            ? Connection::getDatabase()
            : (defined('DB_NAME') ? DB_NAME : 'atiende');
        if (strncmp($dbForPhone, 'wb_', 3) === 0) {
            try {
                $link = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, $dbForPhone);
                if ($link) {
                    $r = mysqli_query($link, 'SELECT telefono FROM bot_config LIMIT 1');
                    if ($r && ($row = mysqli_fetch_assoc($r)) && !empty($row['telefono'])) {
                        $config['data']['empresa']  = ['telefono' => $row['telefono']];
                        $config['data']['telefono'] = $row['telefono'];
                        $config['empresa']          = ['telefono' => $row['telefono']];
                    }
                    mysqli_close($link);
                }
            } catch (Exception $e) {}
        }
```

- [ ] **Step 2 (Checkpoint): Lint**

```bash
docker exec demo_atiende_app php -l /var/www/atiende/config/global.php
```
Expected: `No syntax errors detected`. Verificar que para `atiende` (no-`wb_`) la función ya no toca `axbot` y devuelve config sin telefono (aceptable).

---

## Tarea 6: Script de migración one-time

**Files:**
- Create: `migrate_axbot_to_bot_config.php` (temporal — se borra tras correr)

- [ ] **Step 1: Escribir el script**

```php
<?php
// One-time: copia axbot.empresa.{json,telefono} -> wb_<slug>.bot_config
// Correr DENTRO del container: docker exec demo_atiende_app php /var/www/atiende/migrate_axbot_to_bot_config.php
$host='mysql8'; $user='root'; $pass='root';
$pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

// 1. Leer todos los menús de axbot.empresa
$empresas = $pdo->query("SELECT empresa AS slug, json, telefono FROM axbot.empresa")->fetchAll(PDO::FETCH_ASSOC);

$ddl = "CREATE TABLE IF NOT EXISTS `bot_config` (
  `id` tinyint(1) NOT NULL DEFAULT 1,
  `menu_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), CONSTRAINT `chk_single_row` CHECK (`id`=1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

foreach ($empresas as $e) {
    $db = 'wb_' . $e['slug'];
    // ¿Existe la DB del tenant?
    $exists = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($db))->fetch();
    if (!$exists) { echo "SKIP $db (no existe)\n"; continue; }
    $pdo->exec("USE `$db`");
    $pdo->exec($ddl);
    $st = $pdo->prepare("INSERT INTO bot_config (id, menu_json, telefono) VALUES (1, ?, ?)
                         ON DUPLICATE KEY UPDATE menu_json=VALUES(menu_json), telefono=VALUES(telefono)");
    $st->execute([$e['json'], $e['telefono']]);
    echo "OK $db\n";
}
echo "Migración completa.\n";
```

- [ ] **Step 2 (Checkpoint): Correr y verificar**

```bash
docker exec demo_atiende_app php /var/www/atiende/migrate_axbot_to_bot_config.php
```
Expected: una línea `OK wb_<slug>` por cada tenant existente (o `SKIP` si la DB no existe). Verificar con `SELECT id, JSON_VALID(menu_json) FROM wb_<slug>.bot_config` que cada uno quedó con menú válido.

> El script se borra en la Tarea 7. En cada deployment de prod (campostrini/faustina/termoplastica) debe correrse este script **antes** del teardown de axbot.

---

## Tarea 7: Teardown de `axbot` en código y archivos sueltos

**Files:**
- Modify: `config/Connection.php` (~57-75)
- Delete: `_docker/mariadb/00_axbot_schema.sql`, `fix_corp_menu.php`, `migrate_axbot_to_bot_config.php`

- [ ] **Step 1: Confirmar 0 llamadas vivas a `runQueryLogin`**

```bash
docker exec demo_atiende_app sh -c "grep -rn 'runQueryLogin' /var/www/atiende --include=*.php | grep -v '//' "
```
Expected: solo la **definición** en `config/Connection.php` (las llamadas en `ws/post.php`, `pedidos/pago.php`, `ws/m/finaliza*.php` están comentadas). Si aparece alguna llamada viva, NO borrar — reportar.

- [ ] **Step 2: Eliminar `runQueryLogin` de `config/Connection.php`**

Borrar la función `public static function runQueryLogin($query) { ... }` (la que conecta a `"axbot"`).

- [ ] **Step 3: Borrar archivos obsoletos**

```bash
docker exec demo_atiende_app rm -f /var/www/atiende/_docker/mariadb/00_axbot_schema.sql /var/www/atiende/fix_corp_menu.php /var/www/atiende/migrate_axbot_to_bot_config.php
```

- [ ] **Step 4 (Checkpoint): Lint + sin referencias vivas**

```bash
docker exec demo_atiende_app php -l /var/www/atiende/config/Connection.php
docker exec demo_atiende_app sh -c "grep -rn \"'axbot'\\|\\\"axbot\\\"\\|axbot.empresa\\|FROM empresa\" /var/www/atiende --include=*.php | grep -v '//' | grep -v _backup"
```
Expected: lint OK; el grep no devuelve referencias vivas a `axbot` (solo, a lo sumo, líneas comentadas o el string literal `"title" => "axbot"` en `ws/post.php:1309`, que es inocuo).

---

## Tarea 8: Teardown de `axbot` en setup + docs

**Files:**
- Modify: `startup.ps1`, `startup.sh`, `install.bat`, `sql/setup_databases.sql`, `_docker/mysql/init.sql`, `CLAUDE.md`

- [ ] **Step 1: `startup.ps1` — quitar bloque axbot**

Eliminar el `DROP DATABASE ... CREATE DATABASE axbot` y el `if (Test-Path "_docker\mariadb\00_axbot_schema.sql") { ... }` (el import a `axbot`).

- [ ] **Step 2: `startup.sh` — quitar bloque axbot**

Eliminar el comentario `# Resetear axbot ...` (línea 70), el `DROP/CREATE axbot` (línea 72) y el import de `_docker/mariadb/00_axbot_schema.sql` (líneas 74-75). **Además**, en el echo de resumen (línea 148) sacar `axbot`: `echo "         DBs: atiende, axbot"` → `echo "         DBs: atiende"`.

- [ ] **Step 3: `install.bat` — quitar paso axbot**

Eliminar `echo [3/4] Importando base de datos admin (axbot)...` (línea 34), la línea `mysql ... axbot < _docker\mariadb\00_axbot_schema.sql` (35) y su bloque `if %errorlevel%` (36-40). Renumerar los pasos restantes si corresponde. **Además**, sacar el echo de resumen `echo    - axbot        (configuracion WhatsApp, menus, usuarios)` (línea 54).

- [ ] **Step 4: `sql/setup_databases.sql` — quitar TODAS las referencias a axbot (son 5)**

Eliminar:
- Línea 3: el `y axbot.sql` del comentario de header.
- Líneas 16-18: el statement completo `CREATE DATABASE IF NOT EXISTS \`axbot\` ...;`.
- Línea 26: `GRANT ALL PRIVILEGES ON \`axbot\`.* TO 'atiende'@'localhost';`.
- Línea 31: `GRANT ALL PRIVILEGES ON \`axbot\`.* TO 'atiende'@'%';`.
- Línea 40: el comentario `--   mysql -u root -p axbot < _docker/mariadb/00_axbot_schema.sql`.

Verificar al final: `grep -n axbot sql/setup_databases.sql` no debe devolver nada.

- [ ] **Step 5: `_docker/mysql/init.sql` — quitar CREATE + GRANT axbot**

Eliminar la línea 12 (`CREATE DATABASE IF NOT EXISTS \`axbot\` ...`) y la 18 (`GRANT ALL PRIVILEGES ON \`axbot\`.* ...`).

- [ ] **Step 6: `CLAUDE.md` — actualizar docs**

- En **Entry Points**: corregir `- Login authenticates against the axbot database table via config/Conexion.php` → describir que el login va a `pedidos_platform.tenants` vía `ajax/usuario.php?op=verificar`.
- En la sección **"BotEngine Menu Structure (axbot.empresa.json)"**: renombrar y reescribir para reflejar que el menú vive en `wb_*.bot_config.menu_json` (una fila por DB de tenant), no en `axbot.empresa`.

- [ ] **Step 7 (Checkpoint): Setup limpio de punta a punta**

```bash
docker exec mysql8 mysql -uroot -proot -e "SHOW DATABASES LIKE 'axbot';"
```
Expected: vacío tras un setup nuevo. Correr `startup.ps1` (o `.sh`) completo y confirmar que termina sin error y que `atiende` queda funcional (login + dashboard cargan). Confirmar que `axbot` ya no se crea.

> **Nota para stacks de dev existentes:** como se quitó el `DROP DATABASE axbot`, un stack que ya tenía `axbot` creado no lo elimina solo. Tras correr la migración (Tarea 6) y verificar, dropearlo una vez a mano: `docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS axbot;"`.

---

## Cierre

- [ ] Repasar los **Criterios de done** del spec (`docs/superpowers/specs/2026-06-03-bot-menu-tenant-migration-design.md`) y marcarlos.
- [ ] Borrar el `.zip` de backup de axadmin (`axadmin_backup_20260603.zip`) si ya no se necesita.
