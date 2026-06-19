# Mapa del menú del bot (BotEngine)

Guía para entender el menú del bot de WhatsApp sin perderse en los `menuId`.

> **Por qué los números parecen caóticos:** los `menuId` son **claves semánticas**,
> no un orden. Están hardcodeados en `modelos/BotEngine.php`, referenciados en el bot
> legacy `ws/post.php`, y guardados **literalmente** en `bot_config.menu_json` de
> **cada tenant**. Por eso **no se renumeran**: cambiarlos exige tocar código + tests +
> una migración sobre todos los tenants a la vez. Este doc los explica; no los cambia.

- **Estructura:** el menú vive como JSON en `bot_config.menu_json` (un nodo por `menuId`).
- **Template de tenants nuevos:** `app-tenants/templates/bot/default_menu.json`
  (lo siembra `ProvisioningService`). Lo de `app/_docker/.../bot_config_seed.sql` es solo dev local.
- **Cada nodo:** `{ menuId, consigna, finaliza, menuItem[] }`. Cada `menuItem` es
  `{ opcionId, opcion, menuId (destino), guardar, area, accion? }`.
- **El usuario navega** tipeando el `opcionId`; el bot responde la `consigna` del nodo
  destino. Una `accion` corre lógica PHP (ver tabla de acciones abajo).

## Convención de numeración (de-facto, no estricta)

| Rango | Significado |
|---|---|
| `0` | Estado inicial (recién llega / tras una baja). Redirige a `100`. |
| `1xx` | Identificación / onboarding (`100` bienvenida, `101` código, `102`+`8.x` alta, `103` recuperar código, `105`/`106` vendedor*) |
| `200` | Menú principal (ya identificado) |
| `2.2` | **Salir** (cierre). Se detecta por este destino, NO por el texto. |
| `1`, `5` | Subflujo **reclamo**: `1` = gateway de motivos, `5` = captura del detalle |
| `300`, `15` | Subflujo **consulta**: `300` = gateway de motivos, `15` = captura del detalle |
| `350` / `300`* | Link de pedido (*varía por tenant: `350` en demo/corp, `300` en seed default) |
| `400` | Consultar estado de un reclamo |
| `3` | Cierre genérico ("Nos estamos ocupando…") |
| `4` | "Opción no válida" |
| `8.1`, `802`, `8.2` | Pasos intermedios del alta de cliente nuevo |

\* `105`/`106` (flujo vendedor) existen en demo/corp, no en el template default.

## Flujo (template default)

```
[0] inicio ──(saludo)──► [100] BIENVENIDA / identificación
                              │
        ┌─────────────────────┼───────────────────────┬───────────────┐
       (1)                    (2)                      (3)             (4)
   Ya soy cliente       Quiero ser cliente    No recuerdo mi código   Salir
        │                     │                        │                │
   [101] pedir código    [102] nombre            [103] pedir datos    [2.2] Salir
   accion:registraNumero      │                  accion:recuperar-       (fin)
        │                [8.1] dirección          CodigoCliente
        ▼                     │                   ├─ match → responde código, vuelve a [100]
   [200] MENÚ PRINCIPAL  [802] ubicación          └─ sin match → pide datos →
        │                     │                       accion:registrarOlvidoReclamo
        │                [8.2] confirmar SI           → [3] cierre
        │                accion:registraClientes
        │                     │
        │                     ▼
        │                   [3] cierre
        │
   ┌────┼──────────┬──────────────┬───────────┐
  (1)  (2)        (3)            (4)          (5)
 pedido reclamo  consulta   consultar recl.  salir
  │     │          │             │             │
[350] [1] motivos [300] motivos [400] nº recl. [2.2]
link   (inyect.)   (inyect.)    accion:         Salir
       │            │           consultarReclamo
      [5] detalle  [15] detalle
      guardar=true accion:registrarConsulta
       │            │
      [3] cierre   [2.2] Salir
```

> **Inyección dinámica:** los motivos de **reclamo** se cargan desde la tabla
> `motivo_reclamos` dentro del `menuId 1` (hardcodeado), y los de **consulta** desde
> `motivo_consultas` dentro del `menuId 300` (hardcodeado). No están en el JSON: el
> bot los inyecta en runtime. El motivo reservado `opcionId='99'` (recuperar código)
> se **saltea** en esa inyección (no se le ofrece al cliente).

## Tabla de nodos (template default)

| menuId | Propósito | Destinos / acción |
|---|---|---|
| `0` | Estado inicial | → `100` (solo ante saludo) |
| `100` | Bienvenida + identificación | 1→`101`, 2→`102`, 3→`103` (recuperar), 4→`2.2` |
| `101` | Pide código de cliente | → `200` · `accion: registraNumero` |
| `102` | Alta: nombre | → `8.1` |
| `8.1` | Alta: dirección | → `802` |
| `802` | Alta: ubicación | → `8.2` |
| `8.2` | Alta: confirmar (SI) | → `3` · `accion: registraClientes` |
| `103` | Recuperar código: pide datos | → `3` · `accion: registrarOlvidoReclamo` |
| `104` | (legacy del flujo viejo, **sin uso**) | — |
| `200` | Menú principal | 1→`350`, 2→`1`, 3→`300`, 4→`400`, 5→`2.2` |
| `1` | Gateway motivos de **reclamo** (inyecta `motivo_reclamos`) | motivos → `5` |
| `5` | Captura detalle del reclamo | → `3` · `guardar=true` |
| `300` | Gateway motivos de **consulta** (inyecta `motivo_consultas`) | motivos → `15` |
| `15` | Captura detalle de la consulta | → `2.2` · `accion: registrarConsulta` |
| `350` | Link de pedido (varía por tenant) | finaliza · `palabraClave: pedido/comprar` |
| `400` | Consultar reclamo (pide nº) | → `2.2` · `accion: consultarReclamo` |
| `3` | Cierre genérico | finaliza |
| `2.2` | Salir | finaliza |
| `4` | Opción no válida | — |

## Acciones (lógica PHP en `BotEngine::procesarAccion`)

| `accion` | Qué hace |
|---|---|
| `registraNumero` | Valida el código de cliente y lo asocia al teléfono (`telefonos`). |
| `registraClientes` | Alta de cliente nuevo (nombre/dirección/ubicación). |
| `recuperarCodigoCliente` | Busca el código por el teléfono; si hay match lo responde y vuelve a `100`. |
| `registrarOlvidoReclamo` | Sin match: crea un reclamo "No recuerdo mi numero de cliente" + aviso en cascada. |
| `consultarReclamo` | Devuelve el estado de un reclamo por su número. |
| `registrarConsulta` | Guarda el detalle de la consulta. |
| `registraVendedor` | Identifica al vendedor (código + número). *(demo/corp)* |
| `chequearVendedorCliente` | Valida el código de cliente del vendedor. *(demo/corp)* |

## menuIds hardcodeados en el código (los que NO se pueden cambiar a la ligera)

- `BotEngine.php`: `'1'` (inyección motivos reclamo), `'300'` (inyección motivos consulta),
  `'2.2'` (Salir, detectado por destino), `'100'`, `'103'`, `'106'`, `'200'`, `'3'`, `'4'`.
- `ws/post.php` (bot legacy): ~75 referencias propias.
- DB: cada tenant guarda estos números en `bot_config.menu_json`.

## Dos bots, dos fuentes — no confundir

- **Bot ACTUAL** = `BotEngine.php` (vía `ws/webhook.php`). Menú en `bot_config.menu_json`;
  motivos en `motivo_reclamos` / `motivo_consultas`. Panel: **Configuración → Reclamos/Consultas**.
- **Bot LEGACY** = `ws/post.php`. Motivos en la tabla `menuitem`. Panel: `vistas/motivo.php`.
  Fuera de uso en tenants migrados.
