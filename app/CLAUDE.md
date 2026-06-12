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
- **List filter bar** (canonical impl `cliente.php`/`venta.php`): `card-body pb-2` → `row g-2 align-items-end` with a tiny uppercase label per control (`font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d`), a search input-group (`#fBuscar` + `mdi-magnify`), `form-select-sm` dropdowns, and a `#fLimpiar` clear button — then `<hr class="my-0">` before the table. Give the filter row its own id (`#filtrosVenta`/`#filtrosCliente`) and **hide/show it in `mostrarform()`** alongside `#listadoregistros`, so it disappears on the detail form. JS wiring: debounced `tabla.search(v).draw()` for global search; `tabla.column(idx).search(val).draw()` per dropdown (badge columns match by substring of the rendered HTML text; plain-text cols like Vendedor use an escaped `^val$` regex). Populate dynamic dropdowns from distinct cell values in `initComplete` via `api.column(idx).data()`.
- Action buttons say **"Nuevo"**, not "Agregar" (the one exception is "Agregar Articulos" in `ingreso.php`). Export button lives **outside** the card in a `row mb-2 mt-n4` block above it.
- **Excel import = upload-zone component**: dashed-border drop area, filename display, clear button, submit disabled until a file is chosen. Reference impls: `cliente.php`, `articulo.php`, `vendedor.php`, `repartidores.php` (CSS in each `<style>` block + inline `change`/`clearUpload` script).
- **Tooltips: always `{trigger:'hover'}`** (or `data-bs-trigger="hover"`). The Bootstrap default `'hover focus'` leaves tooltips stuck open after a click.
- `mapa.php` uses **map-as-hero**: single card, `row g-0`, `col-lg-4` sidebar (filters + scrollable list) + `col-lg-8 p-0` map, heights synced via `calc(100vh - Xpx)`; `map.on('load', ()=>map.resize())` + window resize handler.
- `ajax/usuario.php` (case `'permisos'`) filters the permission checkbox list by `$_SESSION[$sessionKey]` so a tenant only sees the permissions it has enabled (`idpermiso → session key` map is inline there).
- **Never touch** the Bootstrap 3 legacy blocks (`if ($_SESSION['x'] == 10)`) — they coexist with the BS5 `== 1` blocks and must stay intact.
- **Edit the shared global `<style>` block, not 15 vistas.** System-wide UI rules live in the `<style>` in `vistas/headerv1.php` (BS5 views) and `vistas/header.php` (legacy: comprasfecha/permiso/escritorioAnterior). One rule there hits every view that `require`s that header.
- **Uniform table width** = global CSS in both headers: `#tbllistado, table.dataTable { width:100% !important }` + `.dataTables_wrapper, #tbllistado_wrapper, .table-responsive { width:100% }`. Needed because DataTables `autoWidth` (default on) fixes a px width from content, so tables with few/short columns rendered narrower than `articulo.php`. The `!important` overrides DataTables' inline px width → all lists span the card.
- **Topbar profile position** (avatar + name): markup is `<ul class="topbar-menu float-end">` so `float-end` pins it right; `.navbar-custom .nav-user { margin-right: Npx }` in `headerv1.php` nudges it. Bigger margin = further LEFT, `0` = flush against the right edge. (`.navbar-custom { padding-right:0 }` removes the theme's own gap.)
- **Shadows — 3 distinct sources, don't confuse them** (all in `headerv1.php`): `.sombra` (heavy `5px 5px`) was the class on topbar+sidebar+profile-dropdown; `.sombra-panel` = card elevation, now a flat hairline (`0 0 0 1px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.06)`); and the **Hyper theme itself** sets `box-shadow: var(--ct-box-shadow)` on `.navbar-custom` (app.css) independent of any class. To flatten the chrome you must kill all three: remove the `sombra` class AND override `.navbar-custom`/`.leftside-menu { box-shadow:none !important }`.
- **Profile dropdown** (`headerv1.php`): user-header (`$img` avatar + `$_SESSION['nombre']` + "Sesión activa") → `dropdown-divider` → "Salir" left-aligned in danger red `#fa5c7c` with tinted hover. Elevation via `.profile-dropdown` subtle shadow (hairline + `0 4px 16px`) + `border-radius:8px`. `$_SESSION` has **no reliable role field** — don't add account links without a real destination page. (Optional follow-ups: a "Mi perfil" page to link here; AA-strict red `#e23a5e` for Salir.)
- **Empresa data** lives in `bot_config` (singleton `id=1`): `nombre_empresa, razon_social, cuit, telefono, logo`. Edited in the **Empresa tab** of `configuracion.php` (`getEmpresa`/`guardarEmpresa` in `ajax/configuracion.php`); `guardarEmpresa` also syncs `nombre/razon_social/cuit/telefono` → `pedidos_platform.tenants`. `getEmpresa` falls back to `tenants.nombre` when `nombre_empresa` is empty. The **ticket** (`pedidos/ticket_pdf.php`) header renders this empresa data (logo centered + nombre + razón social · CUIT · Tel, each only if present) — note the ticket body's `razonSocial` is the **client's** (from `clientes`), a different thing.
- **"Guardar" deshabilitado hasta modificar** (forms de edición): en `mostrarform(true)` poner `$("#btnGuardar").prop("disabled",true)` + bind `$('#formulario').off('input.gd change.gd').on('input.gd change.gd','input,select,textarea',()=>$('#btnGuardar').prop('disabled',false))` (namespace `.gd` + `.off().on()` = no acumula al reabrir). `.val()` programático NO dispara el evento → si no tocás nada queda bloqueado (no se guarda un no-op). Aplicado a cliente/vendedor/usuario/articulo/area/areaConsulta/motivo/motivoConsulta/reclamo (form `#formulario`) y consulta (campo único `#resolucion`). En `cliente.js`, `#btnNuevo` se oculta en `mostrarform` al entrar a edición.
- **Cursor "prohibido" en botones `disabled`**: regla global en `headerv1.php` → `.btn:disabled,button:disabled{pointer-events:auto!important;cursor:not-allowed!important}`. Bootstrap les pone `pointer-events:none` (por eso no se veía cursor); el atributo `disabled` igual bloquea el click aunque se reactiven los pointer-events.
- **Tablas DataTables colapsables SOLO en mobile**: en cada init, `responsive: window.matchMedia('(max-width: 991.98px)').matches` (plugin `responsive.bootstrap5` ya cargado en `footerv1.php`/`headerv1.php`). Se evalúa al cargar → desktop = todas las columnas; <992px = colapsa en fila `+`. Aplicado a cliente/venta/ventasfechacliente/reclamo/consulta/usuario/articulo/reparto(lista principal). Viewport sin `user-scalable=no` (zoom habilitado).
- **Menú mobile (off-canvas Hyper) pulido** (`headerv1.php`, `@media ≤991.98px`): hamburguesa estilada, backdrop vía `body.sidebar-enable::after`, cierre al tocar fuera (handler vanilla en `footerv1.php` que quita `sidebar-enable`), ficha de usuario + "Salir" dentro del sidebar (`d-lg-none`). **Fix crítico (`@media ≤767.98px`): `.content-page{padding:64px 12px 60px!important}`** — el topbar es `position:fixed` y el tema deja el content en `padding:0 0 60px 0` (top 0 → tapa breadcrumb/botones; lados 0 → `overflow:hidden` recorta botones/sombras). Además `.row.mt-n4{margin-top:.25rem}` y la barra de acciones (`.row.mt-n4 .text-sm-end`) a `flex-wrap; justify-content:flex-end` con `.btn{float:none}` para que no se corten.
- **`cliente.php` form de edición con solapas (`#clienteTabs`)**: "Editar Cliente" (datos en secciones `.form-section`) + "Vendedor asignado". El `<select name="vendedor" id="vendedor">` se puebla con `ajax/vendedor.php?op=selectVendedores` (**value = `vendedores.codigo`**, misma convención que `Vendedor::asignarCliente` → no hubo que tocar el guardado: `Persona::editar` ya persiste `vendedor`). Caja-info espejo de `vendedor.php` (`#vendedorAsignadoTexto` + `sincronizarVendedorLabel()`). Un cliente = un vendedor (`clientes.vendedor` es una columna; el UPDATE pisa al anterior → reasignar desasigna solo). Label del primer campo es **"Razón Social"** (era "Nombre"; mapea a `data.razonSocial`).

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
- **Visibilidad de opciones del menú (flag `activo`):** cada `menuItem` numerado de los menús editables (menuId `200`=principal, `100`=identificación) puede llevar `"activo":"true"|"false"` dentro de `bot_config.menu_json`. Item **sin** la clave = visible (retrocompat, sin migración ni ALTER). Editable desde la pestaña **Menú Principal** de `configuracion.php` (switch "Visible" por fila).
- **`BotEngine::proyectarMenu($menuItem)` es el punto único de verdad** para qué opciones se muestran y con qué número. Filtra las ocultas (`activo==='false'`) y **renumera 1..N al vuelo**. Se consume IGUAL en el bucle de **display** y en el de **match** → el número que ve el usuario es el que matchea; nunca quedan huecos (1,3,4).
- **"Salir" se detecta por su `menuId` destino `"2.2"`** (no por el literal "Salir", así tolera renombre): nunca se oculta, `activo` se fuerza a "true" y siempre va **último** en la proyección.
- Changelog detallado de mejoras del bot: `docs/CAMBIOS-2026-06-02.md`, `docs/CAMBIOS-2026-06-05-menu-visibilidad.md`.
- **Si el bot no carga el menú**, seguí el runbook de diagnóstico: `docs/runbook-bot-no-carga-menu.md`.
- **Flujo de vendedor (identificación por código + número).** El menú `100` tiene la opción **"Soy Vendedor"** → menú `105` (acción `registraVendedor`: valida `vendedores.codigo` **Y** que el número que escribe (`$user`) coincida con `vendedores.telefono` de ese código — *un número no registrado no accede aunque sepa un código válido*; mensaje de error genérico para no filtrar códigos. Requiere que `vendedores.telefono` esté en formato WhatsApp `549…` —el ABM de `vendedor.php` ya lo normaliza así—; vendedores importados por Excel con teléfono en otro formato deben normalizarse o no podrán identificarse. Guarda el vínculo en `contactos.vendedor_codigo`) → menú `106` (acción `chequearVendedorCliente`: pide el **código de cliente**, valida `clientes.codigo … AND vendedor=<sesión>`, con **reintento** en el mismo paso si es incorrecto —"Codigo de cliente ingresado incorrecto intente nuevamente"— y palabra clave **`SALIR`** para cerrar sesión) → menú del link de pedido. La identidad de vendedor **persiste** en `contactos.vendedor_codigo`: en `procesarAccion`, si ese campo está seteado, el saludo rutea directo al `106` (el vendedor reconocido no re-ingresa su código). **No se pisa `vendedores.telefono`** (es el contacto del vendedor para notificaciones); la sesión vive en `contactos`. La atribución del link de pedido usa el vendedor de sesión (`vendedores WHERE codigo=<sesión>` + `vendedores.atencion`); el path legacy por `telefono=$user` se conserva como fallback. Spec/plan: `docs/superpowers/{specs,plans}/2026-06-10-vendedor-bot-identificacion*`.
- **El menú destino del link de pedido VARÍA por tenant**: `350` en demo/corp, `300` en el seed default. La migración `_docker/migrations/2026-06-10-vendedor.php` lo **detecta por el placeholder `<linkPedidos>`** y cablea ahí la captura del menú `106` (no se hardcodea). Por eso el seed (`bot_config_seed.sql`) tiene `106 → 300` y demo `106 → 350`, ambos correctos.

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
| `razon_social` added to `bot_config` + `tenants` | Empresa tab & ticket need the legal name separate from `nombre_empresa`; synced to platform `tenants` for superadmin visibility |
| Topbar/sidebar/cards flattened (no shadow) | User preference — flat chrome; card separation now carried by the `#727cf5` border-top |
| Ticket opens via pretty URL `/ticket/{id}` | nginx rewrite already in `_docker/nginx/default.conf` (`/ticket/(\d+)`→`/reportes/exTicket.php?id=$1`). Both the row-action button (`$url='/ticket/'` in `ajax/reparto.php` & `ajax/venta.php`) and the detail `#imprimir` (`reparto.js`/`venta.js`) use it |
| SweetAlert confirm button says "Aceptar" globally | `Swal.fire` monkey-patched in `footerv1.php` (+ `login.php`) to inject `confirmButtonText:'Aceptar'` when the call doesn't set it — avoids editing 250+ call sites; never overrides calls that already define their own text/Cancelar |
| Visibilidad de opciones vía flag `activo` en `menu_json` (sin schema nuevo) | El menú ya vive como JSON en `bot_config`; agregar un flag por item evita ALTER, migración y tabla extra, y mantiene retrocompat (item sin flag = visible) |
| Filtros de `venta.php` corren **client-side** (`aServerSide:false`), no en SQL | `ajax/venta.php?op=listar` ya devuelve TODOS los pedidos e ignora los params de filtro; pasar la tabla a client-side hace funcionar `column().search()` y el filtro de rango en el navegador **sin tocar `Venta.php` ni el backend** (riesgo cero en la lógica de listado) |
| `proyectarMenu()` único, consumido por display **y** match | Renumerar en un solo lugar elimina la clase de bug "ves 2 pero el bot espera 3": display y match leen la MISMA proyección 1..N |
| "Salir" identificado por destino `menuId "2.2"`, no por texto | Tolera que el operador renombre la opción; permite forzarla visible + última sin depender del literal |
| Guardrail "≥1 visible" validado en backend (`saveMenuPrincipal`) | El front puede mandar un subconjunto; el backend recorre el entry completo, así no se puede burlar y dejar un menú vacío |
| `ventasfechacliente` filtra por `p.id` (PK de `clientes`), no `v.clienteId` | El `<select>` Cliente manda la PK; en b2b `pedidos.clienteId` guarda el **código**, así que `v.clienteId='<PK>'` daba **0 filas**. `p.id` (vía JOIN a `clientes p`) matchea en b2b y b2c |
| Tablas responsive **solo en mobile** (`matchMedia(<992px)` al cargar) | No colapsar columnas en desktop con tablas anchas; el plugin ya estaba cargado y `responsive:true` plano colapsaba también en desktop si la tabla excedía el ancho |
| "Guardar" gateado por modificación del form | Evita re-guardar un no-op accidental; se habilita ante cualquier cambio (incluido el selector de vendedor en `cliente.php`) |

## Gotchas

- After moving a DataTables column, update **every** `columnDefs` index (listar + filtrar) — a stale index throws `Requested unknown parameter 'N'`.
- `repartos.php` thead must have exactly 14 `<th>` (0–13); a duplicate `Sel.`/`Msj` header was the cause of the `parameter '14'` error.
- Pages without filters must NOT have a leading empty `<div class="card-body pb-2"></div><hr>` — it renders a blank white band above the table.
- `articulo.js` page size is `iDisplayLength: 10`.
- **Bootstrap tooltip + DataTables race condition.** When DataTables redraws, it removes DOM elements mid-CSS-animation; Bootstrap tries to access the now-null element and throws, which then cascades into DataTables. Fix: `animation: false` on every `new bootstrap.Tooltip(...)`, plus `try/catch` around `.hide()→.dispose()→new Tooltip` in both `drawCallback` and static init. See `vistas/scripts/reparto.js`.
- **Bot menu lives in `wb_*.bot_config.menu_json`.** The admin UI (`ajax/configuracion.php` `saveMenuPrincipal`) edits the option texts **and the `activo` (visibility) flag** of the editable menuIds (100/200) — it does NOT overwrite the full menu JSON. To re-seed a tenant's default menu, apply `_docker/mariadb/bot_config_seed.sql` to its `wb_<slug>` DB. **Diagnóstico de "el bot no carga el menú": `docs/runbook-bot-no-carga-menu.md`.**
- **`proyectarMenu()` solo aplica a menús cuyos items llevan (o pueden llevar) `activo`** — los editables `{200,100}`. Los menús que BotEngine **inyecta** (menuId `1`=motivo_reclamos, `300`=motivo_consultas) NO pasan por la proyección y quedan intactos. La **captura de texto libre** (`opcionId` vacío) tampoco se filtra ni renumera: se conserva tal cual dentro de la proyección.
- **DOS pantallas / DOS tablas para los motivos de reclamo — no confundirlas.** (1) `vistas/motivo.php` → tabla `menuitem` → la lee el **bot LEGACY** `ws/post.php`. (2) Pestaña **Reclamos** de `configuracion.php` → tabla `motivo_reclamos` → la lee el **bot ACTUAL** (`BotEngine.php`), que ya auto-numera. El `codigo`/`opcionId` debe ser **numérico**; `motivo.php` todavía hace `strtoupper()` sin validar y **acepta letras** (A,B,C…) → deuda técnica abierta. (Fix de datos aplicado por UPDATE — ver `docs/CAMBIOS-2026-06-05-menu-visibilidad.md`; la causa raíz en `motivo.php` sigue sin corregir.)
- **El test de `proyectarMenu` es una copia 1:1 del método** (`tests/BotEngineProyectarMenuTest.php`, test aislado que no instancia BotEngine). Si tocás `proyectarMenu()` en `BotEngine.php`, **actualizá el test a mano** — no comparten código fuente.
- **Meta test-mode error #131030.** While the WhatsApp app is in Development mode, only pre-approved numbers receive messages. Add test numbers at Meta Developer Portal → WhatsApp → Configuration → Test recipients. The number must be entered **without** the mobile `9` (e.g. `+54 376 427 8402`, not `+54 9 376 427 8402`).
- **New `wb_*` tenant DBs start empty.** Tables `menuitem`, `motivo_consultas`, `areas_consultas`, `areas` are not seeded by the default SQL dump. Copy from `atiende` and fix any `area` FK references before the bot will function. Note: `menuitem`/`motivo_consultas` still exist and hold the submenu/consultas options that BotEngine injects into the hardcoded menuIds (1 and 300), while the main menu STRUCTURE now lives in `wb_*.bot_config.menu_json` — both coexist. A new tenant's bot needs BOTH seeded: `bot_config` (via `bot_config_seed.sql`) AND `menuitem`/`motivo_consultas`/`areas`.
- **`proyectarMenu()`: una captura de texto libre cuyo destino es `2.2` NO es "Salir".** La captura de texto libre se identifica por `opcionId === ''` (cadena vacía, **strlen 0**), NO por `empty()` — porque el botón Salir inyectado lleva `opcionId === '0'` y `empty('0')` es `true` en PHP. Si se usa `empty()`, una captura cuyo `menuId` destino es `2.2` (detalle de **consulta** menuId 15 y **consultar-reclamo** menuId 400) cae en la rama de Salir, se le asigna un número y deja de matchear el texto libre → el bot responde *"opción no válida"* a cualquier detalle escrito. Detectar por `strlen` (igual que el bucle de match en `procesarAccion`) separa `''` (captura) de `'0'` (Salir). El test `tests/BotEngineProyectarMenuTest.php` cubre estos casos (e/f).
- **Copia del pedido confirmado al administrador** (`bot_config.admin_telefono` + `admin_envio_activo`). Configurable en la pestaña **Menú Principal** de `configuracion.php` (sección "Copia a administrador": input nº + switch), ops `getAdminCopia`/`saveAdminCopia`. El reenvío vive en **`pedidos/send_wa.php`**, branch `elseif ($ped)`: tras mandar el PDF al cliente, si el POST trae `copiaAdmin=1` **y** `admin_envio_activo=1` **y** el nº no es el del propio cliente, reenvía el **mismo PDF + caption** al admin (reusa `$pdf` antes del `unlink`). El opt-in `copiaAdmin=1` lo manda **solo** `index.php` en la confirmación → los otros callers de `send_wa.php` (reparto/venta) NO disparan copias. `indexbis.php` (legacy, manda por WebSocket, no por `send_wa.php`) no está cubierto. Guardrail backend: no se activa sin número cargado.
- **Alta/edición de clientes por formulario (`vistas/cliente.php`).** `clientes.codigo` es `varchar(255)` → admite códigos con letras (`D0001`, `0001D`). El form rutea por un hidden `#modo` ('nuevo'|'editar'): en **alta** llama a `Persona::insertar(...)` (que valida unicidad del código y completa los NOT NULL sin default — `vendedor`/`deposito`/`latitud`/`longitud`); en **edición** llama a `editar(...)` keyeado por código y el campo Código va **read-only** (es la clave referenciada por `pedidos.clienteId`/`telefonos.clienteId`; cambiarla rompería esos vínculos). Antes el alta llamaba a un `insertar` inexistente → estaba rota. **Ojo en botones que pasan el código a JS**: hay que pasarlo **entre comillas** (`onclick="mostrar(\'0001\')"`), si no JS interpreta `0001` como el número `1` y pierde los ceros. La **importación** (`ajax/subirarchivo.php`) hace `DROP TABLE clientes` + recrea (reemplaza TODO, no agrega) y lee el código como texto (letras OK; numéricos con ceros a la izquierda pueden perderlos si la celda de Excel no es texto).
- **`pedidos/index.php` favorites star crash.** `document.getElementById('img' + cod)` is null when the product modal has never been opened in that session. Always null-check before setting `.src`.
- **`bot_config` schema drifts per tenant.** Some tenant DBs have the full empresa columns (`nombre_empresa, razon_social, cuit, logo`), others only the minimal seed (`id, menu_json, telefono, updated_at` — see `bot_config_seed.sql`). `getEmpresa`'s `SELECT nombre_empresa,...` errors on minimal DBs. When adding an empresa column, `ALTER` **every** `atiende_*` tenant DB (e.g. `atiende_corp`, `atiende_demo`, `atiende_demo_1`), not just one — the base `atiende` DB has no `bot_config` at all.
- **Topbar shadow is the theme's, not a class.** `.navbar-custom` gets `box-shadow: var(--ct-box-shadow)` from `app.css` regardless of classes; removing the `.sombra` class isn't enough — override with `box-shadow:none !important`.
- **Docker/Git-Bash gotchas.** The app root inside the `atiende-app` container is `/var/www/atiende` (NOT `/var/www/html`). DB is the `mysql8` container (`root/root`). Git Bash rewrites absolute container paths (`docker exec ... /var/www/...` → `C:/Program Files/Git/var/www/...`); prefix the command with `MSYS_NO_PATHCONV=1`.
- **Repartos/Ventas action buttons exist in TWO places.** The print, "Cambiar estado" (cog) and message buttons render both in the **row Acciones column** (built server-side in `ajax/reparto.php`/`ajax/venta.php` `listar`) AND in the **order-detail form** (`#imprimir`/`#cambiarEstado` spans, filled by `reparto.js`/`venta.js` `mostrar()`). Change BOTH when editing one. The detail form's fields are empty unless a pedido is open — to show pedido data in a message, return it from the backend, don't read the form.
- **`Reparto::editarEstado` always sets `flag=2`** (not a toggle), unlike `Venta::editarEstado` which is `flag=IF(flag=2,0,2) WHERE flag<>3`. So clicking "Cambiar estado" on an already-delivered reparto looks like it does nothing.
- **DataTables sort arrows are Unicode `↑`(:before)/`↓`(:after), NOT MDI glyphs** (see `public/assets/css/vendor/dataTables.bootstrap5.css` `content:"↑"/"↓"`). Global override in `headerv1.php`: hide all `:before`, use a single `:after` with explicit `content` per state (unsorted `↓` faint @ opacity .35, asc `↑`, desc `↓`), `font-size:.7rem`, positioned left of the label. Don't mix display/opacity across before+after — that's what left the asc `↑` invisible.
- **Uniform table ROW HEIGHT** = global CSS in `headerv1.php` (sibling of the width rule): forces `.25rem` top/bottom padding + `vertical-align:middle` on `#tbllistado`/`table.dataTable` cells + `td img{max-height:38px}`, so every list matches `articulo.php` (table-sm) regardless of whether the `<table>` has `table-sm`.
- **Bootstrap tooltips need the `data-bs-toggle="tooltip" data-bs-trigger="hover"` attributes** — a bare `title=` only triggers the slow native browser tooltip. The clear-filter buttons (`#fLimpiar*`, tooltip text "Borrar filtros") across all list vistas use these; the global init lives in `footerv1.php`.
- **Date-range filters: the cell and the `<input type="date">` use DIFFERENT formats.** The `Fecha` column in `venta.php` renders `DD/MM/YYYY HH:MM hs` but `<input type="date">` returns `YYYY-MM-DD` → a raw string compare never matches. Normalize the cell to ISO in the `$.fn.dataTable.ext.search.push` filter (regex-extract `DD/MM/YYYY`→`YYYY-MM-DD`, also accept already-ISO) before comparing. Scope that `ext.search` to `settings.nTable.id === 'tbllistado'` so it doesn't break the detail/mensajes DataTables on the same page. UX: on `#fDesde` change, auto-copy it into `#fHasta` (only if Hasta is empty or earlier) and set Hasta's `min` to Desde; `#fLimpiar` must `removeAttr('min')`.
- **`Motivo::listarp` (and `MotivoConsulta`) use an INNER JOIN** `menuitem,areas WHERE areas.id=menuitem.area` → any motivo whose `area` FK doesn't match an `areas.id` **silently disappears** from the list. The template `atiende.menuitem` ships rows with `area=9` (no such area — areas are 1–7), and new tenants' `menuitem` is empty → when motivos don't show, check (a) the table isn't empty and (b) every `area` FK is valid. Seed with valid FKs.
- **Charset is utf8mb4 end-to-end** (conn in `Conexion.php` via `SET NAMES`, `Connection.php` via `mysqli_set_charset`, + columns) → `¿`/accents in motivos are safe for both the app UI and the bot output. No mojibake risk when inserting Spanish punctuation.
- **`app/` está bind-montado en `atiende-app`** (`/var/www/atiende`): editar archivos en el host se refleja **vivo** sin rebuild — `php -l` y los tests por `docker exec` corren sobre los archivos ya editados. No hace falta reconstruir la imagen para probar cambios de PHP/JS.
- **"Costo de envío" del mensaje de confirmación es configurable por tenant** (`bot_config.costo_envio` DECIMAL + `costo_envio_activo` TINYINT). Se edita en la pestaña **Menú Principal** de `configuracion.php` (sección "Costo de envío": input monto + switch "Mostrar"), endpoints `getCostoEnvio`/`saveCostoEnvio` en `ajax/configuracion.php`. En `pedidos/index.php` e `indexbis.php` el monto se inyecta server-side y la línea **solo se emite si `costo_envio_activo=1`** (si está apagado, la línea no aparece en el mensaje). Es **solo informativo**: NO se suma al "Monto" (que sigue siendo `getTotales(pedido)`). `index.php` lee la config con `Connection` (tenant ya seteado); `indexbis.php` (legacy) la lee con una conexión mysqli **aislada** para no pisar el estado global de `Connection`. Las 2 columnas se agregaron por `ALTER` a cada DB de tenant y al `bot_config_seed.sql` (MySQL 8 **no** soporta `ADD COLUMN IF NOT EXISTS`).
- **Migraciones idempotentes por tenant en `_docker/migrations/`.** Como MySQL 8 no tiene `ADD COLUMN IF NOT EXISTS`, los cambios de schema/menú se aplican con scripts PHP idempotentes que chequean `information_schema`/`SHOW COLUMNS` antes de actuar. Se corren por tenant: `docker exec -i atiende-app php /var/www/atiende/_docker/migrations/<script>.php <db>`. Ejemplos: `2026-06-10-vendedor.php` (agrega `contactos.vendedor_codigo` + parchea `menu_json`), `2026-06-10-clientes-cuil-dni.php` (agrega `clientes.cuil`/`clientes.dni`), `2026-06-11-pais-tenant.php` (agrega `bot_config.pais` en la DB del tenant + `tenants.pais` en central). Para tenants nuevos, las columnas también están en `_docker/mariadb/atiende.sql` y el menú en `bot_config_seed.sql`. Descubrir tenants: `SHOW DATABASES LIKE 'atiende%'` (la base `atiende` no tiene `bot_config`).
- **`clientes.cuil` y `clientes.dni`** son varchar(20) **opcionales** (nullable). Se editan en el form de `cliente.php` (bloque BS5 `== 1`); el modelo es `Persona.php` (`insertar`/`editar`) y el endpoint `ajax/persona.php`. `cliente.js` los manda solos vía `FormData($("#formulario")[0])` (no hubo que tocar `guardaryeditar`). No se usan en bot/ticket/exportación (solo ABM).
- **Normalización de teléfono a formato `wa_id` por país del tenant.** El helper **`config/Telefono.php`** (`Telefono::normalizar($numero, $pais)`, lógica pura, testeado en `tests/TelefonoNormalizarTest.php`) centraliza lo que antes era el `549` hardcodeado en 3 lados (`ajax/vendedor.php`, `ajax/configuracion.php` `saveAdminCopia`, `pedidos/finaliza.php` —este lee el país de `getWebMasterConfig`). El país vive en **`pedidos_platform.tenants.pais`** (fuente de verdad, seteado en el dropdown de la pestaña **Empresa** de `configuracion.php`) y se cachea en **`bot_config.pais`** (lo lee la normalización en contexto del tenant). Mapa `Telefono::PAISES` (AR/BR/MX/UY/CL/PY/CO/PE): **solo AR** inserta el `9` móvil; el resto solo antepone el código de país. **Default `'AR'` en todo** (columnas + fallback) → los tenants existentes no cambian. La verificación de vendedor (`vendedores.telefono = $user`) se beneficia: el teléfono se guarda con el mismo helper que produce el `wa_id` entrante. Sumar un país = una línea en `Telefono::PAISES`. Quirks legacy de BR/MX (números viejos) fuera de alcance. **También editable desde el panel SUPERADMIN** (`app-tenants/superadmin/tenant.php`, form "Datos del tenant"): el `<select name="pais">` muestra `Nombre (+cc)` y `tenant_update.php` guarda en `tenants.pais` **y sincroniza `bot_config.pais`** de la DB del tenant (best-effort en try/catch). **⚠️ Drift por tenant:** el sistema lee SIEMPRE de `bot_config.pais`; tenants con `bot_config` viejo (ej. `atiende_empresa`/id 23) **no tienen la columna** → el sync falla silencioso y la app cae al default `'AR'`. Fix: correr `2026-06-11-pais-tenant.php <db>` (con `MSYS_NO_PATHCONV=1` en Git Bash). Verificar cobertura: `SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='bot_config' AND COLUMN_NAME='pais'` por cada DB `atiende_*`.
- **Panel SUPERADMIN `tenant.php` (codebase aparte `app-tenants/`, NO `app/`).** Vista de detalle de tenant en Bootstrap cards (cabecera con badge de estado coloreado + suscripción, "Datos del tenant", "Credenciales WhatsApp", "Acciones de estado", "Usuarios"). **App Secret / Access Token precargables:** el valor real descifrado va en `data-real` del input editable; el botón «Editar» llama `revealSecret()` que lo muestra y precarga (el guardado `tenant_save.php` solo reescribe si el campo viene no-vacío → reenviar el mismo valor re-cifra idempotente). Forms **sin placeholders** + `autocomplete="off"` en el form y `autocomplete="new-password"` en campos sensibles (Token/Secret/clave) para frenar el autofill del navegador. `php -l` corre con el binario local del host (este panel NO está en el container `atiende-app`).
- **`finaliza.php` ("¡Listo!") vuelve al chat del NEGOCIO.** El botón "Volver a WhatsApp" arma `wa.me/<nº negocio>` (NO del cliente: `wa.me/X` abre el chat CON X). El nº sale de `bot_config.telefono`, normalizado a internacional AR (antepone `549` si es local). Resuelve el tenant igual que `index.php` (HTTP_X_TENANT del subdominio o `?t=`, con `?:` porque Nginx setea X-Tenant=`''`). El nº real se **auto-captura**: `ws/webhook.php` persiste `metadata.display_phone_number` en `bot_config.telefono` en cada mensaje entrante (el cargado a mano puede no ser un WhatsApp válido).
- **`BotEngine` registraNumero corta en código inválido.** Si el código de cliente no existe: avisa "…escribí *Hola* nuevamente", resetea el contacto (`menu=0`) y hace `return` temprano — no sigue al menú (mismo patrón que `chequearVendedorCliente`/`registraClientes`).
- **`vistas/venta.php`** (`ajax/venta.php` + `Venta.php`): la columna **"Pagado"** (`$pagoElectronico`, col 7) muestra **"Confirmado"** cuando el pedido está Entregado (`flag=2`) — regla de display, se revierte si vuelve a Pendiente. **"Anular" es toggle**: `Venta::anular` = `flag=IF(flag=3,0,3)` (anula ↔ reactiva a Pendiente); el botón pasa el estado para que el confirm diga Anular/Reactivar.
- **Spinner al enviar pedido** (`pedidos/index.php`): SweetAlert2 loading desde que se confirma hasta navegar a `finaliza.php` (cubre guardado + `send_wa.php`); antes el overlay `#bloquea` se ocultaba antes y la pantalla quedaba quieta. Errores cierran el spinner y rehabilitan el botón.
- **Si el bot/panel "no responde" con `getaddrinfo failed: … Name does not resolve` (host `mysql8`):** el contenedor `mysql8` perdió los endpoints de red (queda "Up" pero **sin IP** y fuera de `atiende_net`), típico tras reinicio del daemon Docker/WSL → **fix: `docker restart mysql8`** (rejunta la red; datos en volumen, no se pierden). Diagnóstico: `docker network inspect atiende_net` (mysql8 no aparece) o `docker exec atiende-app getent hosts mysql8` (no resuelve).
- **Cliente sin `deposito` = NO muestra productos en la página de pedido.** `pedidos/index.php` filtra cada artículo comparando `clientes.deposito` contra el `deposito` (`|`-separado) del producto: con depósito de cliente vacío, `array_search('', […])` falla y se ocultan TODOS (síntoma "no muestra productos al vendedor" era esto, no el `/V0004` — el `ved` solo viaja en el mensaje, no filtra productos). Ahora `deposito` es **obligatorio en las 3 vías de alta**: form `cliente.php` (`required` + validación server-side en `ajax/persona.php` para alta Y edición), `Persona::editar` ahora **sí guarda** `deposito` (antes solo `insertar`), import Excel (`subirarchivo.php` col O → default `'1'` si vacío), y el bot (`registraClientes`) ya hardcodeaba `'1'`.
- **Copia del ticket confirmado al vendedor + encabezado del admin** (`pedidos/send_wa.php`, rama `elseif ($ped)`). `index.php` pasa `ved` (código de vendedor de la URL `/pedidos/{id}/{ved}`) al POST. El cliente del pedido se resuelve desde `link_pedidos.id=$ped` (= "Pedido N°" y id del ticket) JOIN `clientes` (b2b→`codigo`, b2c→`id`). `reemplazarSaludo()` cambia **solo la 1ª línea** del texto, conservando Pedido N°/Monto/Ticket. **Flujo vendedor:** el nº que confirma ES el del vendedor (la identificación exige `=vendedores.telefono`), así que recibe el mensaje en clave vendedor (*"<nombre>, el pedido de cliente <razón - código> ha sido confirmado."*). **Admin:** encabezado *"Haz recibido un pedido del cliente <razón - código>"*. **El cliente real NO recibe copia** en el flujo de vendedor: nunca le escribió al bot → la **ventana de 24h** de WhatsApp bloquea mensajes libres (haría falta plantilla aprobada de Meta).
- **Banda blanca arriba del form de edición** = la `card-body` de filtros/upload + el `<hr>` quedaban visibles cuando `mostrarform()` ocultaba solo los hijos (`#filtros*`/`#subirarchivo`). Fix: envolver esa `card-body` + el `<hr>` en un `<div id="panelLista">` y ocultar/mostrar `#panelLista` junto (impls: `usuario.php`/`#filtrosUsuario`, `cliente.php`, `vendedor.php`, `articulo.php`). **El hide del padre gana** sobre cualquier re-show del hijo, así que es el fix robusto aunque algo vuelva a mostrar `#filtros*`. Si el importador y los filtros están en **dos `card-body pb-2` apilados**, fusionarlos en uno solo (el upload mantiene su `mb-3`) elimina el doble padding/espacio en blanco.

- **`ventasfechacliente.php` devuelve 0 al filtrar por un cliente** (pero "Todos" sí trae): el `<select>` Cliente (`venta.php?op=selectCliente`) emite `value=clientes.id` (PK), pero en **b2b** `Consultas::ventasfechacliente`/`pedidosfechacliente` filtran sobre `pedidos.clienteId` que guarda el **código** → `v.clienteId='<PK>'` no matchea. Fix: filtrar por `p.id='$idcliente'` (con JOIN a `clientes p`), mismo criterio en el conteo. La tabla pasó a **client-side** (`aServerSide:false` + `ajax.dataSrc:"aaData"`) para que el buscador propio (≥3 chars) y los filtros corran en el navegador (el endpoint ya devuelve todo el set por fecha/cliente).
- **`REPLACE INTO contactos` borraba la sesión de vendedor** (`pedidos/S_Pedidos_bis.php` —el activo— y `S_Pedidos.php`). Al guardar el pedido, el `REPLACE` **borra la fila y la re-inserta** sin `vendedor_codigo` (no está en la lista de columnas) → en el flujo de vendedor el teléfono del pedido **es el del vendedor**, así que perdía su sesión y el siguiente "hola" volvía al menú de bienvenida ("se reinicia al recibir el recibo"). Fix: cambiar a `INSERT … ON DUPLICATE KEY UPDATE` (resetea `menu`/`esperaRespuesta`, **preserva `vendedor_codigo`**). Regla general: **nunca uses `REPLACE INTO contactos`** — borra columnas no listadas; usá upsert. **Salir del flujo sin baja:** la baja (`webhook`/`BotEngine` opt-out `baja/stop/cancelar/desuscribir`) ahora también limpia `vendedor_codigo`; y si quien escribe una palabra de opt-out **es un vendedor con sesión activa**, NO se da de baja → cierra la sesión de vendedor ("Cerraste tu sesión de vendedor"). La palabra `SALIR` (en el paso del código de cliente) sigue saliendo del flujo; su texto-hint se quitó del menú 106 pero la función sigue activa.
- **URL "linda" de pedido rompe los assets** (`/pedidos/{id}/{ved}`): el segmento extra (`/V0004`) hace que el navegador resuelva las rutas relativas `../public`/`../files`/`../ajax` contra `/pedidos/{id}/` → pide `/pedidos/public/…` (404) y **no carga Bootstrap** (solapas como viñetas, modales incrustados, logo roto). Con `/pedidos/{id}` (un segmento) anda. Fix: `<base href="/pedidos/">` en el `<head>` de `pedidos/index.php` (resuelve todo como si el doc estuviera en `/pedidos/`, igual que la URL de un segmento) + neutralizar el único `href='#'` (→ `javascript:void(0)`) para que el `<base>` no lo redirija.
- **`mysql` CLI por `docker exec` mutila acentos (UTF-8).** Un `UPDATE … REPLACE(menu_json, 'texto con í/ó/ñ', '')` o `LIKE '%sesión%'` desde `docker exec mysql8 mysql -e "…"` **falla en silencio** (el pipeline bash→docker→mysql corrompe los multibyte → no matchea; un `COUNT=0` de verificación engaña). Para editar `menu_json`/datos con acentos, usá un **script PHP** corto dentro del container (`Connection::runQuery` + `json_decode`/`json_encode(... JSON_UNESCAPED_UNICODE)`): PHP maneja UTF-8 nativo. Los archivos fuente (seed/migración) sí se editan bien con el editor.

## Trabajo en curso (ARCHIVABLE) — 2026-06-11

- **Rama `feat/menu-visibilidad-opciones`** (~35 commits sobre `main`, **sin PR todavía**). Acumula varias features: visibilidad del menú; **flujo de vendedor en el bot** (menús 105/106, identificación por **código + número** `vendedores.telefono`, reintento de código de cliente, `SALIR`); **CUIL/DNI** en clientes; **normalización de teléfono multi-país** (`config/Telefono.php` + `tenants.pais`/`bot_config.pais`); **depósito obligatorio** en las 3 vías de alta; **copia del ticket al vendedor** + encabezado del admin con el cliente; fixes UI (banda blanca `#panelLista`, orden "Soy Vendedor" antes de "Salir" en el panel). Specs/planes en `docs/superpowers/{specs,plans}/2026-06-10-vendedor-*` y `2026-06-11-normalizacion-telefono-*`.
- **Pendiente (next steps):** (1) **E2E real por WhatsApp en `demo`** del flujo de vendedor completo (identificación, reintento, Salir, ticket al vendedor, copia admin) + alta/edición de cliente con depósito + multi-país. (2) Evaluar **plantilla aprobada de Meta** si se quiere que el cliente reciba el ticket en su número en el flujo de vendedor (hoy bloqueado por la ventana de 24h). (3) **Abrir PR a `main`** (la rama bundlea muchas features).
- **UX/responsive (sesión 2026-06-11, sin commit aún):** solapa "Vendedor asignado" en `cliente.php` (form en secciones/tabs); "Guardar" deshabilitado-hasta-modificar en 10 forms; cursor `not-allowed` en disabled (global); tablas DataTables colapsables **solo en mobile** (`matchMedia<992px`) en 8 vistas; menú mobile pulido (backdrop, cierre al tocar fuera, usuario+Salir en sidebar) + fix `.content-page` padding mobile; fix bug **ventasfechacliente** (filtro `p.id`). Detalle en *UI Conventions*, *Key Decisions* y *Gotchas* de este archivo.
- **Opción futura NO implementada:** permitir cambiar el **código de cliente en edición** requiere un **cascade** (actualizar `pedidos`/`link_pedidos`/`reclamos`/`consultas`/`telefonos`/`fidelizar` en una transacción). Hoy el código es read-only en edición a propósito.
- **Deuda abierta — `vistas/motivo.php` acepta letras en el código del motivo** (`strtoupper()` sin validación numérica). El `codigo`/`opcionId` debe ser numérico (el cliente elige por número en WhatsApp). Datos ya corregidos por UPDATE en `atiende_demo`/`atiende`/`atiende_corp`, pero falta arreglar la validación en `motivo.php` (o migrar su gestión a la pestaña Reclamos de `configuracion.php`, que ya auto-numera).
