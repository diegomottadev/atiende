# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Atiende** (formerly Atiende / Atiende) — A multi-tenant B2B/B2C WhatsApp-integrated order and complaint management system built in PHP, running on Docker (PHP-FPM + Nginx + MariaDB).

## Development Setup

```bash
# Primera vez (o reset completo):
cp .env.example .env          # ajustar HOST_IP si es Linux
./startup.sh                  # build, levanta containers, inicializa DBs, permisos

# Levantar sin resetear DB:
docker compose up -d

# Apagar:
docker compose down
```

`startup.sh` hace todo automáticamente: copia `config/global.php` desde `config/global.docker.dev`, crea archivos placeholder, levanta los 3 containers (app/nginx/mariadb), espera healthcheck de MariaDB, resetea e importa las bases de datos, y aplica permisos.

No test suite or linter is configured in this project.

**Siempre usar Docker, nunca xampp.** Correr PHP, Composer y PHPUnit dentro de los containers (`docker compose run --rm` / `docker compose exec`), no con el binario local. El xampp del PATH es PHP 7.4.1 y no sirve para herramientas que requieren PHP ≥ 8.1 (ej. PHPUnit 10 en `pedidos-platform`).

## Environment Configuration

Config files are git-ignored. Templates live in:
- `config/global.docker.dev` — development env vars
- `config/global.docker.prod` — production env vars

Key environment variables:
| Variable | Purpose |
|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD` | MariaDB connection |
| `WEB_MASTER` | External Atiende API endpoint |
| `__PROD__` | Main app URL |
| `__WS__` | WebSocket server URL (`ws://` or `wss://`) |
| `__PROD__VIEW` | Order view URL |
| `__FTP__` | FTP flag for file operations |

Default dev DB credentials: `root/root`. Database name: `atiende`.

## Architecture

### Entry Points
- `index.php` → redirects to `vistas/login.php`
- Login is handled by `ajax/usuario.php?op=verificar`, which authenticates the tenant against `pedidos_platform.tenants` (the old `axbot` login path was removed)

### Directory Structure

| Directory | Role |
|---|---|
| `modelos/` | PHP model classes — all business logic |
| `ajax/` | ~30 AJAX endpoint handlers (CRUD, exports, notifications) |
| `vistas/` | HTML view templates (`header.php`, `footer.php` are shared layout) |
| `ws/` | WebSocket/real-time chat handlers (`post.php`, `npost.php`) |
| `config/` | DB connection classes and env config (git-ignored) |
| `public/` | Static assets — CSS, JS, vendor libraries |
| `_docker/` | Docker configs and DB schema/seed SQL |
| `fpdf181/` | FPDF library for PDF generation |
| `PHPExcel/` | Excel export library |

### Data Flow

1. User authenticates → `vistas/login.php` → session established
2. Dashboard (`vistas/escritorio.php`) loads analytics via `modelos/Consultas.php`
3. UI actions hit `ajax/` handlers which call model methods
4. WhatsApp messages flow through `ws/post.php` and `ws/npost.php` via the external `WEB_MASTER` API
5. Real-time updates use WebSocket (`__WS__` env var points to the WS server)

### Key Models

- `Persona.php` — Client management (B2B and B2C modes)
- `Reclamo.php` — Complaint handling with WhatsApp messaging
- `Consulta.php` / `Consultas.php` — Inquiries and analytics
- `MensajeB2B.php` / `MensajeB2C.php` — Channel-specific messaging
- `Articulo.php` — Product catalog
- `Reparto.php` — Delivery/distribution

### Multi-Tenant & External Integrations

- Tenant configuration is fetched from the external `WEB_MASTER` API at runtime
- MercadoPago payment gateway used in `pedidos/fin.php` and `pedidos/finbis.php`
- MapboxGL (`public/mapboxgl/`) for geographic visualization in `vistas/mapa.php`
- WhatsApp integration is handled entirely through `WEB_MASTER` API calls inside the `ws/` handlers

### Multiple Production Environments

There are company-specific startup scripts (`startup-prod-campostrini.sh`, `startup-prod-faustina.sh`, `startup-prod-termoplastica.sh`) — each points to a different `WEB_MASTER` endpoint and URL set.

## UI Conventions (vistas/)

Modern card pattern applied to ALL list/CRUD modules **except `mapa.php`** (which has its own `.filtro-bar` layout, left as-is):

```html
<div class="card sombra-panel" style="border-top:3px solid #727cf5;">
    <!-- Filters ONLY if the page has them: -->
    <div class="card-body pb-2"> ...filter row (row g-2 align-items-end)... </div>
    <hr class="my-0">
    <!-- Table section (always first if no filters — no empty card-body before it): -->
    <div class="card-body p-0">
        <div class="table-responsive" id="listadoregistros"> <table id="tbllistado">...</table> </div>
    </div>
    <div class="card-body px-3 pb-3" id="formularioregistros"> ...form... </div>
</div>
```

- Table header CSS per file: `#tbllistado thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }`
- Old `ribbon` divs replaced by `<h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">`.
- select2 height fix: single `height:31px;line-height:29px`; multiple `max-height:31px;overflow:hidden` to stop vertical growth.
- Spinners: SweetAlert2 `Swal.fire({title:'...', didOpen:()=>Swal.showLoading()})` during async sends; close with `icon:'success'`/`'error'`.
- DataTables stat chips (e.g. `ventasfechacliente.php`) are set with `.text()`, not `.val()`.
- Action buttons say **"Nuevo"**, not "Agregar" (the one exception is "Agregar Articulos" in `ingreso.php`). Export button lives **outside** the card in a `row mb-2 mt-n4` block above it.
- **Excel import = upload-zone component**: dashed-border drop area, filename display, clear button, submit disabled until a file is chosen. Reference impls: `cliente.php`, `articulo.php`, `vendedor.php`, `repartidores.php` (CSS in each `<style>` block + inline `change`/`clearUpload` script).
- **Tooltips: always `{trigger:'hover'}`** (or `data-bs-trigger="hover"`). The Bootstrap default `'hover focus'` leaves tooltips stuck open after a click.
- `mapa.php` uses **map-as-hero**: single card, `row g-0`, `col-lg-4` sidebar (filters + scrollable list) + `col-lg-8 p-0` map, heights synced via `calc(100vh - Xpx)`; `map.on('load', ()=>map.resize())` + window resize handler.
- `ajax/usuario.php` (case `'permisos'`) filters the permission checkbox list by `$_SESSION[$sessionKey]` so a tenant only sees the permissions it has enabled (`idpermiso → session key` map is inline there).
- **Never touch** the Bootstrap 3 legacy blocks (`if ($_SESSION['x'] == 10)`) — they coexist with the BS5 `== 1` blocks and must stay intact.

## pedidos/index.php — Customer Order Page

Compact fopaci-style product list. Key patterns:

- Unicons icon library (`uil-*`) available via `public/assets/css/icons.min.css` (already loaded in the template).
- Product rows use `.prod-actions` sidebar div with `.btn-agregar` (shows "Agregar"); once quantity > 0 the row gets `.item-activo` class (purple left-border highlight).
- Cart table: numbered lines (`lineNum` counter), `.001`-coded products rendered as a `colspan=5` note row (observation), not a product row.
- Bottom bar shows product count (`#barProdCount`) alongside the total.
- Spinner uses Bootstrap `spinner-border` component, not a GIF.
- Favorites `<option>` styled purple+bold; star button requires null-check on `getElementById('img'+cod)` before setting `.src`.

## BotEngine Menu Structure (wb_*.bot_config)

- Bot menu JSON now lives in each tenant's own database, table `wb_*.bot_config` column `menu_json` (single row, `id=1`). It is read by `ws/webhook.php` and `ajax/configuracion.php`, and seeded from `_docker/mariadb/bot_config_seed.sql`.
- BotEngine **hardcodes** `menuId '1'` for `menuitem` injection and `menuId '300'` for `motivo_consultas` injection.
- `corp` tenant menu IDs: 101=client-code prompt, 200=main menu (1→pedido/350, 2→reclamo/1, 3→consulta/300, 4→consultar reclamo/400, 5→salir/2.2), 300=consultas gateway, 350=link pedido, 15=free-text consulta capture (`accion='registrarConsulta'`).
- `registrarConsulta` action reads `$_anterior` (saved by BotEngine from the previous menu step) — this only works if `motivo_consultas.guardar=1` for that record.
- Reparto WhatsApp message format: `"N# - product_name (qty)"` with `.001`-coded lines filtered out entirely.
- **Menú solo ante saludo:** cuando `menu == '0'` (estado inicial / tras una baja), BotEngine solo muestra el menú si el mensaje es un saludo (`esSaludo()`: hola, buenas, buenos días, menú, etc.). Cualquier otro texto en estado inicial → sin respuesta. Evita que el bot re-salude ante mensajes sueltos.
- **Palabras clave solo al inicio:** el `buscarMenuClave` (atajos `pedido`/`comprar`) solo aplica si `esperaRespuesta == '0'`. Mientras el usuario escribe un detalle (reclamo/consulta), su texto se toma literal — así "mi pedido no llegó" NO salta al flujo de pedidos.
- **Baja / opt-out:** palabras `BAJA`/`STOP`/`CANCELAR`/`DESUSCRIBIR` (coincidencia exacta del mensaje, + `bajacp` legacy) → el bot pide confirmación `SI` (estado `{"type":"baja_pendiente"}` en `contactos.anterior`) → al confirmar borra de `telefonos` + resetea `contactos`. Re-suscripción con `hola`. La eliminación de datos por privacidad es aparte (`eliminar-datos.php`).
- **Números sin padding:** `Reclamo N°` / `Consulta N°` se muestran con el id real (`intval`), sin los ceros de `sprintf('%08d')` (que se quitó).
- Changelog detallado de mejoras del bot: `docs/CAMBIOS-2026-06-02.md`.
- **Si el bot no carga el menú**, seguí el runbook de diagnóstico: `docs/runbook-bot-no-carga-menu.md`.

## WhatsApp Cloud API Integration (direct, per-tenant)

Newer path (alongside the legacy `ws/` + `WEB_MASTER` flow): direct Meta Graph API via `config/WhatsAppClient.php` (Guzzle).

- Credentials are **per-tenant**: loaded from `pedidos_platform.tenants` for `wb_*` DBs inside `config/Conexion.php` (session `tenant_db` routing).
- `WhatsAppClient` methods return arrays: `['ok'=>true]` or `['ok'=>false,'error'=>msg]`. `send()` uses `http_errors:false` and surfaces Meta's error message. Missing creds → `['ok'=>false,'error'=>'Credenciales WhatsApp no configuradas para este tenant']`.
- `sendText` uses `preview_url:true` (auto-previews links). `sendLocation($to,$lat,$lng,$name='',$address='')` sends `type:'location'`; **omit name/address when empty** — any value makes WhatsApp render a Google-Maps-search pin instead of the native dropped pin.
- `normalizePhone()`: Argentine wa_id `549XXXXXXXXXX` → `54XXXXXXXXXX` (strip the mobile `9`); all other countries used as-is. **Rule: Meta webhooks deliver AR numbers with the mobile `9` inserted after the country code; the Cloud API send endpoint rejects that format. Never add `15` (old PSTN prefix) — that was the bug.**
- `ajax/send_wa.php` is the JS entry point. Reads `type` BEFORE validation; only `text` is required for non-location types (location POSTs have no text). Echoes the real result array so the JS Swal can show success/error.
- Reparto sends (`vistas/scripts/reparto.js`): `_buildRepartoPayload()` builds summary text + `ubicaciones[]`; `_enviarRepartoWA()` posts text then locations; `reenviarMensaje(pedidoid)` re-sends per row (button shown only when `fecha_notificacion !== null`).

## Key Decisions

| Decision | Rationale |
|---|---|
| App renamed **Atiende** | Brand decision — all user-facing references use Atiende; codebase dirs unchanged |
| Location pin sent with empty `name`/`address` | Any text turns the native WhatsApp pin into a Maps-search URL |
| `_docker/mariadb/atiende.sql` stripped of business `INSERT`s | New tenants must start with clean data; only schema + config rows kept |
| Checkbox moved to dedicated first column in `repartos.php` | Cleaner DataTables layout; shifted all column indices (cols 0–13) |
| WA credentials read from `pedidos_platform.tenants` | Multi-tenant isolation — each `wb_*` DB has its own Meta phone/token |
| `corp` tenant: menuId 300=consultas gateway, 350=link pedido | menuId 300 is hardcoded in BotEngine for `motivo_consultas` injection — freeing it was required to add the "Hacer una consulta" option |
| `motivo_consultas.guardar` must be `1` | BotEngine only saves to `contactos.anterior` when `guardar=1`; without it `registrarConsulta` receives a null `$_anterior` and silently fails |

## Gotchas

- After moving a DataTables column, update **every** `columnDefs` index (listar + filtrar) — a stale index throws `Requested unknown parameter 'N'`.
- `repartos.php` thead must have exactly 14 `<th>` (0–13); a duplicate `Sel.`/`Msj` header was the cause of the `parameter '14'` error.
- Pages without filters must NOT have a leading empty `<div class="card-body pb-2"></div><hr>` — it renders a blank white band above the table.
- `articulo.js` page size is `iDisplayLength: 10`.
- **Bootstrap tooltip + DataTables race condition.** When DataTables redraws, it removes DOM elements mid-CSS-animation; Bootstrap tries to access the now-null element and throws, which then cascades into DataTables. Fix: `animation: false` on every `new bootstrap.Tooltip(...)`, plus `try/catch` around `.hide()→.dispose()→new Tooltip` in both `drawCallback` and static init. See `vistas/scripts/reparto.js`.
- **Bot menu lives in `wb_*.bot_config.menu_json`.** The admin UI (`ajax/configuracion.php` `saveMenuPrincipal`) only edits the option texts of the editable menuIds (100/200) — it does NOT overwrite the full menu JSON. To re-seed a tenant's default menu, apply `_docker/mariadb/bot_config_seed.sql` to its `wb_<slug>` DB. **Diagnóstico de "el bot no carga el menú": `docs/runbook-bot-no-carga-menu.md`.**
- **Meta test-mode error #131030.** While the WhatsApp app is in Development mode, only pre-approved numbers receive messages. Add test numbers at Meta Developer Portal → WhatsApp → Configuration → Test recipients. The number must be entered **without** the mobile `9` (e.g. `+54 376 427 8402`, not `+54 9 376 427 8402`).
- **New `wb_*` tenant DBs start empty.** Tables `menuitem`, `motivo_consultas`, `areas_consultas`, `areas` are not seeded by the default SQL dump. Copy from `atiende` and fix any `area` FK references before the bot will function. Note: `menuitem`/`motivo_consultas` still exist and hold the submenu/consultas options that BotEngine injects into the hardcoded menuIds (1 and 300), while the main menu STRUCTURE now lives in `wb_*.bot_config.menu_json` — both coexist. A new tenant's bot needs BOTH seeded: `bot_config` (via `bot_config_seed.sql`) AND `menuitem`/`motivo_consultas`/`areas`.
- **`pedidos/index.php` favorites star crash.** `document.getElementById('img' + cod)` is null when the product modal has never been opened in that session. Always null-check before setting `.src`.
