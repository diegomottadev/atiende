# Spec — Control de visibilidad de opciones del menú del bot

Fecha: 2026-06-05 · Rama: `feat/menu-visibilidad-opciones`
Enfoque: **A** (flag `activo` por opción dentro de `bot_config.menu_json`, numeración en tiempo real). **Sin cambio de schema.**

Pipeline de agentes: product-strategist → product-owner → functional-analyst + ux-ui-designer.

---

## 1. Objetivo

Permitir que el operador con permiso de Configuración **muestre u oculte** individualmente las opciones numeradas de los menús editables del bot (menuId `200` = menú principal, `100` = identificación primera vez) desde la pestaña **Menú Principal** de `vistas/configuracion.php`, sin tocar código ni schema.

Al ocultar una opción:
- No se muestra al usuario en WhatsApp ni se puede seleccionar.
- Las opciones visibles se **renumeran 1..N al vuelo** — display y match usan la **misma** proyección, así el usuario siempre ve `1,2,3…` sin huecos y el número que tipea matchea lo que vio.

Reglas duras:
- Menús **legacy** (items sin flag `activo`) → comportamiento idéntico al actual.
- **"Salir"** (item cuyo `menuId` destino es `"2.2"`) → sin toggle, nunca se oculta, siempre último.
- **Guardrail**: nunca guardar un menú con 0 visibles (validado en backend).
- Menús **inyectados** por BotEngine (`menuId '1'`=motivo_reclamos, `'300'`=motivo_consultas) **no se tocan**.

## 2. Modelo de datos

Cada `menuItem` numerado suma `"activo":"true"|"false"` dentro del JSON existente:

```json
{ "opcionId":"3", "opcion":"Hacer una consulta", "menuId":"300", "guardar":"false", "area":"", "activo":"false" }
```

- Item **sin** la clave `activo` → se interpreta visible (`"true"`). Retrocompat total, sin migración.
- **No hay ALTER ni cambio de schema**: el flag vive en la columna `bot_config.menu_json` ya existente.

## 3. BotEngine — `proyectarMenu()` (punto único de verdad)

```php
private function proyectarMenu(array $menuItem): array {
    $visibles = []; $salir = null;
    foreach ($menuItem as $opt) {
        if (empty($opt['opcionId']) && ($opt['menuId'] ?? '') !== '2.2') {
            $visibles[] = $opt; continue;            // captura de texto libre: se conserva, no se renumera
        }
        if (($opt['menuId'] ?? '') === '2.2') { $salir = $opt; continue; } // Salir → al final
        $activo = array_key_exists('activo',$opt) ? $opt['activo'] : 'true'; // legacy = visible
        if ($activo === 'false') continue;            // oculta
        $visibles[] = $opt;
    }
    $n = 1;
    foreach ($visibles as &$o) { if (!empty($o['opcionId'])) { $o['opcionId'] = (string)$n; $n++; } }
    unset($o);
    if ($salir !== null) { $salir['opcionId'] = (string)$n; $salir['activo']='true'; $visibles[] = $salir; }
    return $visibles;
}
```

Integración:
- **DISPLAY** (~`BotEngine.php:200-206`): iterar sobre `$this->proyectarMenu($this->menuJson[$i]['menuItem'])` en vez del array crudo. El render `*opcionId*. opcion` queda igual pero ya renumerado.
- **MATCH** (~`BotEngine.php:285-611`): `$menuItem = $this->proyectarMenu(...)` antes del bucle. Se mantiene `strcasecmp(opcionId,$mensaje)==0 || strlen(opcionId)==0` (texto libre sigue capturando).

Garantía: display y match consumen la MISMA proyección → imposible ver "2" y que el bot espere "3".

## 4. Endpoints (`app/ajax/configuracion.php`)

Auth (ambos): `if (empty($_SESSION['configuracion'])) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }`
`getMenuPrincipal` sigue en whitelist de lectura del `csrfGuard`; `saveMenuPrincipal` exige CSRF. Soporte JSON envuelto (`{menu:[...]}`) y plano (`[...]`).

### GET `getMenuPrincipal`
Por cada menú `∈ {200,100}`, por cada item con `opcionId` no vacío, devolver:
```json
{"opcionId":"3","opcion":"Hacer una consulta","activo":"false","esSalir":false}
```
- `esSalir = (menuId destino === "2.2")`.
- `activo` = `esSalir ? "true" : (existe la clave ? normalizado : "true")` (default true para legacy).

### POST `saveMenuPrincipal`
Request: `menuId=200` + `items=[{opcionId,opcion,activo}, ...]`.
Validaciones server-side, en orden:
1. `menuId ∈ {200,100}` → si no: `{"ok":false,"error":"menú no encontrado"}`.
2. `items` decodifica a array → si no: `{"ok":false,"error":"menú inválido"}`.
3. bot_config sin fila: `{"ok":false,"error":"tenant sin menú"}`; JSON no-array: `"menú inválido"`.
4. localizar entry por menuId (envuelto/plano) → no existe: `"menú no encontrado"`.
5. mapas `txt[opcionId]=opcion`, `vis[opcionId]= ("true"|"false")` (todo ≠ "true" → "false").
6. **Guardrail** sobre el entry COMPLETO: item cuenta visible si es Salir (`menuId destino "2.2"`, siempre) o `activo`final ≠ "false". Si visibles < 1 → `{"ok":false,"error":"Debe quedar al menos una opción visible"}` (no escribe).
7. aplicar `opcion` y `activo` sobre cada menuItem (Salir → `activo` forzado "true", ignora lo que mande el cliente).
8. reescribir en el mismo formato leído → `json_encode(...,JSON_UNESCAPED_UNICODE)` → `mysqli_real_escape_string` → `UPDATE bot_config SET menu_json=... WHERE id=1`. OK: `{"ok":true}`.

## 5. UI (`app/vistas/configuracion.php`)

Se agrega una 3ª columna **"Visible"** con un switch BS5 (`form-check form-switch`) por fila, manteniendo el patrón actual (card border + `table table-sm table-bordered` + thead `table-light` + 1 botón Guardar por grupo).

```
┌─ Menú Principal ──────────────────────────────────────────────┐
│ │ Opción │ Texto que ve el usuario          │   Visible   │   │
│ │   1    │ [ Hacer un pedido            ]    │   ( ●——)    │   │
│ │   2    │ [ Hacer un reclamo           ]    │   ( ●——)    │   │
│ │   3    │ [ Hacer una consulta         ]    │   (——○ )OFF │   │
│ │ 0(Salir)│ [ Salir                    ]    │  ( ●——)🔒   │   │
│ [ 💾 Guardar ]                                                 │
└────────────────────────────────────────────────────────────────┘
```

Reglas UI:
- Header: `<th width="18%" class="text-center">Visible</th>` tras "Texto…".
- Fila normal: `<input class="form-check-input menu-visible" type="checkbox" role="switch" data-menuid data-opcionid {checked si activo==='true'}>`.
- Fila **Salir** (`esSalir`): `checked disabled`, envuelta en `<span data-bs-toggle="tooltip" data-bs-trigger="hover" title="La opción Salir siempre debe estar visible.">`.
- **Guardrail front** (`change` del switch): si apagarlo deja 0 visibles en el grupo → revertir (`checked=true`) + `Swal warning` "El menú debe mostrar como mínimo una opción al cliente."
- `cargarMenuPrincipal`: tras inyectar HTML, init tooltips `{trigger:'hover',animation:false}`.
- `guardarGrupoMenu`: restringir el `querySelectorAll` a `input[type="text"]` (ahora hay 2 inputs por fila) y sumar `activo` leyendo el switch hermano por `data-opcionid`.

Estados: loading (Swal showLoading), éxito (Swal success "Menú actualizado", timer 1200), error (`Swal error r.error || 'Error al guardar'`), vacío (se mantiene `Sin menús`).

## 6. Edge cases

1. JSON envuelto vs plano: detectar con `isset($decoded['menu'])`, reescribir en el mismo formato.
2. Item sin `activo` → visible (legacy intacto).
3. `opcionId` vacío = captura de texto libre → se conserva, no se filtra ni renumera; no se lista en la UI.
4. Menús inyectados (1/300) → fuera de `{200,100}`, sin flag, no pasan por la proyección de editables.
5. 0 visibles → bloqueado en backend (mensaje exacto), no escribe.
6. Salir detectado por destino `"2.2"` (no por el literal "Salir") → tolera renombre; forzado visible + último.
7. `activo` basura (`"1"`,`"on"`,null) → normaliza: solo "true" es visible.
8. Front manda subconjunto de items → guardrail recorre el entry completo, no se puede burlar.

## 7. Multi-tenant y seguridad

- `bot_config` singleton `id=1` por DB de tenant (ruteo por `$_SESSION['tenant_db']`) → aislamiento natural, sin migración cross-tenant.
- Drift de columnas de `bot_config` entre tenants no afecta (solo se usa `menu_json`).
- Sin prepared statement nuevo: el único input que toca SQL es el JSON serializado, escapado con `mysqli_real_escape_string` (igual que hoy); `opcion`/`activo` se inyectan vía estructura PHP + `json_encode`, no por concatenación.
- Webhook WA <5s: `proyectarMenu` es O(n) en memoria, sin queries extra.

## 8. Archivos afectados

| Archivo | Cambio |
|---|---|
| `app/modelos/BotEngine.php` | + `proyectarMenu()`; usarla en display (~200-206) y match (~285-611) |
| `app/ajax/configuracion.php` | `getMenuPrincipal` devuelve `activo`+`esSalir`; `saveMenuPrincipal` persiste `activo` + guardrail + Salir forzado |
| `app/vistas/configuracion.php` | columna "Visible" (switch BS5), guardrail front, tooltip Salir, init tooltips, ajuste de `guardarGrupoMenu` |
| (test) | test aislado de `proyectarMenu` (oculta intermedia → 1..N; Salir último; legacy sin flag = sin cambios) |

## 9. Criterios de DONE

1. `getMenuPrincipal` devuelve `activo` y `esSalir`; default "true" para legacy; soporta envuelto/plano.
2. `saveMenuPrincipal` persiste `opcion`+`activo`, fuerza Salir visible, rechaza 0 visibles con el mensaje exacto.
3. Auth 403 + CSRF correctos.
4. `proyectarMenu` filtra+renumera+Salir-último, consumido por display **y** match (numeración mostrada == matcheada).
5. Captura de texto libre sigue funcionando; menús legacy e inyectados intactos.
6. Switch Salir `disabled checked` + tooltip hover.
7. Sin cambio de schema / migración.
8. E2E manual (tenant `demo`): ocultar opción 3 de menú 200 → bot muestra 1,2,3(=ex-4),4(=Salir); tipear "3" dispara ex-4; el número viejo "5" no matchea.
