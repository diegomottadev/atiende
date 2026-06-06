# Release 2026-06-05 — Visibilidad de opciones del menú del bot + fix de códigos de motivos

Rama: `feat/menu-visibilidad-opciones`

## Feature: control de visibilidad por opción
Cada opción de los menús editables del bot (menuId `200`=principal, `100`=identificación) puede mostrarse u ocultarse desde la pestaña **Menú Principal** de `configuracion.php`, sin tocar código ni schema.

- **`modelos/BotEngine.php`**: nuevo `proyectarMenu($menuItem)` (privado). Filtra opciones con `activo==='false'` y renumera 1..N al vuelo. Punto único de verdad: se usa igual en display y en match → el número visto == el número que matchea. "Salir" (destino `menuId "2.2"`) nunca se oculta y va último. Captura de texto libre (`opcionId` vacío) y menús inyectados (1=motivo_reclamos, 300=motivo_consultas) intactos.
- **`ajax/configuracion.php`**: `getMenuPrincipal` devuelve `activo`+`esSalir`; `saveMenuPrincipal` persiste `activo`, valida ≥1 visible (guardrail backend con `break 2`), fuerza Salir visible, acota `menuId` al allowlist `{200,100}` y sanitiza el texto (`strip_tags` + límite de 200).
- **`vistas/configuracion.php`**: columna "Visible" (switch BS5), guardrail front, tooltip hover en Salir.
- **`tests/BotEngineProyectarMenuTest.php`** (nuevo): test aislado de `proyectarMenu` (copia 1:1 del método).
- **QA**: aprobado. **Security**: 0 críticas/altas (2 hardenings aplicados: allowlist de `menuId`, sanitización de texto).

Spec completa: `docs/superpowers/specs/2026-06-05-menu-visibilidad-opciones-design.md`.

## Fix de datos: códigos numéricos en motivos de reclamo
`vistas/motivo.php` (tabla `menuitem`, consumida por el bot legacy `ws/post.php`) permitía cargar el `codigo`/`opcionId` como letras (A,B,C…) por `strtoupper()` sin validación numérica. Se renumeró `menuitem.opcionId` a 1..N (por `id`) en `atiende_demo`, `atiende` (plantilla base) y `atiende_corp`.

> Causa raíz NO corregida: `motivo.php` sigue aceptando letras. Queda como deuda técnica (ver Gotchas en CLAUDE.md). El bot ACTUAL (`BotEngine.php`) lee de `motivo_reclamos`, que ya auto-numera desde la pestaña Reclamos.

## Cambios de DB
- **Feature de visibilidad: SIN migración ni cambio de schema.** El flag `activo` vive dentro de la columna existente `bot_config.menu_json`; los items sin la clave se interpretan como visibles (retrocompat total).
- **Fix de motivos: por UPDATE manual** (no migración versionada). Se renumeró `menuitem.opcionId` 1..N en `atiende_demo`, `atiende` y `atiende_corp`. Los tenants nuevos heredan los códigos numéricos de la plantilla base `atiende`.

```sql
-- Fix aplicado (por DB: atiende_demo, atiende, atiende_corp):
SET @n := 0; UPDATE menuitem SET opcionId = (@n := @n + 1) ORDER BY id;
```
