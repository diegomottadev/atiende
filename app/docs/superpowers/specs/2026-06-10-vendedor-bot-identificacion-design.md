# Identificación de vendedor en el bot + CUIL/DNI en clientes

**Fecha:** 2026-06-10
**Estado:** Aprobado (diseño)
**Rama:** `feat/menu-visibilidad-opciones` (o rama nueva a definir)

## Resumen

Dos features independientes, ambas del dominio vendedor/cliente:

1. **Flujo de vendedor en el bot de WhatsApp.** Un vendedor se identifica en el chat con su **código de vendedor** (igual que un cliente con su código), luego ingresa el **código del cliente** al que le va a cargar un pedido, y recibe el link de pedido atribuido a vendedor + cliente. La identidad de vendedor **queda recordada** para mensajes posteriores. Incluye **reintento** ante código de cliente incorrecto y una opción **"Salir"** para cerrar la sesión de vendedor.

2. **Campos CUIL y DNI en `clientes`.** Columnas nuevas opcionales, editables en el formulario de edición de `cliente.php`. Solo ABM; el bot no las usa (por ahora).

Se especifican juntas por cercanía de dominio, pero son ortogonales y pueden implementarse/mergearse por separado.

---

## Contexto del sistema (estado actual)

- El bot vive en `app/modelos/BotEngine.php`, invocado desde `app/ws/webhook.php`. El menú principal vive como JSON en `wb_*.bot_config.menu_json` (un row, `id=1`).
- **Identificación de cliente:** el webhook normaliza el número entrante a solo dígitos (`$user`, ej. AR `549XXXXXXXXXX`). `BotEngine::handle()` busca `telefonos.clienteId WHERE telefono=$user` → si existe, es cliente conocido (menú `200`); si no, menú `100` (bienvenida: 1=Ya soy Cliente, 2=Quiero ser Cliente, 3=No recuerdo, 4=Salir).
- **Avance del flujo:** en `procesarAccion`, tras ejecutar la acción de un `menuItem`, la línea ~637 **recursiona** al `menuId` siguiente con `esperaRespuesta='0'`. Las acciones que fallan hacen `return` **temprano**, antes de esa recursión y sin avanzar el estado — este es el mecanismo que habilita el **reintento**.
- **Lógica de vendedor preexistente (parcial, NO cableada en ningún `menu_json`):**
  - Acción `chequearVendedorCliente` (BotEngine ~L401): identifica al vendedor por `vendedores.telefono=$user`, valida el cliente y setea `vendedores.atencion=clienteId`. En fallo **resetea a menú 0** (sin reintento).
  - Atribución del link de pedido (BotEngine ~L270-289): lee `vendedores.atencion WHERE telefono=$user` y arma `/pedidos/{id}/{codigoVendedor}`.
- `vendedores.telefono` es el **contacto canónico** del vendedor (lo usan `sendWap`/`sendContacto` para notificarle pedidos). **No debe pisarse.**
- Esquemas relevantes (`app/_docker/mariadb/atiende.sql`):
  - `contactos(id PK, empresaId, nombre, telefono, menu, esperaRespuesta, anterior text, mensaje longtext, fechaHora)` — tabla de estado de sesión del bot, una row por número.
  - `clientes(codigo, razonSocial, direccion, zona, vendedor, …, id bigint PK auto_increment)` — sin `cuil`/`dni`.
  - `vendedores(codigo PK varchar(50), nombre, telefono, version, supervisor NOT NULL, atencion)`.

---

## Feature 1 — Flujo de vendedor

### Decisiones de diseño (acordadas)

| Decisión | Elección | Razón |
|---|---|---|
| Identificación | Por **código de vendedor** (no por teléfono) | Igual modelo de confianza que el código de cliente; no exige pre-cargar números. |
| Persistencia | **Recordado** entre sesiones | Comodidad: el vendedor no re-ingresa su código en cada pedido. |
| Dónde se guarda el vínculo | Columna nueva `contactos.vendedor_codigo` | `contactos` ya se carga por número en cada mensaje y la columna **sobrevive** a los reseteos de menú (que sólo tocan `menu/anterior/mensaje/esperaRespuesta`). Un solo `ALTER` por tenant; sin tabla ni JOIN nuevos. |
| Cerrar sesión | Opción **"Salir"** por palabra clave en el paso de código de cliente | El paso es captura de texto libre; detectar `SALIR` no rompe ese modelo ni requiere un menú numerado. |
| `vendedores.telefono` | **No se modifica** | Es el contacto del vendedor para notificaciones. El vínculo de sesión vive en `contactos`. |

### Cambios de datos

```sql
-- En cada DB de tenant existente (atiende, atiende_demo, atiende_corp, atiende_demo_1, …):
ALTER TABLE `contactos` ADD COLUMN `vendedor_codigo` VARCHAR(50) NULL DEFAULT NULL;
```
- Agregar la misma columna a la definición de `contactos` en `app/_docker/mariadb/atiende.sql` (para tenants nuevos).
- MySQL 8 no soporta `ADD COLUMN IF NOT EXISTS`: el script de migración debe tolerar el caso "ya existe" (verificar en `information_schema` o capturar el error 1060).

### Cambios en `menu_json` (demo + `bot_config_seed.sql` si aplica)

- **Menú `100`** (bienvenida): agregar un `menuItem` `"Soy Vendedor"` con `menuId:"105"`. `proyectarMenu` renumera 1..N al vuelo y mantiene "Salir" (destino `2.2`) último, así que el `opcionId` literal es indiferente.
- **Menú `105`** (nuevo): `consigna` = `"Ingresá tu *código de vendedor*:"`, un `menuItem` de captura `{opcionId:"", opcion:"", menuId:"106", accion:"registraVendedor"}`.
- **Menú `106`** (nuevo): `consigna` = `"Ingresá el *código del cliente* al que vas a cargar el pedido.\n\n(Escribí *SALIR* para cerrar tu sesión de vendedor.)"`, un `menuItem` de captura `{opcionId:"", opcion:"", menuId:"<link>", accion:"chequearVendedorCliente"}`.
- El **menú del link de pedido varía por tenant**: es `350` en demo/corp pero `300` en el seed default. La migración lo **detecta por el placeholder `<linkPedidos>`** y cablea el `menuId` de la captura del 106 a ese menú (no se hardcodea). El menú del link ya existe en cada tenant y se reutiliza sin cambios.

> Nota: `105`/`106` NO pasan por la inyección de motivos (que es para menuIds `1` y `300`), ni se ven afectados por `proyectarMenu` salvo si se listaran como editables — son menús de captura, intactos.

### Cambios en `BotEngine.php`

1. **Leer el vendedor de sesión.** En `procesarAccion`, ampliar el `SELECT menu,esperaRespuesta,anterior FROM contactos …` (~L187) para traer también `vendedor_codigo` → variable local `$codigoVendedor`.

2. **Routing en estado inicial.** En el bloque `if ($menu == '0')` (~L193-205), tras el gate de saludo:
   ```
   if (strlen($codigoVendedor) > 0)      $menu = '106';   // vendedor recordado → pedir código de cliente
   elseif (strlen($codigoCliente) == 0)  $menu = menuIdB; // 100
   else                                  $menu = '200';
   ```
   El vendedor de sesión tiene prioridad sobre el cliente hasta que haga "Salir".

3. **Acción `registraVendedor`** (en la rama de captura, junto a `registraNumero`):
   - `SELECT codigo,nombre FROM vendedores WHERE codigo = '<mensaje escapado>'`.
   - Si existe → `UPDATE contactos SET vendedor_codigo='<codigo>' WHERE id='$user'`; **no** retorna → cae a la recursión y avanza al menú `106`.
   - Si no existe → `sendText("El código de vendedor no es válido. Volvé a intentarlo escribiendo *Hola* nuevamente.")` + reset de `contactos` (`menu='0'`, etc.) + `return` (mismo patrón que `registraNumero` inválido).

4. **Acción `chequearVendedorCliente`** (reescrita para sesión + reintento + salir):
   - Leer `$codVend` = `contactos.vendedor_codigo` para `$user` (o reutilizar `$codigoVendedor`).
   - **Salir:** si `strtolower(trim($mensaje)) === 'salir'` → `UPDATE contactos SET vendedor_codigo=NULL, menu='0', esperaRespuesta=0, anterior='', mensaje='' WHERE id='$user'` + `sendText("Cerraste tu sesión de vendedor. ¡Hasta pronto!")` + `return`.
   - **Validar cliente:** `SELECT codigo,razonSocial FROM clientes WHERE codigo='<mensaje>' AND vendedor='$codVend'`.
     - Encontrado → `UPDATE vendedores SET atencion='<clienteCodigo>' WHERE codigo='$codVend'`; opcional `sendText("Cliente: <razonSocial>")`; **no** retorna → cae a la recursión → menú `350` genera el link.
     - No encontrado → `sendText("Codigo de cliente ingresado incorrecto intente nuevamente")` + `return` **sin tocar `contactos`** → el estado queda en `menu=106, esperaRespuesta=1` → el próximo mensaje es otro intento.
   - La acción `chequearVendedorCliente` original (identificación por teléfono) queda **reemplazada** por esta versión basada en sesión. No hay tenant que la use hoy, así que no rompe nada en producción.

5. **Atribución del link de pedido** (~L270-289): anteponer la rama basada en sesión y **preservar** la actual como fallback:
   ```
   if (strlen($codigoVendedor) > 0) {
       $row = SELECT atencion FROM vendedores WHERE codigo='$codigoVendedor'
       if (atencion no vacío) {
           UPDATE link_pedidos SET clienteId='<atencion>' WHERE id='$notiPedido'
           $vendedorR = $codigoVendedor
           UPDATE vendedores SET atencion='' WHERE codigo='$codigoVendedor'
       }
   } else {
       …lógica actual por telefono=$user, intacta…
   }
   ```
   El `INSERT INTO link_pedidos` inicial usa `clienteId=$codigoCliente` (vacío en flujo vendedor) y luego se sobrescribe con `atencion` (el cliente elegido) — comportamiento ya existente.

### Datos de prueba (demo)

- Un vendedor con `codigo` conocido (ej. el creado vía el ABM de `vendedor.php`).
- ≥2 filas en `clientes` con `vendedor = <ese código>` y `codigo` conocido, para validar match y reintento.

---

## Feature 2 — CUIL/DNI en `clientes` (independiente)

### Cambios de datos
```sql
ALTER TABLE `clientes`
  ADD COLUMN `cuil` VARCHAR(20) NULL DEFAULT NULL,
  ADD COLUMN `dni`  VARCHAR(20) NULL DEFAULT NULL;
```
- Agregar a la definición de `clientes` en `app/_docker/mariadb/atiende.sql`.
- Aplicar por `ALTER` a cada DB de tenant existente (tolerar "ya existe").

### Cambios de código

> El modelo de clientes es **`Persona.php`** (clase `Persona`) y el endpoint es **`ajax/persona.php`**; la vista es `cliente.php` y su JS `cliente.js` (que postea a `../ajax/persona.php`). No existe `Cliente.php` ni `ajax/cliente.php`.

- `app/modelos/Persona.php`: agregar `cuil`/`dni` a **dos métodos con firmas distintas** y a su SQL:
  - `insertar($codigo,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono,$deposito,$latitud,$longitud)` → agregar `$cuil,$dni` al `INSERT`.
  - `editar($idpersona,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono)` → agregar `$cuil,$dni` al `UPDATE`.
  - `mostrar($idpersona)` ya hace `SELECT *`, así que devuelve las columnas nuevas sin cambios.
- `app/ajax/persona.php`: leer `cuil`/`dni` del POST (`limpiarCadena`) y pasarlos en **ambos** call sites — `insertar(...)` (L39) y `editar(...)` (L43). Ojo: `editar` no recibe `deposito/latitud/longitud`, así que `cuil/dni` deben sumarse a cada lista de argumentos de forma independiente.
- `app/vistas/cliente.php`: dos inputs nuevos en el form de edición (bloque BS5 `$_SESSION['ventas'] == 1`; **no** tocar el legacy `== 10`), siguiendo el patrón de campos existentes (`razonSocial`/`direccion`). Opcionales (sin `required`).
- `app/vistas/scripts/cliente.js`: `limpiar()` resetea ambos; `guardaryeditar` los envía (FormData a `ajax/persona.php`); `mostrar()` los rellena desde la respuesta.

> Alcance acotado: sin validación de formato de CUIL/DNI, sin uso en bot/ticket/exportación. Si luego se necesita, se especifica aparte.

---

## Componentes y límites

| Unidad | Qué hace | Depende de |
|---|---|---|
| `menu_json` (105/106 + opción en 100) | Define la estructura del flujo de vendedor | `bot_config.menu_json` por tenant |
| `BotEngine::procesarAccion` (routing + acciones) | Identifica vendedor, valida cliente con reintento, cierra sesión, atribuye el link | `contactos`, `vendedores`, `clientes`, `link_pedidos` |
| `contactos.vendedor_codigo` | Vínculo persistente teléfono↔vendedor (sesión) | ALTER por tenant |
| ABM `clientes` (cuil/dni) | Persiste y edita CUIL/DNI | `clientes`, `Persona.php`, `ajax/persona.php`, `vistas/cliente.php`, `cliente.js` |

---

## Manejo de errores / casos borde

- **Código de vendedor inválido:** aviso + reset, corta el flujo (no deja sesión a medias).
- **Código de cliente inválido o ajeno al vendedor:** *"Codigo de cliente ingresado incorrecto intente nuevamente"*, reintento en el mismo paso (sin límite de intentos; `SALIR` u "hola" salen del paso).
- **Teléfono que es cliente y vendedor a la vez:** mientras haya `vendedor_codigo`, prioriza el flujo de vendedor; "Salir" lo limpia y vuelve al comportamiento de cliente.
- **`vendedores.telefono` intacto:** las notificaciones al vendedor siguen funcionando.
- **Migración idempotente:** los `ALTER` deben tolerar columnas ya existentes (MySQL 8 sin `IF NOT EXISTS`).
- **Concurrencia de `atencion`:** `vendedores.atencion` es scratch por vendedor; se setea al validar cliente y se limpia al generar el link. Aceptable para la escala del sistema.

---

## Testing

- `tests/BotEngineProyectarMenuTest.php` es copia 1:1 de `proyectarMenu`. Este cambio **no** toca `proyectarMenu`, así que el test no se modifica; pero agregar la opción "Soy Vendedor" a menú 100 debería verificarse manualmente con el renumerado de `proyectarMenu`.
- `php -l` sobre los archivos PHP tocados (dentro del container `atiende-app`).
- **E2E manual por WhatsApp en `demo`** (el motor es DB-coupled): saludo → "Soy Vendedor" → código de vendedor (válido/ inválido) → código de cliente (propio / ajeno → reintento) → recepción del link → `hola` de nuevo (reconocido) → `SALIR`. Seguir el runbook `docs/runbook-bot-no-carga-menu.md` ante fallas de carga de menú.
- ABM clientes: alta/edición con y sin CUIL/DNI; verificar persistencia y relleno del form.

---

## Fuera de alcance (YAGNI)

- Validación de formato de código de vendedor, CUIL o DNI.
- Límite de reintentos del código de cliente.
- Uso de CUIL/DNI en bot, ticket, exportaciones o búsqueda.
- Auto-reconocimiento del vendedor por su teléfono registrado (se usa identificación por código).
- Panel/listado de "sesiones de vendedor activas".
