# Spec funcional — Protección CSRF en handlers `ajax/` que mutan estado

- **Fecha:** 2026-06-02
- **Origen:** PO (historia "CSRF en endpoints de mutación") → revisada por Analista Funcional
- **Prioridad:** Must-have · **Gate de release: "antes del próximo tenant"**
- **RICE ≈ 28.8** · Esfuerzo Medio (2–8 hs) · **Sin cambios de DB**

---

## 1. Objetivo

Impedir que un sitio de terceros, usando la sesión autenticada del navegador de un operador, fuerce peticiones de mutación (crear/editar/eliminar/guardar/anular/enviar WhatsApp) contra los handlers de `ajax/`. Cierra la última deuda de seguridad pendiente (CSRF) tras el pase que resolvió SQLi/HMAC/upload/passwords. No cambia funcionalidad visible.

## 2. Mecanismo (token per-sesión, centralizado)

1. **Token per-sesión** (no per-request, para no romper DataTables ni concurrencia): `bin2hex(random_bytes(32))`.
2. **Lazy-init en `config/auth.php`** (no en el login): si hay sesión válida y no existe `$_SESSION['csrf_token']`, se genera. Cubre sesiones activas durante el deploy sin re-login.
3. **Exposición al front:** `const globalCsrfToken` en `vistas/headerv1.php`; el `$.ajaxSetup({beforeSend})` + `$(document).ajaxError` viven en `vistas/footerv1.php` (donde jQuery/SweetAlert ya están cargados). El header `X-CSRF-Token` se adjunta en **toda petición same-origin** (GET incluido — ver §6, por mutaciones legacy que se invocan por GET), omitiendo URLs externas absolutas.
4. **Validación centralizada** en `config/csrf.php` (`requireCsrf()` / `csrfGuard($op,$readOnly)`), con `hash_equals`.
5. **Allowlist EXPLÍCITA de lectura por handler** (no genérica por nombre — ver §0 del veredicto).
6. **`$(document).ajaxError` global obligatorio** para que un 403/401 no deje la UI colgada.

## 3. Correcciones críticas sobre la propuesta original (veredicto del analista)

1. **🔴 Allowlist genérica por nombre = inviable.** No hay convención de nombres consistente (`listarp`, `listarc`, `selectArea`, `obtenerPedidos`, `traerTelefono`, `nextCodigo`, `getToken`, `grafico_ventas`, `permisos`…). Una lista por substring daría falsos negativos (rompe lecturas) y falsos positivos peligrosos. → **Allowlist explícita por handler** (tabla §4).
2. **🔴 `bajarPedidos.php` muta por GET** (`UPDATE pedidos SET flag=1` dentro de la descarga CSV vía `location.href`) — el header CSRF no lo cubre. → Separar el `UPDATE` a un POST con CSRF (recomendado) **o** dejarlo como deuda CSRF-6.
3. **🔴 `usuario.php op=permisos` es mutación** (escribe asignaciones) pese al nombre → exige token.
4. **Lazy-init en `auth.php`**, no en login.
5. **`$(document).ajaxError` es obligatorio** (no nice-to-have).

## 4. Endpoints afectados (relevamiento real, 30 handlers)

| Handler | Ops de MUTACIÓN (→ CSRF) | Ops de LECTURA (exentas) |
|---|---|---|
| `area.php` | guardaryeditar, eliminar | mostrar, listarp |
| `areaConsulta.php` | guardaryeditar, eliminar | mostrar, listarp |
| `articulo.php` | guardaryeditar, desactivar, activar | mostrar, listar, selectCategoria, listarCabecera |
| `configuracion.php` | guardar, eliminar, saveMenuPrincipal, guardarArea, eliminarArea, saveToken | listarAreas, listarReclamos, listarConsultas, getMenuPrincipal, listarAreasAdmin, getToken |
| `consulta.php` | guardaryeditar, guardarMensaje, eliminar | mostrar, listarp, listarMensajes |
| `consultas.php` | — | comprasfecha, ventasfechacliente |
| `ingreso.php` | guardaryeditar, anular | mostrar, listarDetalle, listar, selectProveedor, listarArticulos |
| `mapa.php` | — | ventas, reclamos, grafico_ventas, grafico_reclamos |
| `mensajeb2b.php` | delete | mostrar, listar, listarClientes |
| `mensajeb2c.php` | deleteMessage | listarContactos, listarMensajes, showMessage, getContactsToSendMessage |
| `motivo.php` | guardaryeditar, eliminar | mostrar, listarp, selectArea |
| `motivoConsulta.php` | guardaryeditar, eliminar | mostrar, listarp, selectArea |
| `permiso.php` | — | listar |
| `persona.php` | guardaryeditar, eliminar, eliminarCliente | mostrar, listarp, listarc |
| `reclamo.php` | guardaryeditar, guardarMensaje, eliminar | mostrar, listarp, listarMensajes |
| `repartidor.php` | guardaryeditar, eliminar | listar, mostrar |
| `reparto.php` | guardaryeditar, anular, asignar, desasignar, editarEstado, guardarMensaje | mostrar, traerTelefono, listarDetalle, listar, listarArticulos, listarMensajes, selectCliente, repartidores, obtenerPedidos |
| `solicitud.php` | guardarNuevoCliente, eliminar | mostrar, listar, nextCodigo |
| `usuario.php` | guardaryeditar, desactivar, activar, **permisos** | mostrar, listar (verificar/salir exentos de auth) |
| `vendedor.php` | guardaryeditar, desactivar, activar | mostrar, listar |
| `venta.php` | guardaryeditar, anular, editarEstado, guardarMensajeCliente, guardarMensaje | mostrar, traerTelefono, listarDetalle, listar, listarArticulos, listarMensajes, selectCliente |
| `up_file.php` | vendedores, clientes, articulos (imports) | — → `requireCsrf()` directo |
| `subirarchivo.php`, `telefonosUpload.php` | import Excel | — → `requireCsrf()` directo |
| `send_wa.php` | envío WhatsApp (POST, sin switch) | — → `requireCsrf()` directo |
| `notificacion.php` | — (polling) | todo (sin CSRF) |
| `aExcel.php`, `aExcelConsulta.php`, `aExcelVentas.php`, `excel.php`, `exports/*` | — (export GET) | todo (auth-only) |
| `bajarPedidos.php` | **UPDATE flag=1 por GET** | export CSV → **EXCEPCIÓN, ver §8** |

**Exentos completos:** `ws/webhook.php`, `ws/post.php`, `ws/npost.php` (HMAC, sin sesión — el bot NO debe romperse, SLA <5s); `pedidos/*` (cliente final, token de `link_pedidos`); login/logout; bloques BS3 legacy (`$_SESSION['x']==10`).

## 5. Contrato `config/csrf.php`

```php
<?php
function requireCsrf(): void {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    $real = $_SESSION['csrf_token'] ?? '';
    if ($real === '' || $sent === '' || !hash_equals($real, $sent)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'CSRF token inválido']);
        exit;
    }
}
function csrfGuard(string $op, array $readOnlyOps): void {
    if (!in_array($op, $readOnlyOps, true)) requireCsrf();
}
```

- **Request:** header `X-CSRF-Token: <token>` (preferido) o body `csrf_token` (fallback).
- **Error:** HTTP 403, JSON `{"ok":false,"error":"CSRF token inválido"}`, `exit`.
- **Precondición:** `auth.php` corrió antes y pobló `$_SESSION['csrf_token']`.

## 6. Cambios concretos

- **`config/auth.php`** (al final, tras el check de `idusuario`):
  ```php
  require_once __DIR__ . '/csrf.php';
  if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  ```
- **`config/csrf.php`** — crear con §5.
- **`vistas/headerv1.php`** (~L141, junto a `globalUrl`): solo expone el token (acá jQuery/SweetAlert aún NO están cargados):
  ```php
  const globalCsrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
  ```
- **`vistas/footerv1.php`** (al pie, ya cargados jQuery + SweetAlert): el `$.ajaxSetup` y el `$(document).ajaxError`. **El token se adjunta en TODA petición same-origin (GET incluido)**, no solo en POST/PUT/DELETE/PATCH.

  > **Decisión de implementación (ajuste sobre la propuesta original "solo POST"):** varias mutaciones legacy de `venta.php`/`reparto.php` (`editarEstado`, `guardarMensaje`, `asignar`) se invocan con `$.ajax({url:"...op=..."})` **sin `type`** → jQuery defaultea a **GET**. Si el header fuera solo en POST, esas acciones recibirían 403. Adjuntando el token en todo same-origin: las lecturas GET lo ignoran (server no valida en ops de lectura), las mutaciones GET-legacy pasan, y no se filtra a terceros (se omiten URLs externas absolutas). Al ir como **header** (no en la query string) no se loguea en la URL.

  ```js
  $.ajaxSetup({ beforeSend: function (xhr, s) {
    var url = s.url || '';
    var external = /^https?:\/\//i.test(url) && url.indexOf(window.location.origin) !== 0;
    if (!external && typeof globalCsrfToken !== 'undefined' && globalCsrfToken)
      xhr.setRequestHeader('X-CSRF-Token', globalCsrfToken);
  }});
  $(document).ajaxError(function(e,xhr){
    if (xhr.status===403) Swal.fire({icon:'warning',title:'Sesión de seguridad expirada',text:'Recargá la página e intentá de nuevo.'});
    else if (xhr.status===401){ Swal.fire({icon:'warning',title:'Sesión expirada'}).then(()=>{ window.location.href=(globalUrl||'../')+'index.php'; }); }
  });
  ```
- **Cada handler con switch** (después de auth, antes del switch):
  ```php
  $op = $_GET['op'] ?? '';
  csrfGuard($op, [ /* lista de LECTURA real de ESE handler, de §4 */ ]);
  ```
- **Handlers sin switch** (`send_wa`, `subirarchivo`, `up_file`, `telefonosUpload`): `requireCsrf();` directo tras auth.

## 7. Flujos de error

| Condición | Server | Usuario |
|---|---|---|
| Token ausente/inválido en mutación | 403 JSON | `ajaxError` → Swal "Sesión de seguridad expirada, recargá" |
| Sesión expirada (sin idusuario) | 401 (auth.php) | Swal + redirect login |
| Sesión vieja sin token (deploy) | auth.php lazy-init lo genera | nada anómalo |
| Export GET (sin header) | pasa (lectura) | descarga normal |

## 8. Decisiones pendientes (recomendaciones)

1. **`bajarPedidos.php`** — separar el `UPDATE flag=1` a un POST con CSRF (**recomendado**) vs. deuda CSRF-6. Sin esto, el gate "todo lo que muta" tiene un agujero conocido.
2. **`op=permisos`** — confirmado: mutación, lleva token.
3. **Ubicación de `$.ajaxSetup`+`ajaxError`** — `headerv1.php` (header común autenticado; `login.php` no lo carga) — **recomendado**.

## 9. Plan de pruebas / Done

**Positivos:** en ≥2 tenants (atiende + un `wb_*`), alta/edición/eliminación en todos los módulos; DataTables (POST) cargan sin 403; exports descargan; imports Excel suben; `send_wa` envía; login/logout OK.
**Negativos:** POST de mutación sin/con-token-malo → 403 sin mutar; lectura sin token → 200.
**Regresión bot:** webhook/post/npost responden (<5s); flujo `pedidos/` intacto.
**Infra:** sesión sin `csrf_token` → auth.php la regenera; `ajaxError` muestra Swal.

## 10. Descomposición

1. **CSRF-1** Infra: token en auth.php + `csrf.php` + `headerv1` (`$.ajaxSetup` + `ajaxError`). No rompe nada.
2. **CSRF-2** Handlers core: usuario, persona, reclamo, consulta, venta, reparto, send_wa, configuracion, notificacion.
3. **CSRF-3** Resto + imports Excel.
4. **CSRF-4** Exenciones + regresión del bot ← **gate del próximo tenant.**
5. **CSRF-5** (Should, fuera del gate) `pedidos-platform` — historia espejo.
6. **CSRF-6** (según decisión §8.1) `bajarPedidos.php`.
