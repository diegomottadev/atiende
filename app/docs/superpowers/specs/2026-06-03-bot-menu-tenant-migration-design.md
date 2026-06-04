# Migrar el menú del bot a la DB del tenant y eliminar `axbot` — Spec de Diseño

**Fecha:** 2026-06-03
**Proyecto:** Atiende (atiende2021)
**Estado:** Aprobado para planificación

## Objetivo

Eliminar por completo la base de datos global `axbot`, moviendo el único dato per-tenant que todavía vive ahí — el **menú del bot (JSON)** — a la base de datos propia de cada tenant (`wb_<slug>`). Esto cierra una inconsistencia arquitectónica: todo lo demás del tenant ya está aislado en su DB `wb_*` o centralizado en `pedidos_platform.tenants`, salvo el menú.

## Contexto y motivación

`axbot.empresa` es herencia del bot legado (la extensión Chrome, ya eliminada en este repo). Cuando el producto pasó al modelo multi-tenant con DBs `wb_*`, los datos transaccionales migraron pero el menú JSON quedó en la tabla global `axbot.empresa`, indexado por string `slug`.

Hallazgos de la exploración:

- `pedidos_platform.tenants` lo **administra una app separada** (`pedidos-platform`); este repo solo la **lee** (slug, nombre, credenciales WA). Por eso no es destino válido para el menú: implicaría escribirle a una tabla ajena y coordinar migraciones cross-repo.
- Este repo **sí es dueño** de las DBs `wb_*`: las crea y maneja toda su data transaccional. Es el lugar natural para el menú.
- Tras eliminar `axadmin/`, las tablas `axbot.users` / `axbot.usuarios` quedaron **sin consumidores vivos**, y `config/Connection.php::runQueryLogin` (que conecta a `axbot`) **sin llamadas vivas** (solo comentadas).

### Usos vivos actuales de `axbot.empresa`

| # | Archivo | Operación |
|---|---|---|
| 1 | `ws/webhook.php:299-317` | LEE menú: `SELECT json FROM empresa WHERE empresa = '$slug'` (camino WhatsApp Cloud API) |
| 2 | `ajax/configuracion.php:94-128` (`getMenuPrincipal`) | LEE menú |
| 3 | `ajax/configuracion.php:130-161` (`saveMenuPrincipal`) | ESCRIBE menú: `UPDATE empresa SET json=...` |
| 4 | `config/global.php:78-89` (`getWebMasterConfig`) | LEE `telefono`: `SELECT telefono FROM empresa LIMIT 1` (sin filtro de slug) |

Además: `fix_corp_menu.php` (script de mantenimiento puntual que `UPDATE empresa` para slug `corp`) contiene el **menú default canónico**.

## Diseño

### 1. Nuevo almacenamiento: tabla `bot_config` en cada DB `wb_*`

`bot_config` es una tabla **exclusiva de las DBs `wb_*`**. La DB default de dev (`atiende`) **no** la lleva: ese camino nunca tocó `axbot` — el webhook, para DBs no-`wb_`, cae al fallback de archivo `ws/dev_tenant_config*.json` (ver `ws/webhook.php:320-330`), que se conserva tal cual.

Como cada DB `wb_*` **es** un único tenant, la tabla guarda **una sola fila**. La asociación al tenant es **implícita por base de datos** — no lleva columna `slug`/`tenant_id` (sería redundante, podría desincronizarse, y un FK a `pedidos_platform.tenants` es imposible al ser otra DB de otra app). El aislamiento lo da el routing `$_SESSION['tenant_db'] → wb_<slug>`.

```sql
CREATE TABLE IF NOT EXISTS `bot_config` (
  `id`         tinyint(1)   NOT NULL DEFAULT 1,
  `menu_json`  longtext     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono`   varchar(20)  NULL DEFAULT NULL,   -- reemplaza axbot.empresa.telefono
  `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_single_row` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Reescribir las 4 lecturas/escrituras

Todas pasan a operar sobre la DB del tenant ya conectada, en vez de abrir una conexión a `axbot`.

| # | Archivo | Cambio |
|---|---|---|
| 1 | `ws/webhook.php` | Reemplazar el bloque `axbot` por lectura desde `$conexion` (mysqli del tenant, ya disponible vía `Conexion.php` incluido en línea 93): `SELECT menu_json FROM bot_config LIMIT 1`. **Se mantiene el gate por prefijo `wb_`** (`strncmp($tenantDbName,'wb_',3)===0`) y **se conserva el fallback** a `ws/dev_tenant_config*.json` para DBs no-`wb_`. El resto del armado de `$responseWebMaster` (`empresa_nombre` desde `pedidos_platform`, flags b2b/b2c) se conserva. |
| 2 | `ajax/configuracion.php` `getMenuPrincipal` | Leer `menu_json` desde `bot_config` vía `$conexion`. Eliminar conexión a `axbot`. |
| 3 | `ajax/configuracion.php` `saveMenuPrincipal` | `UPDATE bot_config SET menu_json=...` vía `$conexion`. |
| 4 | `config/global.php` `getWebMasterConfig` | Leer `telefono` desde `bot_config` en la DB del tenant resuelta. **Verificar primero si `['data']['telefono']` lo consume algún modelo** (`Consulta`, `Consultas`, `Persona`, `Reclamo` hacen `$this->responseWebMaster = getWebMasterConfig()`); si está muerto, **dropearlo** en vez de migrarlo, y la columna `telefono` queda sin uso (o se omite). |

### 3. Seed del menú default + migración de datos

- **Template default (artefacto):** el menú hardcodeado de `fix_corp_menu.php` se convierte en un seed reusable que este repo **entrega**: `_docker/mariadb/bot_config_seed.sql`, conteniendo el `CREATE TABLE bot_config` + el `INSERT` del menú default. Es el menú inicial de un tenant nuevo.
- **Provisioning (handoff, fuera de alcance ejecutar):** la creación de DBs `wb_*` la hace la app separada `pedidos-platform`, no este repo (`startup.*` solo crea `atiende`). Por lo tanto este repo **no inserta** la fila en el alta; entrega el artefacto seed para que el provisioner lo aplique al crear cada `wb_*`. El criterio de done de este repo se limita a que el artefacto exista y sea correcto.
- **Migración one-time:** script que por cada tenant `wb_*` existente copia `axbot.empresa.json` (match por slug) → `wb_<slug>.bot_config.menu_json` (creando la tabla si no existe). Debe correr **por deployment** (los prods campostrini/faustina/termoplastica tienen stack propio).

### 4. Teardown de `axbot` + setup + docs

- Quitar bloques de conexión a `axbot` en `ws/webhook.php`, `ajax/configuracion.php`, `config/global.php`.
- Eliminar `config/Connection.php::runQueryLogin` (confirmar 0 llamadas vivas antes).
- Borrar `_docker/mariadb/00_axbot_schema.sql` y `fix_corp_menu.php`.
- Sacar el `DROP/CREATE axbot` + import del schema de `startup.ps1`, `startup.sh`, `install.bat`, y el comentario en `sql/setup_databases.sql`.
- Quitar la creación y el `GRANT` de `axbot` en `_docker/mysql/init.sql` (líneas 12 y 18) — si no, queda una DB huérfana en cada init del container.
- Actualizar `CLAUDE.md`:
  - Sección **"BotEngine Menu Structure (axbot.empresa.json)"** → describir `wb_*.bot_config.menu_json`.
  - Entry Points: corregir *"Login authenticates against the axbot database"* (obsoleto; el login va a `pedidos_platform.tenants` vía `ajax/usuario.php?op=verificar`).

## Orden de ejecución seguro (mitiga el riesgo principal)

El código nuevo debe estar desplegado **antes** de borrar la tabla `empresa`, y la migración de datos debe correr **antes** de tirar `axbot`:

1. Crear tabla `bot_config` + seed default.
2. Desplegar código que lee/escribe en `bot_config` (las 4 reescrituras).
3. Correr migración de datos (`axbot.empresa.json` → `bot_config.menu_json`) por deployment.
4. Eliminar `axbot` (DB, schema file, scripts, bloques de conexión, setup).

## Fuera de alcance

- Cambios en la app separada `pedidos-platform`.
- Migración de los stacks Docker de prod existentes (se documenta el paso, pero su ejecución es operativa por deployment).
- Rediseño del editor de menú de la UI (`configuracion.php`) más allá de repuntar su fuente de datos.

## Criterios de done

- [ ] El webhook de WhatsApp Cloud API carga el menú desde `wb_*.bot_config` para un tenant `wb_*`.
- [ ] `getMenuPrincipal` / `saveMenuPrincipal` leen y escriben en `bot_config`.
- [ ] Existe el artefacto seed `_docker/mariadb/bot_config_seed.sql` (`CREATE TABLE bot_config` + `INSERT` del menú default), listo para que el provisioner lo aplique al crear un `wb_*`.
- [ ] No queda ninguna referencia viva a la DB `axbot` en el código.
- [ ] `startup.ps1` / `startup.sh` / `install.bat` corren sin `axbot` y dejan un entorno funcional.
- [ ] `CLAUDE.md` refleja el nuevo modelo.
- [ ] Resuelto el destino de `['data']['telefono']` (migrado a `bot_config.telefono` o eliminado por desuso, con evidencia).
