# Migrar provisioning a `wb_*.bot_config` — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Que el aprovisionamiento de tenants (pedidos-platform) siembre y administre el menú/telefono del bot en `wb_<slug>.bot_config`, eliminando todo uso de la base global `axbot`.

**Arch:** El provisioning crea la DB `wb_<slug>`, le aplica `tenant_schema.sql` (que ahora incluye `bot_config`) y siembra el menú default vía la conexión del provisioner (ya `USE`'d). Suspensión/activación dejan de tocar axbot (las gatea `tenants.estado`). El telefono se escribe en `bot_config`.

**Spec:** `docs/specs/2026-06-04-provisioning-bot-config-migration.md`

## Convenciones (LEER)
- **SIN git** (convención del proyecto). No hay pasos de commit; cada tarea cierra con un **Checkpoint**.
- **Verificación:** este repo SÍ tiene PHPUnit 10. Usar `php -l` para sintaxis y la suite para regresión, **vía Docker** (container `pedidos_platform_app`): `docker exec pedidos_platform_app php -l <ruta>` y `docker exec pedidos_platform_app vendor/bin/phpunit`. NO usar el php local (xampp 7.4).
- **Orden obligatorio:** Tarea 1 (schema) antes que el resto. La eliminación de `getAxbotPDO()` (Tarea 5, Step 1) va DESPUÉS de repuntar todos los callers (Tareas 2-4).
- Rutas relativas a `C:\Users\ACER\Downloads\whatsbus2021-main\pedidos-platform`. Dentro del container el proyecto está en `/var/www/pedidos-platform`.

---

## Tarea 1: `tenant_schema.sql` — agregar tabla `bot_config`

**Files:** Modify `templates/sql/tenant_schema.sql`

- [ ] **Step 1:** Antes de la línea final `SET FOREIGN_KEY_CHECKS = 1;`, agregar:

```sql
CREATE TABLE `bot_config`  (
  `id` tinyint(1) NOT NULL DEFAULT 1,
  `menu_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  CONSTRAINT `chk_single_row` CHECK (`id` = 1)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;
```

Una sentencia `CREATE TABLE` plana (sin `;` dentro de strings) — compatible con `splitSqlStatements()` y con el `explode(';')` de dev_provision.

- [ ] **Step 2 (Checkpoint):** Aplicar el schema a una DB scratch y confirmar que `bot_config` se crea:
```
docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS wb_schematest; CREATE DATABASE wb_schematest DEFAULT CHARACTER SET utf8mb4;"
docker exec -i mysql8 mysql -uroot -proot wb_schematest < templates/sql/tenant_schema.sql
docker exec mysql8 mysql -uroot -proot wb_schematest -e "SHOW TABLES LIKE 'bot_config'; DESCRIBE bot_config;"
docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS wb_schematest;"
```
Expected: la tabla existe con columnas id/menu_json/telefono/updated_at.

---

## Tarea 2: `ProvisioningService.php` — sembrar bot_config y limpiar axbot

**Files:** Modify `modelos/ProvisioningService.php`

- [ ] **Step 1 — `provision()`:** Eliminar la línea 18 `$clave = bin2hex(random_bytes(16));`. Reemplazar el bloque `$paso = 3` (líneas 44-49) por:

```php
            $paso = 3;
            $menuJson = file_get_contents(ROOT . '/templates/bot/default_menu.json');
            // $prov sigue posicionado en `USE wb_<slug>` (paso 2). Insertamos el
            // menú default en la bot_config del tenant (antes iba a axbot.empresa).
            $prov->prepare('INSERT INTO bot_config (id, menu_json) VALUES (1, ?)')
                 ->execute([$menuJson]);
```

- [ ] **Step 2 — `compensate()`:** Eliminar el bloque (líneas 111-113):
```php
            if ($paso >= 3) {
                getAxbotPDO()->prepare('DELETE FROM empresa WHERE empresa = ?')->execute([$slug]);
            }
```
El `DROP DATABASE` del paso>=1 ya limpia `bot_config`.

- [ ] **Step 3 — `suspend()`:** Eliminar el `SELECT slug` + el `getAxbotPDO()->UPDATE empresa SET activo = 0` (líneas 142-147). Dejar solo el `UPDATE tenants SET estado='suspendido'`.

- [ ] **Step 4 — `reactivate()`:** Igual: eliminar el `SELECT slug` + `UPDATE empresa SET activo = 1` (líneas 154-159). Dejar el `UPDATE tenants SET estado='activo'`.

- [ ] **Step 5 — `teardown()`:** Eliminar del array `$plan` la línea `"DELETE FROM axbot.empresa WHERE empresa = '{$row['slug']}'"` (187). Eliminar la ejecución real `getAxbotPDO()->prepare('DELETE FROM empresa ...')->execute(...)` (206). Conservar DROP DATABASE + los UPDATE a tenants/users/subscriptions.

- [ ] **Step 6 (Checkpoint):**
```
docker exec pedidos_platform_app php -l /var/www/pedidos-platform/modelos/ProvisioningService.php
```
Expected: "No syntax errors detected". Y confirmar que no queda `axbot`/`getAxbotPDO`/`$clave` en el archivo (grep).

---

## Tarea 3: `activate.php` + `tenant_save.php`

**Files:** Modify `superadmin/activate.php`, `superadmin/tenant_save.php`

- [ ] **Step 1 — `activate.php:16`:** Eliminar la línea `getAxbotPDO()->prepare("UPDATE empresa SET activo = 1 ...")->execute([$tenant['slug']]);`. El `UPDATE tenants SET estado='activo'` (línea 15) queda. (No tocar el `require database.php` — se sigue usando `getPlatformPDO`.)

- [ ] **Step 2 — `tenant_save.php:25`:** Reemplazar:
```php
    getAxbotPDO()->prepare('UPDATE empresa SET telefono = ? WHERE empresa = ?')->execute([$phoneId, $row['slug']]);
```
por una escritura a la `bot_config` del tenant vía el provisioner:
```php
    $prov = getProvisionerPDO();
    $prov->exec("USE `wb_{$row['slug']}`");
    $prov->prepare('UPDATE bot_config SET telefono = ? WHERE id = 1')->execute([$phoneId]);
```
**IMPORTANTE:** estas 3 líneas deben quedar DENTRO del `if ($token !== '')` (líneas 22-26), exactamente donde estaba el `UPDATE empresa` — para preservar el comportamiento actual (el telefono solo se escribe cuando además se envía un token). No moverlas a nivel superior.

- [ ] **Step 3 (Checkpoint):**
```
docker exec pedidos_platform_app php -l /var/www/pedidos-platform/superadmin/activate.php
docker exec pedidos_platform_app php -l /var/www/pedidos-platform/superadmin/tenant_save.php
```
Expected: ambos "No syntax errors detected"; sin `getAxbotPDO`/`axbot` en ninguno.

---

## Tarea 4: `dev_provision.php` — sembrar bot_config

**Files:** Modify `superadmin/dev_provision.php`

- [ ] **Step 1:** Eliminar `$clave = bin2hex(random_bytes(16));` (línea 33) y `$axbot = getAxbotPDO();` (línea 36). Reemplazar el bloque "3. Fila en axbot.empresa" (líneas 51-55) por:

```php
            // 3. Sembrar el menú default en la bot_config del tenant
            $menuJson = file_get_contents(ROOT . '/templates/bot/default_menu.json');
            $prov->prepare('INSERT INTO bot_config (id, menu_json) VALUES (1, ?)')
                 ->execute([$menuJson]);
```
(`$prov` ya está `USE`'d en `wb_<slug>` desde la línea 43.)

- [ ] **Step 2 (Checkpoint):**
```
docker exec pedidos_platform_app php -l /var/www/pedidos-platform/superadmin/dev_provision.php
```
Expected: "No syntax errors detected"; sin `getAxbotPDO`/`axbot`/`$clave`.

---

## Tarea 5: Cleanup de infra axbot

**Files:** Modify `config/database.php`, `config/bootstrap.php`, `.env.example`, `db/pedidos_platform.sql`, `tests/bootstrap.php`; Delete `templates/sql/axbot_row_insert.sql`

- [ ] **Step 1 — `config/database.php`:** Eliminar la función `getAxbotPDO()` completa (líneas 17-28). Dejar `getPlatformPDO` y `getProvisionerPDO`.

- [ ] **Step 2 — `config/bootstrap.php:7`:** Quitar `'DB_AXBOT_NAME'` de la lista `$dotenv->required([...])`.

- [ ] **Step 3 — `.env.example`:** Eliminar la línea `DB_AXBOT_NAME=axbot` (línea 12).

- [ ] **Step 4 — `tests/bootstrap.php:3`:** Actualizar el comentario que menciona `getAxbotPDO()` (quitar esa mención) para que un grep quede limpio.

- [ ] **Step 5 — Borrar artefacto:** `templates/sql/axbot_row_insert.sql`.
```
docker exec pedidos_platform_app rm -f /var/www/pedidos-platform/templates/sql/axbot_row_insert.sql
```

- [ ] **Step 6 — `db/pedidos_platform.sql` (grants comentados):** 
  - Quitar la línea `-- GRANT SELECT, INSERT, UPDATE ON axbot.empresa TO 'pp_app'@'%';`.
  - Cambiar `-- GRANT CREATE, DROP ON \`wb_%\`.* TO 'pp_provisioner'@'%';` por `-- GRANT CREATE, DROP, SELECT, INSERT, UPDATE ON \`wb_%\`.* TO 'pp_provisioner'@'%';`.

- [ ] **Step 7 (Checkpoint):**
```
docker exec pedidos_platform_app php -l /var/www/pedidos-platform/config/database.php
docker exec pedidos_platform_app php -l /var/www/pedidos-platform/config/bootstrap.php
docker exec pedidos_platform_app sh -c "grep -rn 'getAxbotPDO\|DB_AXBOT_NAME\|axbot' /var/www/pedidos-platform --include=*.php | grep -v 'docs/'"
```
Expected: lint OK; el grep no devuelve referencias vivas a axbot (a lo sumo, nada — o solo docs).

---

## Tarea 6: Verificación integral (PHPUnit + alta de prueba)

- [ ] **Step 1 — Suite PHPUnit:**
```
docker exec pedidos_platform_app vendor/bin/phpunit
```
Expected: verde (en particular `test_teardown_dry_run_does_not_destroy` pasa sin cambios).

- [ ] **Step 2 — Alta de prueba (funcional):** Provisionar un tenant de prueba con `dev_provision.php` (vía la UI superadmin en dev, o invocando el flujo). Luego confirmar que su `bot_config` quedó sembrada:
```
docker exec mysql8 mysql -uroot -proot wb_<slugprueba> -e "SELECT id, JSON_VALID(menu_json) ok, JSON_LENGTH(menu_json) items, telefono FROM bot_config;"
```
Expected: una fila id=1, ok=1, items>0. Si no se puede correr el alta completa por dependencias (CSRF/sesión), al menos verificar que el INSERT del menú corre sobre una DB con el schema aplicado.

- [ ] **Step 3 — Repaso de done-criteria** del spec y marcado.

---

## Cierre
- [ ] Confirmar que `axbot` permanece eliminada (no se recrea) y que el alta de tenants nuevos deja el bot funcional leyendo de `wb_*.bot_config`.
