# Migrar provisioning de `axbot.empresa` a `wb_*.bot_config` — Spec de Diseño

**Fecha:** 2026-06-04
**Proyecto:** pedidos-platform
**Estado:** Aprobado para planificación

## Objetivo

Migrar el aprovisionamiento de tenants para que el menú del bot se siembre en la tabla `bot_config` dentro de la DB propia de cada tenant (`wb_<slug>`), en vez de la base global `axbot.empresa`. Esto cierra el círculo con la migración ya hecha en la app Atiende (que ya lee/escribe `wb_*.bot_config`) y permite que `axbot` quede eliminada definitivamente.

## Contexto

La app **Atiende** (repo `whatsbus2021-main`) ya migró todos sus consumidores a `wb_*.bot_config` y la base `axbot` fue dropeada. Pero el **productor** del menú — `pedidos-platform`, que aprovisiona tenants — sigue escribiendo en `axbot.empresa`. Quedaron desconectados: un tenant nuevo recibiría su menú en `axbot.empresa` (que ya no existe → el `INSERT` fallaría), mientras que el webhook lee de `wb_*.bot_config`. Esta migración hace que el provisioning siembre `bot_config`.

### Hallazgos que simplifican el cambio

- **Suspensión:** el webhook (`whatsbus2021-main/ws/webhook.php:51`) ya filtra el tenant por `tenants.estado = 'activo'`. El `UPDATE axbot.empresa SET activo` de `suspend/reactivate` era redundante → se elimina sin reemplazo.
- **Token `clave`:** se genera e inserta en `axbot.empresa.clave` pero **ningún consumidor vivo lo lee** → se elimina del provisioning. `bot_config` no necesita columna `clave`.
- **Teardown:** `DROP DATABASE wb_<slug>` ya se lleva `bot_config` consigo → el `DELETE FROM axbot.empresa` se elimina.

### Inventario de puntos a cambiar (todos en `pedidos-platform`)

| Archivo | Línea(s) | Operación axbot actual |
|---|---|---|
| `modelos/ProvisioningService.php` | 18, 45-49 | genera `$clave`; `INSERT INTO empresa` (provision) |
| `modelos/ProvisioningService.php` | 112 | `DELETE FROM empresa` (compensación) |
| `modelos/ProvisioningService.php` | 146, 158 | `UPDATE empresa SET activo` (suspend/reactivate) |
| `modelos/ProvisioningService.php` | 187, 206 | `DELETE FROM empresa` + texto del plan (teardown) |
| `superadmin/dev_provision.php` | 33, 36, 52-55 | `$clave`, `getAxbotPDO()`, `INSERT INTO empresa` |
| `superadmin/activate.php` | 16 | `UPDATE empresa SET activo=1` (activación de tenant) |
| `superadmin/tenant_save.php` | 25 | `UPDATE empresa SET telefono` (al guardar phone_id de WhatsApp) |
| `config/database.php` | 17-28 | `getAxbotPDO()` (queda sin uso tras repuntar todos los callers) |
| `config/bootstrap.php` | 7 | `DB_AXBOT_NAME` en la lista `required()` |
| `templates/sql/tenant_schema.sql` | — | (falta la tabla `bot_config`) |
| `templates/sql/axbot_row_insert.sql` | — | artefacto muerto (template del INSERT a axbot) |
| `tests/ProvisioningServiceTest.php` | dry-run | (ver §8 — NO requiere cambio: el test no aserta sobre la línea axbot) |
| `.env.example` / `db/pedidos_platform.sql` | — | `DB_AXBOT_NAME`, grants a `axbot.empresa` y `wb_%` |

## Diseño

### 1. `tenant_schema.sql` — agregar la tabla `bot_config`

Agregar (antes del `SET FOREIGN_KEY_CHECKS = 1;` final) la DDL de `bot_config` **sin** datos (el menú se inserta programáticamente):

```sql
CREATE TABLE `bot_config` (
  `id`         tinyint(1)   NOT NULL DEFAULT 1,
  `menu_json`  longtext     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono`   varchar(20)  NULL DEFAULT NULL,
  `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_single_row` CHECK (`id` = 1)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

Es una sola sentencia `CREATE TABLE` plana — compatible tanto con el `splitSqlStatements()` de `ProvisioningService` como con el `explode(';')` ingenuo de `dev_provision.php`.

### 2. `ProvisioningService::provision()` — sembrar `bot_config`

- **Eliminar** la línea 18 (`$clave = bin2hex(...)`).
- **Reemplazar** el bloque `$paso = 3` (líneas 44-49). En vez de `getAxbotPDO()->INSERT INTO empresa`, insertar en la DB del tenant usando la conexión `$prov` que **ya está posicionada en `USE wb_<slug>`** (de aplicar el schema en `$paso = 2`):

```php
            $paso = 3;
            $menuJson = file_get_contents(ROOT . '/templates/bot/default_menu.json');
            $prov->prepare('INSERT INTO bot_config (id, menu_json) VALUES (1, ?)')
                 ->execute([$menuJson]);
```

`telefono` queda NULL (igual que el `''` anterior; Atiende trata NULL/'' como "sin teléfono").

### 3. `ProvisioningService::compensate()` — quitar limpieza de axbot

Eliminar el bloque `if ($paso >= 3) { getAxbotPDO()->...DELETE FROM empresa... }` (líneas 111-113). El `DROP DATABASE wb_<slug>` (paso >= 1) ya limpia `bot_config`. La numeración de pasos no cambia (el `bot_config` insert sigue siendo paso 3, y se revierte con el DROP del paso 1).

### 4. `suspend()` / `reactivate()` + `activate.php` — quitar `UPDATE axbot SET activo`

- `ProvisioningService::suspend()` / `reactivate()`: eliminar el `SELECT slug` + `getAxbotPDO()->UPDATE empresa SET activo` (líneas 142-147 y 154-159). Conservar el `UPDATE tenants SET estado`.
- `superadmin/activate.php:16`: eliminar el `getAxbotPDO()->UPDATE empresa SET activo = 1`. El `UPDATE tenants SET estado='activo'` (línea 15) ya queda; eliminar el `require database.php` solo si no se usa nada más de ahí (sí se usa `getPlatformPDO`, así que se mantiene).

Los tres son seguros: el webhook gatea por `tenants.estado`, así que el flag `axbot.empresa.activo` era redundante.

### 5. `teardown()` — quitar `DELETE axbot`

- Eliminar la línea 187 del array `$plan` (el string `"DELETE FROM axbot.empresa..."`).
- Eliminar la ejecución real `getAxbotPDO()->DELETE FROM empresa` (línea 206).
- El resto (DROP DATABASE, updates a tenants/users/subscriptions) intacto.

### 6. `dev_provision.php` — mismo cambio que provision()

- Eliminar `$clave` (33) y `$axbot = getAxbotPDO()` (36).
- Reemplazar el bloque "3. Fila en axbot.empresa" (52-55) por un INSERT en `bot_config` vía `$prov` (ya `USE`'d en la línea 43). **Nota:** `dev_provision.php` aplica el schema con `explode(';')` — la tabla `bot_config` plana lo soporta.

### 7. `tenant_save.php` — repuntar la escritura del `telefono`

`superadmin/tenant_save.php:25` hoy hace `getAxbotPDO()->UPDATE empresa SET telefono = ? WHERE empresa = ?` (escribe el `phone_id` de WhatsApp como `telefono`). Repuntar a la `bot_config` del tenant usando la conexión del provisioner posicionada en su DB:

```php
        $prov = getProvisionerPDO();
        $prov->exec("USE `wb_{$row['slug']}`");
        $prov->prepare('UPDATE bot_config SET telefono = ? WHERE id = 1')->execute([$phoneId]);
```

(Es una acción de superadmin; reusar el provisioner —que ya alcanza cualquier `wb_*`— evita un helper de conexión nuevo. Requiere `UPDATE` en el grant `wb_%`, ver §8.)

### 8. `config/database.php` + `bootstrap` + grants — limpiar axbot

- Eliminar la función `getAxbotPDO()` (`config/database.php:17-28`) — sin consumidores tras repuntar provision, dev_provision, compensate, suspend, reactivate, teardown, activate y tenant_save.
- Quitar `DB_AXBOT_NAME` de la lista `$dotenv->required([...])` en `config/bootstrap.php:7` y de `.env.example:12`.
- Borrar el artefacto muerto `templates/sql/axbot_row_insert.sql`.
- En `db/pedidos_platform.sql` (grants comentados, plantilla de prod):
  - Quitar `GRANT ... ON axbot.empresa TO 'pp_app'@'%';` (línea 4).
  - **Ampliar el grant del provisioner** (línea 8): de `GRANT CREATE, DROP ON \`wb_%\`.*` a `GRANT CREATE, DROP, SELECT, INSERT, UPDATE ON \`wb_%\`.* TO 'pp_provisioner'@'%';`. **Crítico:** `CREATE/DROP` por sí solos NO permiten los `INSERT` del seed de `tenant_schema.sql` (permiso/areas/motivos) ni el `INSERT`/`UPDATE` de `bot_config` — en dev funciona solo porque el provisioner es `root`. Sin ampliar el grant, el alta falla en producción.

### 9. Tests (`tests/ProvisioningServiceTest.php`)

- **El test `test_teardown_dry_run_does_not_destroy` NO requiere cambios:** solo aserta `assertNotEmpty($plan['acciones'])` y `assertSame($slug, $plan['slug'])` — no inspecciona la línea `DELETE FROM axbot.empresa`. Quitar esa línea del array `$plan` no rompe el test.
- Los tests de `generateSlug` / `ensureUniqueSlug` / `splitSqlStatements` no se ven afectados.
- (Opcional, si la infra de test lo permite) agregar un test que verifique que tras provisionar, `wb_<slug>.bot_config` tiene una fila con JSON válido.
- Correr la suite vía Docker (PHPUnit 10): `docker compose run --rm app vendor/bin/phpunit` (o el comando equivalente del proyecto). Confirmar que queda en verde.

## Fuera de alcance

- Recrear `axbot` (queda eliminada).
- Re-migrar tenants ya creados (`wb_corp/demo/demo_1` ya tienen `bot_config`).
- Cambios en la app Atiende (ya migrada).

## Criterios de done

- [ ] `tenant_schema.sql` crea la tabla `bot_config`.
- [ ] `provision()` siembra `wb_<slug>.bot_config` con el menú default y ya no toca `axbot`; sin `$clave`.
- [ ] `compensate()`, `suspend()`, `reactivate()`, `teardown()` sin referencias a `axbot`.
- [ ] `activate.php` sin `UPDATE axbot`; `tenant_save.php` escribe el `telefono` en `wb_<slug>.bot_config`.
- [ ] `dev_provision.php` siembra `bot_config` y no toca `axbot`.
- [ ] `getAxbotPDO()` eliminada; `DB_AXBOT_NAME` fuera de `bootstrap.php` required() y `.env.example`; `axbot_row_insert.sql` borrado.
- [ ] Grant `wb_%` del provisioner ampliado a `CREATE, DROP, SELECT, INSERT, UPDATE`; grant a `axbot.empresa` removido.
- [ ] Sin referencias vivas a `axbot` / `getAxbotPDO` / `DB_AXBOT_NAME` en el repo.
- [ ] Suite PHPUnit en verde.
- [ ] Alta de un tenant de prueba (dev_provision) deja `wb_<slug>.bot_config` con JSON válido y el bot puede cargar el menú.
