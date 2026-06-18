# Spec — "No recuerdo mi número de cliente" (recuperar código por teléfono)

Fecha: 2026-06-18
Módulo: Bot WhatsApp (`modelos/BotEngine.php` + `bot_config.menu_json`)
Origen: pedido del usuario (Diego) — opción de bot para recuperar el código de cliente.

## 1. Objetivo

Cuando un cliente no recuerda su código, el bot debe devolvérselo automáticamente buscándolo por el número de teléfono desde el que escribe. Si ese número no está asociado a ningún cliente, el bot toma sus datos en un solo mensaje y los registra como un reclamo para que la empresa lo atienda. Reduce fricción en la identificación y evita que un cliente quede trabado por no recordar el código.

## 2. Estado actual (IMPORTANTE: el flujo ya existe y se REEMPLAZA)

El menú de bienvenida (`bot_config.menu_json`, `menuId 100`) tiene la opción `"No recuerdo mi número de cliente"` → `menuId 103`. **Hoy `103` ya está implementado y funciona, pero de otra forma**:

- **`menuId 103`** (actual): consigna *"Sin problema 🙂 Escribime tu **nombre completo** y tu **dirección** así un agente identifica tu cuenta. Recordá que no puedo escuchar audios…"* + un `menuItem` de captura de texto libre (`opcionId:""`, `guardar:"true"`, `motivo:"Cliente no recuerda su código"`) que va a `menuId 104`. Es decir: pide nombre+dirección y guarda un reclamo genérico, **sin ninguna búsqueda por teléfono**.
- **`menuId 104`** (actual): *"¡Gracias **<nombre>**! Registramos tu solicitud. Un agente se va a comunicar a la brevedad…"*.
- En producción/`atiende_demo` puede haber reclamos con `motivo = "Cliente no recuerda su código"` generados por este flujo.

**Este trabajo REEMPLAZA ese flujo** por: lookup-por-teléfono primero; solo si no hay match, captura estructurada. El nodo `104` queda obsoleto para este flujo (se reemplaza por el cierre `menuId 3`, ver §3).

Otros datos verificados:
- `telefonos` (`clienteId`, `telefono` PK, `activo`): mapa teléfono→cliente que arma el bot al identificarse. BotEngine ya lo usa al inicio para reconocer al cliente por `$user`.
- `clientes` (`codigo` varchar, `razonSocial`, `telefono` varchar nullable, `id` PK): `telefono` lo carga el admin, en formato arbitrario.
- `config/Telefono.php::normalizar($numero,$pais)`: normaliza a `wa_id` según `bot_config.pais`.
- `reclamos`, `motivo_reclamos`, `bot_config.admin_telefono`, `areas` (`id`,`area`,`telefono`): como en el resto del bot.
- **`menuId 3`** (ya existe): consigna exacta *"**<nombre>** Nos estamos ocupando de inmediato.\n¡Hasta pronto!"* → es el cierre que pidió el usuario; se **reutiliza**.

## 3. Flujo y punto de integración (resuelve el "acción al llegar")

**Restricción del motor (verificada):** en `BotEngine::procesarAccion`, al *llegar* a un menú (`esperaRespuesta=='0'`) solo se manda la `consigna` y se setea espera; las `accion` de los `menuItem` corren en la rama `esperaRespuesta=='1'`, es decir **sobre el mensaje siguiente**, dentro del loop de match. Por eso la acción de lookup NO puede ir como ítem de captura del `103` (no correría al llegar).

**Solución:** la acción de lookup se ata al **ítem opción-3 del `menuId 100`** (el `menuItem` `"No recuerdo mi número de cliente"`). Cuando el cliente tipea `3` estando en el menú 100 (rama `esperaRespuesta=='1'`), el loop de match procesa esa opción y **ahí corre la acción `recuperarCodigoCliente`**, antes de navegar al `103`.

```
Cliente en menú 100 tipea "3"  (rama match, esperaRespuesta==1)
        │
        ▼  corre accion `recuperarCodigoCliente` del ítem opción-3
LOOKUP por $user:
   1. SELECT clienteId FROM telefonos WHERE telefono = $user           (match exacto)
   2. si no hay → comparar clientes.telefono normalizado vs $user normalizado
        │
        ├── HAY MATCH (1 o varios) ───────────────────────────────────────────┐
        │     Responde "Tu código de cliente es: *{codigo}* — {razonSocial}"  │
        │     (varios → los lista todos)                                       │
        │     → reset al menú de bienvenida 100 y RETURN temprano              │
        │       (NO navega al 103; patrón de registraNumero)                   │
        │                                                                       │
        └── SIN MATCH ──────────────────────────────────────────────────────┐ │
              Navega al `103` (consigna nueva, §abajo) y deja captura activa │ │
                    │                                                         │ │
                    ▼ el cliente manda los datos en un mensaje               │ │
              Acción de captura (ej. `registrarOlvidoReclamo`):              │ │
                guarda RECLAMO (§4) + AVISO en cascada (§5)                  │ │
                → cierre reutilizando `menuId 3`:                            │ │
                  "*{nombre}* Nos estamos ocupando de inmediato.\n           │ │
                   ¡Hasta pronto!"  → vuelve al inicio                       │ │
```

**Consigna nueva del `103`** (reemplaza la actual):
```
Para registrarte enviá en *un solo mensaje*:
*Nombre/Razón Social:*
*Localidad:*
*Dirección/Dirección del negocio:*
*DNI o CUIL:*
```

## 4. Reclamo de "olvido" (caso sin match)

Se inserta en `reclamos`:

| Campo | Valor |
|---|---|
| `empresa` | `$this->empresa` |
| `fecha_ingreso` | `now()` |
| `clienteId` | `''` |
| `telefono` | `$user` |
| `nick` | `$pushname` |
| `motivo` | **`"No recuerdo mi numero de cliente"`** (string fijo) |
| `area` | el `area` del motivo reservado en `motivo_reclamos` (§6); puede ser `0` |
| `detalle` | el texto que envió el cliente |
| `resolucion` | `''` |

Queda visible en el panel de Reclamos (el `LEFT JOIN areas` muestra la fila aunque `area=0`).

## 5. Aviso en cascada (caso sin match)

Tras guardar, se notifica al primero disponible:
1. **Supervisor del área del motivo** — si esa área tiene `areas.telefono` → aviso WhatsApp (mismo formato que un reclamo: nombre, tel, detalle, link `/responder/reclamo/{id}/{token}` con el token HMAC del fix de seguridad).
2. **Si no** → `bot_config.admin_telefono` (si está cargado, normalizado).
3. **Si no hay ninguno** → no avisa; queda solo en el panel.

## 6. El motivo reservado en `motivo_reclamos`

- Se **siembra una fila** en `motivo_reclamos` para este flujo, con **`area = 0` (sin área por default)**. El admin le asigna el área después desde el panel, como cualquier motivo.
- **Se identifica por un `opcionId` reservado** (numérico, fuera del rango que usan los motivos visibles — ej. `opcionId = '99'`), **no** por el texto, para tolerar que el admin renombre la `opcion` (consistente con la regla "opcionId debe ser numérico" del proyecto).
- **Se EXCLUYE del menú de reclamos del cliente**: BotEngine, al inyectar `motivo_reclamos` en el `menuId 1`, **saltea la fila del `opcionId` reservado**. Así nunca aparece como opción seleccionable al hacer un reclamo.
- El flujo forgot-code **lee el `area`** de esa fila (`SELECT area FROM motivo_reclamos WHERE opcionId = '<reservado>' LIMIT 1`) para el reclamo y la cascada de aviso.

## 7. Cambios por archivo

- **`bot_config.menu_json`** (cada tenant + seed `_docker/mariadb/bot_config_seed.sql`):
  - `menuId 100`, ítem opción-3: setear `accion = "recuperarCodigoCliente"`.
  - `menuId 103`: cambiar la consigna por la de §3 (campos del alta); su `menuItem` de captura cambia a `accion = "registrarOlvidoReclamo"` (en vez del `guardar:"true"` genérico actual) y su destino al cierre `menuId 3`.
  - `menuId 104`: queda sin uso para este flujo (no se borra para no romper otras referencias; simplemente deja de enrutarse acá).
- **`modelos/BotEngine.php`**:
  - Acción `recuperarCodigoCliente`: lookup (`telefonos` → `clientes` normalizado) + ramas match (responde código(s) + reset a 100 + return) / sin-match (navega a 103 con captura).
  - Acción `registrarOlvidoReclamo`: inserta el reclamo (§4) + cascada de aviso (§5) + cierra reutilizando `menuId 3`.
  - En la inyección de `motivo_reclamos` al `menuId 1`: filtrar la fila del `opcionId` reservado (§6).
  - Reutiliza `Telefono::normalizar` y `WhatsAppClient`.
- **`ws/post.php`** (bot legacy): **fuera de alcance**.
- **Seed / migración idempotente** (`_docker/migrations/`): la fila reservada de `motivo_reclamos`, el `accion` del ítem 100 y la consigna/acción del 103 se agregan al seed para tenants nuevos; en `atiende_demo` se aplican al implementar. (MySQL 8 sin `IF NOT EXISTS` → inserts/updates idempotentes con chequeo previo; edición de `menu_json` vía script PHP, no `mysql -e`, por los acentos.)

## 8. Casos edge

- **Varios clientes para un teléfono**: se listan todos los códigos.
- **`clientes.telefono` vacío/NULL**: no matchea (esperado).
- **Mismatch de formato** (local vs `wa_id`): se compara con ambos lados normalizados.
- **Datos en varios mensajes**: se toma el primer mensaje como `detalle` (texto libre, sin validar estructura).
- **Reclamos viejos `"Cliente no recuerda su código"`**: quedan en la tabla con ese motivo viejo; no se migran (decisión: dejarlos; el nuevo flujo usa el motivo nuevo). Documentar para no confundir en reportes.
- **Fila reservada no sembrada en un tenant**: el flujo igual funciona; `area = 0` → cascada cae a `admin_telefono` → nada. El seed lo evita en tenants nuevos.

## 9. Multi-tenant

- Todo opera sobre la DB del tenant activo. No se hardcodea DB ni id de área.
- En B2B el lookup de `clientes` es por `codigo`; en B2C por `id` (igual que el resto del bot).

## 10. Criterios de done

- [ ] Elegir opción 3 en el menú 100 dispara el lookup (ya no el pedido de nombre+dirección directo).
- [ ] Teléfono asociado (vía `telefonos` o `clientes.telefono` normalizado) → responde el/los código(s) correctos y vuelve al menú, sin pedir datos.
- [ ] Teléfono NO asociado → pide los datos (consigna §3) y al recibirlos crea un reclamo con motivo `"No recuerdo mi numero de cliente"`, `detalle` = el texto, y cierra con el mensaje de `menuId 3`.
- [ ] El reclamo aparece en el panel de Reclamos.
- [ ] Aviso en cascada: supervisor del área del motivo → `admin_telefono` → nada, según disponibilidad.
- [ ] El motivo reservado **no** aparece como opción al hacer un reclamo normal.
- [ ] Se siembra con `area = 0` y el admin puede asignarle un área desde el panel, que el bot respeta.
- [ ] Funciona en B2B y B2C.
- [ ] `php -l` OK; probado end-to-end en `atiende_demo`.

## 11. Fuera de alcance (YAGNI)

- Paridad en `ws/post.php` (bot legacy).
- Validar/parsear la estructura de los datos del alta (texto libre).
- Auto-identificar al cliente en el caso match (solo se muestra el código).
- UI nueva para configurar el área del motivo (se usa el panel de motivos existente).
- Migrar los reclamos viejos `"Cliente no recuerda su código"`.
