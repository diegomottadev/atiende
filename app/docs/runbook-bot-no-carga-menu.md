# Runbook — El bot no carga el menú

**Síntoma:** un tenant escribe al WhatsApp y el bot no responde / no muestra el menú.

**Contexto:** desde la migración a `wb_*.bot_config` (jun 2026), el menú del bot vive en la tabla `bot_config` (una fila, `id=1`) dentro de la DB propia de cada tenant (`wb_<slug>`). El webhook (`ws/webhook.php`) lo lee de ahí. La base global `axbot` ya no existe.

Todos los comandos van contra el container MySQL `mysql8` (creds dev `root/root`). Corré en PowerShell, o dentro de Claude Code con `!` adelante.

---

## Diagnóstico en orden

### 1. ¿Existe el menú en `bot_config`?

Reemplazá `<slug>` por el del tenant (si la DB es `wb_corp`, el slug es `corp`):

```powershell
docker exec mysql8 mysql -uroot -proot wb_<slug> -e "SELECT id, JSON_VALID(menu_json) AS ok, JSON_LENGTH(menu_json) AS items, telefono FROM bot_config;"
```

| Resultado | Significado | Acción |
|---|---|---|
| `ok=1`, `items>0` | El menú está OK | Seguir al paso 2 |
| `ok=0` | JSON corrupto | Re-sembrar (ver "Re-sembrar el menú") |
| Sin filas | La tabla existe pero está vacía | Re-sembrar |
| `Table 'bot_config' doesn't exist` | El provisioning no creó la tabla | El alta del tenant falló — revisar pedidos-platform |

¿No sabés el nombre de la DB? → `docker exec mysql8 mysql -uroot -proot -e "SHOW DATABASES LIKE 'wb_%';"`

### 2. ¿El tenant está activo y con credenciales WhatsApp?

El webhook solo responde si `tenants.estado = 'activo'` (gatea en `ws/webhook.php:51`):

```powershell
docker exec mysql8 mysql -uroot -proot pedidos_platform -e "SELECT slug, estado, whatsapp_phone_id, deleted_at FROM tenants WHERE slug='<slug>';"
```

- `estado` debe ser `activo` (no `suspendido` / `pendiente_whatsapp` / `cancelado`).
- `whatsapp_phone_id` no debe estar vacío.
- `deleted_at` debe ser `NULL`.

### 3. ¿Qué dice el log del webhook?

```powershell
docker logs demo_atiende_app --tail 50
```

Mensajes clave a buscar:
- `menú cargado desde bot_config para slug=<slug>` → el menú se cargó bien (el problema es otro: credenciales, formato del mensaje).
- `bot_config vacío/inválido para slug=<slug>` → la fila existe pero `menu_json` está vacío o no es un array → re-sembrar.
- `No se pudo obtener configuración para db=<db>` → ni `bot_config` ni el fallback `ws/dev_tenant_config*.json` dieron menú.
- `tenant identificado: db=...` ausente → el webhook no matcheó el `whatsapp_phone_id` contra ningún tenant `activo` (volver al paso 2).

---

## Re-sembrar el menú de un tenant

Aplicar el seed default a la DB del tenant (crea la tabla si falta e inserta el menú default solo si no hay fila):

```powershell
docker exec -i mysql8 mysql -uroot -proot wb_<slug> < _docker/mariadb/bot_config_seed.sql
```

> El seed usa `INSERT ... WHERE NOT EXISTS`, así que **no pisa** un menú ya existente. Para forzar el reemplazo (menú corrupto), primero borrar la fila:
> ```powershell
> docker exec mysql8 mysql -uroot -proot wb_<slug> -e "DELETE FROM bot_config WHERE id=1;"
> ```
> y volver a aplicar el seed.

Verificar después con el SELECT del paso 1 (`ok=1`, `items>0`).

---

## Editar el menú (uso normal)

- **Cliente/operador:** desde el panel Atiende → Configuración (edita los textos de las opciones de los menuId 100/200). Eso escribe en `wb_<slug>.bot_config` vía `ajax/configuracion.php` (`saveMenuPrincipal`).
- **Alta de tenant nuevo:** la siembra inicial la hace `pedidos-platform` al provisionar (lee `templates/bot/default_menu.json` e inserta en `wb_<slug>.bot_config`).

---

## Notas de arquitectura

- La estructura del menú principal vive en `wb_*.bot_config.menu_json` (JSON, array de entradas).
- Los submenús de reclamos/consultas (`menuitem`, `motivo_consultas`) son tablas aparte que BotEngine inyecta en los menuId hardcodeados (1 y 300) — coexisten con `bot_config`.
- `bot_config.telefono` lo escribe `pedidos-platform/superadmin/tenant_save.php` al guardar el `phone_id`; lo lee `config/global.php::getWebMasterConfig()`.
- La base `axbot` fue eliminada (jun 2026). Si algún código o script vuelve a referenciarla, es un bug de regresión.
