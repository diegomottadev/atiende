# Eliminación de Datos de Usuario (Cumplimiento Meta) — Spec Funcional/Técnica

**Fecha:** 2026-06-02
**Proyecto:** Atiende (multi-tenant B2B/B2C con WhatsApp Cloud API)
**Stack:** PHP 8+ · MySQLi/PDO · MariaDB · Docker · Bootstrap 5 · jQuery · DataTables · SweetAlert2 · WhatsApp Cloud API (Meta Graph API) · Guzzle
**Origen:** PO — Épica "Cumplimiento Meta — Eliminación de Datos de Usuario" (H1–H5)
**Status:** Draft (contiene decisiones PENDIENTES DE DEFINICIÓN — ver §13)

---

## 1. Contexto

Meta exige, como condición para aprobar la app de WhatsApp Business en el Developer Portal, que el negocio publique **instrucciones de eliminación de datos de usuario** y, opcionalmente, exponga un **Data Deletion Callback** firmado. Atiende es multi-tenant: cada tenant `wb_<slug>` tiene su propia base de datos y sus propias credenciales de Meta (`whatsapp_phone_id`, `whatsapp_token_enc`, `whatsapp_app_secret_enc`) almacenadas cifradas en `pedidos_platform.tenants` y desencriptadas en `config/Conexion.php`.

Adicionalmente, en Argentina la **Ley 25.326** y la **AAIP** otorgan al titular el derecho de supresión de sus datos personales. Esta épica cubre ambos frentes: el requisito técnico de Meta y el derecho legal del titular. **Responsable de la base de datos ante la AAIP = Atiende (plataforma)** (RESUELTO §13.6); los tenants actúan como **encargados del tratamiento**. La supresión se materializa por **anonimización** (RESUELTO §13.1): ninguna tabla se borra físicamente.

El dato de identidad principal del usuario final en Atiende es el **número de teléfono de WhatsApp** (`wa_id` / `telefono`), presente en múltiples tablas. El `user_id` que Meta envía en el callback firmado **NO** es el `wa_id` — es un identificador de Facebook scoped a la app, no mapeable a un teléfono. Por eso H2 registra esas solicitudes como "pendiente de identificación".

---

## 2. Alcance

### Incluido
- **H1** — Página pública `eliminar-datos.php` en la raíz (instrucciones + acceso al formulario). Sin login, HTTPS, indexable, enlazada desde `politica-privacidad.php`.
- **H2** — Endpoint `data-deletion-callback.php` (POST `signed_request` de Meta), validación HMAC-SHA256 con `app_secret` per-tenant, respuesta JSON `{url, confirmation_code}`, idempotente. **Parte del release** (RESUELTO §13.3) — se implementa último en el orden pero no queda diferido ni tras flag.
- **H3** — Formulario público de solicitud con verificación de identidad por **OTP de WhatsApp** (reutiliza `config/WhatsAppClient.php::sendText` + `normalizePhone`), anti-enumeración, rate limiting + captcha, OTP hasheado, expiración y un solo uso.
- **H4** — Vista operativa `vistas/eliminaciones.php` (card pattern) + handler `ajax/eliminacion.php` con auth check y scope estricto por `tenant_db`. Página pública de estado por `confirmation_code` sin PII.
- **H5** — Modelo `modelos/EliminacionDatos.php`: ejecución transaccional de la **anonimización** multi-tabla (sin borrado físico — RESUELTO §13.1), integridad referencial, auditoría. Tablas nuevas `data_deletion_requests` y `data_deletion_audit` + migración en todos los `wb_*` y dump base.

### Excluido (fuera de esta épica)
- Borrado de datos de los **usuarios del panel** (`usuario`, `users`) — son operadores, no titulares finales.
- Borrado en sistemas externos del tenant (ERP que sincroniza `clientes` por CSV) — se documenta como riesgo, no se implementa.
- Portabilidad/exportación de datos (derecho distinto al de supresión).
- UI de superadmin en `pedidos-platform/` (esta épica opera dentro de cada `wb_*`).

---

## 3. Usuarios y roles

| Rol | Qué puede hacer |
|---|---|
| Titular (usuario final WhatsApp) | Lee instrucciones; envía solicitud; verifica identidad por OTP; consulta estado por `confirmation_code` |
| Meta (sistema) | POST al callback firmado; recibe `{url, confirmation_code}` |
| Operador tenant | Lista solicitudes de **su** tenant; aprueba (ejecuta borrado), rechaza, marca identificación manual |
| Admin tenant | Igual que operador + ve auditoría completa |
| Superadmin plataforma | Fuera de alcance UI; responsable de configurar la URL del callback en Meta |

---

## 4. Arquitectura

### Flujo general

```
                       ┌──────────────────────────────────────────┐
   Titular WhatsApp    │  RAÍZ (público, sin sesión admin)         │
        │              │  eliminar-datos.php   (H1 instrucciones)  │
        │ 1. abre      │      │ formulario (H3)                    │
        ▼              │      ▼                                    │
  eliminar-datos.php ──┼─► ajax/eliminacion_publica.php            │
        │              │      ├─ op=solicitar  → genera OTP + WA   │
        │ 2. OTP WA     │      ├─ op=verificar  → valida OTP        │
        │ (sendText)    │      └─ op=estado     → estado público   │
        ▼              │                                           │
   estado-eliminacion.php?code=XXXX  (público, sin PII)            │
                       └──────────────────────────────────────────┘
                                       │
   Meta Platform ──POST signed_request─┘
        │ data-deletion-callback.php (H2) → {url, confirmation_code}
        ▼
  ┌────────────────────────────────────────────────────────────┐
  │  PANEL ADMIN (con sesión / config/auth.php)                 │
  │  vistas/eliminaciones.php (H4 card pattern)                 │
  │      └─► ajax/eliminacion.php  (op=listar/aprobar/rechazar) │
  │              └─► modelos/EliminacionDatos.php (H5)          │
  │                      └─► transacción multi-tabla + auditoría│
  └────────────────────────────────────────────────────────────┘
                  Todo opera sobre la DB del tenant activo (session tenant_db)
```

### Resolución del tenant en contexto público (sin sesión)

Las páginas públicas (`eliminar-datos.php`, callback, `ajax/eliminacion_publica.php`, `estado-eliminacion.php`) **no tienen** `$_SESSION['tenant_db']`. Necesitan resolverlo.

> **[CRÍTICO — fix de auditoría de diseño, ver §15.1]** Las páginas públicas **NUNCA** deben escribir `$_SESSION['tenant_db']`. Hacerlo abre un vector de *session fixation / IDOR cross-tenant*: un operador logueado en el panel que abra (o sea inducido a abrir) una URL pública `?t=otroTenant` reescribiría su propia sesión, dejando su panel apuntando a la DB de otro tenant. El contexto público se aísla por completo del contexto del panel.

Patrón seguro a aplicar en **todos** los endpoints públicos:

1. **Aislar la cookie de sesión pública** antes de cualquier `session_start()`, para que jamás colisione con la sesión del panel:
   ```php
   // Tope del archivo público, ANTES de incluir config/Conexion.php:
   session_name('ATIENDE_PUB');           // cookie distinta a la del panel
   session_start();                        // sesión pública aislada
   ```
2. **Resolver el tenant en una variable LOCAL** (nunca en `$_SESSION`), validándolo contra `pedidos_platform.tenants`:
   ```php
   $slug = preg_replace('/[^a-z0-9_-]/i', '', $_GET['t'] ?? '');
   // PDO read-only a pedidos_platform:
   $stmt = $ppPdo->prepare(
     'SELECT slug FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
   $stmt->execute([$slug]);
   if (!$stmt->fetch()) { http_response_code(404); exit(json_encode(['ok'=>false,'error'=>'tenant no encontrado'])); }
   $tenantDb = 'wb_' . $slug;             // variable LOCAL
   ```
3. **Abrir una conexión MySQLi dedicada** a esa DB (no usar el `$conexion` que `Conexion.php` arma desde la sesión):
   ```php
   $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, $tenantDb);
   $conn->set_charset(DB_ENCODE);
   ```
4. Cargar las credenciales WhatsApp (`WA_PHONE_NUMBER_ID`, `WA_ACCESS_TOKEN`, `WA_APP_SECRET`) per-tenant **directamente desde `tenants`** con la misma lógica de desencriptado de `config/Conexion.php`, en variables locales — **sin** depender de `$_SESSION['tenant_db']`.

De este modo el contexto público opera sobre la DB correcta sin tocar la sesión del panel. El panel sigue resolviendo su DB exclusivamente desde `$_SESSION['tenant_db']` (cookie `PHPSESSID` propia), por lo que un operador logueado es inmune a la fijación de tenant vía URL pública.

- **Formulario público (H3):** el tenant se deriva del **slug en query string** (`?t=<slug>`), que el tenant publica en la URL configurada en Meta y enlaza desde su política. Resuelto como variable local según el patrón anterior. **Recomendación PENDIENTE — ver §13.4.**
- **Callback Meta (H2):** Meta NO envía el slug. La recomendación por defecto (§13.7) es **una URL de callback por tenant** (`data-deletion-callback.php?t=<slug>`), de modo que el `app_secret` correcto se cargue (en variable local) desde `tenants`. Sin slug no se puede validar la firma HMAC porque el `app_secret` es per-tenant.

---

## 5. Esquema de base de datos (H5)

Ambas tablas viven en **cada DB de tenant** (`wb_*` y `atiende`). NO en `pedidos_platform`.

### 5.1 `data_deletion_requests`

```sql
CREATE TABLE IF NOT EXISTS `data_deletion_requests` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `confirmation_code` VARCHAR(32)  NOT NULL,            -- código público opaco (no PII)
  `source`            ENUM('formulario','meta_callback','operador') NOT NULL DEFAULT 'formulario',
  `telefono_norm`     VARCHAR(20)  NULL,                -- teléfono normalizado (54XXXX) si se conoce
  `telefono_hash`     CHAR(64)     NULL,                -- HMAC-SHA256(telefono_norm, pepper) — NUNCA SHA-256 plano (ver §15.6)
  `meta_user_id`      VARCHAR(64)  NULL,                -- user_id de Facebook (no mapea a wa_id)
  `estado`            ENUM('pendiente','verificada','pendiente_identificacion','en_proceso','completada','rechazada')
                                   NOT NULL DEFAULT 'pendiente',
  `otp_hash`          CHAR(64)     NULL,                -- HMAC-SHA256(otp, pepper) — NUNCA SHA-256 plano (ver §15.6)
  `otp_expira`        DATETIME     NULL,
  `otp_intentos`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `otp_enviado_at`    DATETIME     NULL,
  `verificada_at`     DATETIME     NULL,
  `resuelta_at`       DATETIME     NULL,
  `resuelta_por`      INT          NULL,                -- usuario.idusuario del operador
  `motivo_rechazo`    VARCHAR(255) NULL,
  `ip_solicitante`    VARCHAR(45)  NULL,                -- IPv4/IPv6 (auditoría / rate limit)
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_confirmation_code` (`confirmation_code`),
  KEY `idx_telefono_hash` (`telefono_hash`),
  KEY `idx_estado` (`estado`),
  KEY `idx_meta_user_id` (`meta_user_id`),
  KEY `idx_ip_created` (`ip_solicitante`, `created_at`)   -- soporte rate limiting
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Notas:
- `confirmation_code`: **32 hex chars / 128 bits** (`bin2hex(random_bytes(16))`), opaco, único, público, no enumerable (ver §15.6).
- `telefono_norm` se almacena **solo entre `pendiente` y `completada`** para poder ejecutar la anonimización; al pasar a `completada` se **anula** (`NULL`) junto con `otp_hash`, dejando solo `telefono_hash` (irreversible) para idempotencia. Así el propio registro de la solicitud no se vuelve un nuevo depósito de PII.

### 5.2 `data_deletion_audit`

```sql
CREATE TABLE IF NOT EXISTS `data_deletion_audit` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`    BIGINT UNSIGNED NOT NULL,
  `accion`        VARCHAR(50)  NOT NULL,   -- 'creada','otp_enviado','verificada','aprobada',
                                           -- 'tabla_borrada','tabla_anonimizada','rechazada','error'
  `tabla`         VARCHAR(64)  NULL,       -- tabla afectada (cuando aplica)
  `filas`         INT          NULL,       -- filas afectadas
  `detalle`       VARCHAR(500) NULL,       -- sin PII: nunca guardar teléfono/nombre en claro
  `actor`         VARCHAR(64)  NOT NULL,   -- 'sistema','meta','usuario:<idusuario>'
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  CONSTRAINT `fk_audit_request`
    FOREIGN KEY (`request_id`) REFERENCES `data_deletion_requests`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> **[ALTO — fix de auditoría de diseño, ver §15.2]** La FK usa `ON DELETE RESTRICT`, **no** `CASCADE`. La auditoría es evidencia legal y debe conservarse 5 años (§13.8); con `CASCADE`, purgar un `data_deletion_requests` destruiría su rastro de auditoría. Con `RESTRICT`, **un `data_deletion_requests` no puede borrarse mientras tenga filas en `data_deletion_audit`**. Regla de retención: `data_deletion_requests` NO se purga; tras `completada` solo se anula su PII residual (`telefono_norm=NULL`, `otp_hash=NULL`), conservando `confirmation_code`, `telefono_hash` y toda la auditoría asociada durante el período de retención.

### 5.3 Script de migración idempotente

Archivo: `_docker/mariadb/migrations/2026-06-02_data_deletion.sql` — se incluye en el dump base y se corre en cada `wb_*`.

```sql
-- Idempotente: CREATE TABLE IF NOT EXISTS no falla si ya existe.
-- (Ejecutar este archivo dentro de cada DB wb_* y en atiende.)
START TRANSACTION;
CREATE TABLE IF NOT EXISTS `data_deletion_requests` ( /* ...§5.1... */ );
CREATE TABLE IF NOT EXISTS `data_deletion_audit`    ( /* ...§5.2... */ );
COMMIT;
```

Runner (PHP, idempotente) para iterar todos los tenants:

```
1. PDO a pedidos_platform → SELECT slug FROM tenants WHERE deleted_at IS NULL
2. Por cada slug: USE wb_<slug>; correr el .sql
3. También correr en `atiende`
4. Registrar resultado por tenant (ok / ya existía / error) en log
```

No requiere `ALTER` sobre tablas existentes, por lo que es seguro re-ejecutar. Permiso nuevo se inserta con guard (ver §10).

---

## 6. Mapa de PII por tabla/modelo (H5)

**Política (RESUELTA §13.1, 2026-06-02): ANONIMIZAR TODO — ninguna tabla se borra físicamente.** Todas las filas con PII del titular se conservan, sustituyendo los valores de PII por marcadores. Se preservan importes, métricas y vínculos referenciales.

Clave de identidad del titular: **teléfono** (`telefono_norm`). En tablas con `clienteId`/`codigo`, se resuelve primero el/los `clienteId` asociados al teléfono (vía `clientes.telefono`, `telefonos.telefono`, `contactosb2c.telefono`) y luego se opera por esos identificadores.

### Token pseudónimo para PII que es clave primaria

En `telefonos` (PK = `telefono`) y `contactos` (PK `id` = wa_id) la PII *es* la clave. Anonimizar a un valor fijo (`''`/`[ELIMINADO]`) violaría la unicidad/PK y rompería los vínculos por `clienteId`/`id`. Estrategia: reemplazar el teléfono/wa_id por un **token pseudónimo determinístico irreversible**:

```
$tok = 'del_' . substr(telefono_hash, 0, 16)   // telefono_hash = HMAC-SHA256(telefono_norm, pepper), §15.6
```

Propiedades: **determinístico** (el mismo teléfono → el mismo token, así los `UPDATE` en cascada por PK/FK son consistentes), **único** (16 hex ≈ 64 bits, colisión despreciable), **irreversible** (HMAC+pepper, no revertible por diccionario). Esto cumple el objetivo de "no borrar físicamente" conservando la fila y su integridad referencial, sin retener el teléfono real.

### Mapa por tabla (todas = ANONIMIZAR)

| Modelo | Tabla(s) | Columnas con PII | Acción | Valor de anonimización |
|---|---|---|---|---|
| **Persona** (B2B) | `clientes` | `razonSocial`, `direccion`, `telefono`, `localidad`, `latitud`, `longitud` | Anonimizar en sitio (PK `id` no es PII → se conserva) | `razonSocial='[ELIMINADO]'`, `direccion=''`, `telefono=''`, `localidad=''`, `latitud='0'`, `longitud='0'` |
| **Persona** (B2C) | `contactosb2c` | `telefono`, `razonSocial`, `direccion`, `localidad`, `cuit` | Anonimizar en sitio (PK `id` no es PII → se conserva el vínculo con el pivote) | `razonSocial='[ELIMINADO]'`, `direccion=''`, `telefono=''`, `localidad=''`, `cuit=''` |
| (teléfonos extra) | `telefonos` | `telefono` (**PK**), `clienteId` | Anonimizar vía **token pseudónimo** (PK = PII) | `UPDATE telefonos SET telefono = CONCAT('del_', SUBSTRING(?,1,16)) WHERE telefono=?` — usa `del_<hash16>`; preserva PK única y el vínculo por `clienteId` |
| **Reclamo** | `reclamos` | `telefono`, `nick`, `detalle`, `resolucion` | Anonimizar (preserva métricas por `area`/`motivo`) | `telefono=''`, `nick='[ELIMINADO]'`, `detalle='[ELIMINADO]'`, `resolucion='[ELIMINADO]'` (texto libre puede contener PII) |
| **Consulta** | `consultas` | `telefono`, `nick`, `detalle`, `resolucion` | Anonimizar (idem reclamos) | igual que `reclamos` |
| **Venta** | `pedidos` | `telefono`, `dato5..dato9` (campos libres con posible dirección/nombre) | Anonimizar (**conserva importes** `precio/subtotal/cantidad` para retención fiscal) | `telefono=''`, `dato5..dato9=''`; `clienteId` se mantiene apuntando al `clientes` ya anonimizado |
| **Reparto** | `pedidos` (`repartidor_id`, `fecha_*`) + `repartidores` | `pedidos.telefono` (ya cubierto). `repartidores.telefono`/`nombre` = personal del tenant, NO titular | Sin PII propia del titular; **no se toca `repartidores`** (operadores, fuera de alcance §2) | n/a |
| **MensajeB2B** | `mensajes` | `destino` (longtext con teléfonos/IDs destinatarios) | Anonimizar selectivamente dentro del campo | reemplazar el teléfono del titular por `[ELIMINADO]` en el JSON/CSV de `destino` (el resto de destinatarios intacto) |
| **MensajeB2C** | `mensajesb2c` + pivote `mensajeb2c_contactob2c` | El cuerpo (`mensaje`) es contenido masivo, sin PII individual; el vínculo está en el pivote vía `contactob2c_id` | **Neutralizar el vínculo sin borrar**: el `contactob2c_id` ya apunta a un `contactosb2c` anonimizado (queda como destinatario `[ELIMINADO]`). No se borran filas del pivote — se conserva el histórico de la campaña, ahora despersonalizado | (sin DELETE) la anonimización de `contactosb2c` neutraliza la PII referenciada |
| (bot runtime) | `contactos` | `id`(=wa_id, **PK**), `nombre`, `telefono`, `anterior`, `mensaje` | Anonimizar vía **token pseudónimo** + limpiar columnas | `UPDATE contactos SET id=CONCAT('del_',SUBSTRING(?,1,16)), telefono='', nombre='[ELIMINADO]', anterior='', mensaje='' WHERE id=? OR telefono=?` |
| (fidelización) | `fidelizar` | `clienteid`, mensajes de chat por pedido | Anonimizar contenido del chat, conservar la fila/pedido | `UPDATE fidelizar SET mensaje='[ELIMINADO]' WHERE pedidoid IN (...)`; `clienteid` queda apuntando al cliente ya anonimizado |

> Regla única (post-§13.1): **anonimización en todas las tablas**. Donde la PII es la clave primaria (`telefonos.telefono`, `contactos.id`) se usa el **token pseudónimo determinístico `del_<hash16>`** como excepción técnica para no violar la PK; en el resto se sustituye el valor en sitio. No hay `DELETE` físico en ninguna tabla.

---

## 7. Wireframes ASCII

### 7.1 `eliminar-datos.php` — instrucciones + formulario (H1/H3, público)

```
┌──────────────────────────────────────────────────────────────┐
│  [Atiende]                        Política de Privacidad ▸     │  navbar-atiende
├──────────────────────────────────────────────────────────────┤
│  Eliminación de tus datos personales                          │
│                                                                │
│  Si interactuaste con nosotros por WhatsApp y querés que       │
│  eliminemos tus datos, seguí estos pasos:                      │
│   1. Ingresá tu número de WhatsApp.                            │
│   2. Te enviaremos un código por WhatsApp para verificar       │
│      que sos el titular.                                       │
│   3. Confirmá el código y registraremos tu solicitud.         │
│   4. Guardá tu código de seguimiento para consultar el estado. │
│                                                                │
│  Plazo de resolución: hasta 10 días hábiles.                  │
│  Responsable de los datos: Atiende — privacidad@atiende.app   │
│  (Página genérica de Atiende — sin datos por tenant.)         │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │  Solicitar eliminación de datos                          │ │
│  │  Número de WhatsApp (con código de país)                 │ │
│  │  [ +54 9 11 ____________ ]                               │ │
│  │  [ captcha widget                              ]         │ │
│  │                                   [  Enviar código  ]    │ │
│  └──────────────────────────────────────────────────────────┘ │
│  ¿Ya tenés un código de seguimiento?  Consultar estado ▸       │
└──────────────────────────────────────────────────────────────┘
```

### 7.2 Pantalla de OTP (segundo paso del mismo formulario, público)

```
┌──────────────────────────────────────────────────────────────┐
│  Verificá tu identidad                                         │
│                                                                │
│  Te enviamos un código de 6 dígitos por WhatsApp al número     │
│  terminado en ••••• 8402.                                      │
│                                                                │
│         [ _ ] [ _ ] [ _ ] [ _ ] [ _ ] [ _ ]                    │
│                                                                │
│  El código vence en 09:58                                      │
│                                  [  Verificar y solicitar  ]   │
│  ¿No te llegó?  Reenviar código (disponible en 30s)            │
└──────────────────────────────────────────────────────────────┘
   (en éxito → muestra confirmation_code + link a estado)
```

### 7.3 `estado-eliminacion.php?code=XXXX` — estado público (sin PII)

```
┌──────────────────────────────────────────────────────────────┐
│  Estado de tu solicitud de eliminación                         │
│                                                                │
│  Código de seguimiento:  3f9a1c0b7d2e4a55                      │
│  Estado:        ●  EN PROCESO                                  │
│  Recibida:      02/06/2026                                     │
│  Plazo estimado: hasta el 12/06/2026                          │
│                                                                │
│  Estados posibles: Pendiente · Verificada · En proceso ·       │
│                    Completada · Rechazada                      │
│  (No se muestra ningún dato personal en esta página.)          │
└──────────────────────────────────────────────────────────────┘
```

### 7.4 `vistas/eliminaciones.php` — vista operativa (H4, card pattern Atiende)

```
┌─────────────────────────────────────────────────────────────────────┐
│ card sombra-panel  (border-top:3px solid #727cf5)                     │
│ ┌─ card-body pb-2 (filtros) ────────────────────────────────────────┐ │
│ │ Estado [select ▼]   Origen [select ▼]   Código [____]   [Buscar]  │ │
│ └───────────────────────────────────────────────────────────────────┘ │
│ ── hr ──                                                              │
│ ┌─ card-body p-0  (#listadoregistros) ─────────────────────────────┐ │
│ │ # │ CÓDIGO       │ ORIGEN    │ TEL (parcial)│ ESTADO   │ FECHA │ACC│ │
│ │ 1 │ 3f9a1c0b…   │ formulario│ ••••8402     │ Verificada│02/06 │👁✔✖│ │
│ │ 2 │ a71e90fa…   │ meta      │ (s/ident.)   │ Pend.ident│02/06 │👁✔✖│ │
│ │ 3 │ c0d2…       │ operador  │ ••••1199     │ Completada│01/06 │👁  │ │
│ └───────────────────────────────────────────────────────────────────┘ │
│ ┌─ card-body px-3 pb-3 (#formularioregistros — detalle/acción) ─────┐ │
│ │  Solicitud 3f9a1c0b…   Estado: Verificada                         │ │
│ │  Tablas a afectar (todas anonimización): clientes, pedidos,       │ │
│ │   reclamos, contactos*, telefonos*  (*PK → token del_<hash>)      │ │
│ │  [ Aprobar y ejecutar borrado ]   [ Rechazar ▼ (motivo) ]         │ │
│ └───────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

Convenciones aplicadas: `#tbllistado thead th { font-size:.72rem; text-transform:uppercase; ... }`, botón **"Nuevo"** no aplica (esta vista no crea), acciones con tooltip `{trigger:'hover'}` y `animation:false`, spinner SweetAlert2 durante la ejecución del borrado.

---

## 8. Endpoints

Formato de respuesta estándar: `{ "ok": true, "data": {...} }` / `{ "ok": false, "error": "msg" }`.

### 8.1 H2 — Callback de eliminación de datos de Meta

```
POST /data-deletion-callback.php?t=<slug>
Content-Type: application/x-www-form-urlencoded
Body: signed_request=<base64url(sig).base64url(payload)>
```

Procesamiento (ver hardening §15.4 — **no** se escribe `$_SESSION['tenant_db']`):
1. Validar `slug` contra `pedidos_platform.tenants`; resolver `tenantDb` en **variable local**; abrir conexión MySQLi dedicada; cargar `$appSecret` per-tenant en variable local desde `tenants` (no vía sesión).
2. **Guard previo:** si `!isset($appSecret) || $appSecret === ''` → HTTP 500 `"callback no disponible"`. No continuar.
3. Partir `signed_request` en exactamente 2 partes (`encoded_sig.payload`); si no hay 2 → 400.
4. Decodificar ambas con **base64url estricto** (`strtr` `-_`→`+/`, padding, `base64_decode($x, true)`); si falla la decodificación estricta → 400.
5. `expected = hash_hmac('sha256', $payloadRaw, $appSecret, true)`; comparar con la firma decodificada usando `hash_equals`. Mismatch → 400.
6. Decodificar `payload` JSON. **Rechazar si `data['algorithm'] !== 'HMAC-SHA256'`** (defensa contra downgrade) → 400. Extraer `user_id`.
7. Buscar solicitud existente por `meta_user_id` (idempotencia). Si no existe, crear con `estado='pendiente_identificacion'`, `source='meta_callback'`, `meta_user_id`.
8. Responder JSON.

Response éxito (formato exigido por Meta — `confirmation_code` de 32 hex):
```json
{ "url": "https://<tenant>/estado-eliminacion.php?code=3f9a1c0b7d2e4a55b1c2d3e4f5061728",
  "confirmation_code": "3f9a1c0b7d2e4a55b1c2d3e4f5061728" }
```
Códigos de error:
| Caso | HTTP | Cuerpo |
|---|---|---|
| `slug` ausente/inexistente | 404 | `{"ok":false,"error":"tenant no encontrado"}` |
| `app_secret` no configurado/vacío | 500 | `{"ok":false,"error":"callback no disponible"}` |
| Firma HMAC inválida | 400 | `{"ok":false,"error":"firma inválida"}` |
| `algorithm != HMAC-SHA256` | 400 | `{"ok":false,"error":"firma inválida"}` |
| base64url no decodifica (estricto) | 400 | `{"ok":false,"error":"request inválido"}` |
| `signed_request` malformado | 400 | `{"ok":false,"error":"request inválido"}` |

### 8.2 H3 — Solicitar eliminación + enviar OTP

```
POST /ajax/eliminacion_publica.php?t=<slug>
{ "op": "solicitar", "telefono": "+54 9 376 427 8402", "captcha": "<token>" }
```
Validaciones server-side:
- `telefono`: requerido, string, se normaliza con `normalizePhone()` (strip del `9` móvil **solo si empieza con `54`** — ver §15.3); debe quedar 10–15 dígitos. Inválido → error genérico (anti-enumeración).
- `captcha`: requerido, validado contra el proveedor.
- Rate limit: máx 3 solicitudes por `telefono_hash` y máx 5 por `ip_solicitante` en 1 hora. El lockout se evalúa **por `telefono_hash`** (cuenta todas las solicitudes del número), no solo por la fila/OTP actual (ver §15.6).

Lógica:
1. Normaliza teléfono → `telefono_norm`; `telefono_hash = hash_hmac('sha256', telefono_norm, $pepper)` (pepper desde env/`config/global.php`; NUNCA SHA-256 plano — §15.6).
2. Genera `confirmation_code` (32 hex / 128 bits) y OTP de 6 dígitos; `otp_hash = hash_hmac('sha256', otp, $pepper)`; `otp_expira = now()+10min`.
3. Crea/actualiza solicitud `estado='pendiente'`, `source='formulario'`.
4. Envía OTP por WhatsApp en **tiempo constante / desacoplado del response**: el envío real (`WhatsAppClient::sendText`) se encola/dispara de forma asíncrona para que la latencia del response no revele si el número existe (§15.7). Si no hay infra de cola, el handler debe responder antes de esperar la respuesta de Meta y normalizar el tiempo de proceso.
5. **Responde siempre igual** exista o no el número (anti-enumeración).

Response (siempre, exista o no — `confirmation_code` de 32 hex):
```json
{ "ok": true, "data": { "next": "otp", "masked": "••••8402", "confirmation_code": "3f9a1c0b7d2e4a55b1c2d3e4f5061728" } }
```
Errores:
| Caso | HTTP | Cuerpo |
|---|---|---|
| captcha inválido | 400 | `{"ok":false,"error":"Verificación de seguridad fallida"}` |
| rate limit excedido | 429 | `{"ok":false,"error":"Demasiados intentos, probá más tarde"}` |
| teléfono mal formado | 400 | `{"ok":false,"error":"Número inválido"}` |
| fallo envío WA | 200 | igual a éxito (no se filtra; queda registrado en audit como error) |

### 8.3 H3 — Verificar OTP

```
POST /ajax/eliminacion_publica.php?t=<slug>
{ "op": "verificar", "confirmation_code": "3f9a1c0b7d2e4a55b1c2d3e4f5061728", "otp": "482915" }
```
Validaciones:
- `confirmation_code`: requerido, **32 hex**.
- `otp`: requerido, 6 dígitos.

Lógica: busca por `confirmation_code` en estado `pendiente`; verifica `otp_expira > now()`, `otp_intentos < 5`, **y lockout agregado por `telefono_hash`** (§15.6); compara `hash_equals(otp_hash, hash_hmac('sha256', otp, $pepper))`. En éxito → `estado='verificada'`, `verificada_at=now()`, invalida OTP (`otp_hash=NULL`). Cada fallo incrementa `otp_intentos`.

Response éxito:
```json
{ "ok": true, "data": { "estado": "verificada", "confirmation_code": "3f9a1c0b7d2e4a55b1c2d3e4f5061728" } }
```
Errores:
| Caso | HTTP | Cuerpo |
|---|---|---|
| OTP incorrecto | 400 | `{"ok":false,"error":"Código incorrecto"}` |
| OTP vencido | 400 | `{"ok":false,"error":"El código venció, solicitá uno nuevo"}` |
| Demasiados intentos | 429 | `{"ok":false,"error":"Demasiados intentos"}` |
| Código inexistente | 400 | `{"ok":false,"error":"Código incorrecto"}` (mismo msg, anti-enumeración) |

### 8.4 Consulta de estado pública

```
GET /ajax/eliminacion_publica.php?op=estado&code=3f9a1c0b7d2e4a55b1c2d3e4f5061728&t=<slug>
```
Validaciones / hardening (§15.5):
- `code`: requerido, 32 hex.
- **Rate limit** por `ip_solicitante` (mismo umbral que las demás ops) para impedir fuerza bruta del `confirmation_code` (mitigado además por los 128 bits de entropía).
- **Respuesta uniforme HTTP 200** tanto para código existente como inexistente — no se devuelve 404 distinguible, para no permitir oráculo de existencia.

Response (sin PII; idéntico shape exista o no — campos vacíos si no existe):
```json
{ "ok": true, "data": { "estado": "en_proceso", "creada": "2026-06-02", "plazo": "2026-06-12" } }
```
| Caso | HTTP | Cuerpo |
|---|---|---|
| código inexistente | 200 | `{ "ok": true, "data": { "estado": "no_encontrada", "creada": null, "plazo": null } }` (uniforme) |
| rate limit excedido | 429 | `{"ok":false,"error":"Demasiados intentos, probá más tarde"}` |

### 8.5 H4 — Operador: listar

```
GET /ajax/eliminacion.php?op=listar   (requiere config/auth.php + permiso)
Filtros opcionales: &estado=&origen=&code=
```
Auth: `require_once '../config/auth.php'` (401 si no hay `$_SESSION['idusuario']`) + chequeo de permiso (§10). Scope estricto: **todas las queries operan sobre la DB del tenant activo** (`tenant_db`), sin parámetro de tenant cross — previene IDOR.

> **[fix de auditoría — §15.8]** `config/auth.php` hoy NO valida CSRF. Las operaciones de **estado-cambiante** (`op=aprobar`, `op=rechazar`) deben exigir un **token CSRF** (`$_SESSION['csrf_token']` emitido al renderizar `vistas/eliminaciones.php`, enviado en header `X-CSRF-Token` o campo y comparado con `hash_equals`). `op=listar` (GET, idempotente) queda exento.

Response: filas en formato DataTables, teléfono **enmascarado** (`••••8402`), nunca completo.

### 8.6 H4 — Operador: aprobar (ejecuta anonimización)

```
POST /ajax/eliminacion.php?op=aprobar
{ "id": 42 }
```
Validaciones: **token CSRF válido** (§15.8); `id` integer > 0; la solicitud debe pertenecer al tenant activo (implícito por `tenant_db`); estado debe ser `verificada` o `pendiente_identificacion`+teléfono provisto manualmente.

Lógica: setea `en_proceso` → instancia `EliminacionDatos::ejecutar($id)` (§9, anonimización multi-tabla) → `completada` y anula PII residual del propio request.

Response éxito:
```json
{ "ok": true, "data": { "estado": "completada", "tablas": { "clientes": 1, "pedidos": 7, "reclamos": 2, "contactos": 1, "telefonos": 1 } } }
```
Errores:
| Caso | HTTP | Cuerpo |
|---|---|---|
| estado no aprobable | 409 | `{"ok":false,"error":"La solicitud no está en un estado aprobable"}` |
| pendiente_identificacion sin teléfono | 422 | `{"ok":false,"error":"Falta identificar el teléfono del titular"}` |
| fallo transaccional | 500 | `{"ok":false,"error":"No se pudo completar la anonimización (rollback aplicado)"}` |

### 8.7 H4 — Operador: rechazar

```
POST /ajax/eliminacion.php?op=rechazar
{ "id": 42, "motivo": "No se pudo verificar la identidad" }
```
Validaciones: **token CSRF válido** (§15.8); `id` int>0; `motivo` requerido, max 255. Setea `estado='rechazada'`, `motivo_rechazo`, `resuelta_por=idusuario`, audita.

Response: `{ "ok": true, "data": { "estado": "rechazada" } }`.

---

## 9. Pseudo-algoritmo del borrado transaccional (H5)

`EliminacionDatos::ejecutar(int $requestId): array` — todo en una transacción MySQLi (`begin_transaction()` / `commit()` / `rollback()`), prepared statements en cada query con input.

> **[fix de auditoría — §15.9] Cláusulas `IN(...)` con listas de IDs.** MySQLi **no expande arrays** en un único placeholder `?`. Cada `IN ($ids)` del pseudocódigo debe construir placeholders dinámicos `implode(',', array_fill(0, count($ids), '?'))`, bindear cada elemento (`bind_param` con string de tipos dinámico o `...$args`), y **manejar el caso de lista vacía**: si `$ids`/`$b2cIds`/`$pedidoIds` quedan vacíos, **omitir esa porción del WHERE** (o saltar el statement) — nunca emitir `IN ()`, que es SQL inválido. Las notaciones `IN($ids)` de abajo son **taquigrafía**, no SQL literal a concatenar.

```
1.  Cargar request por id. Si estado ∉ {verificada, pendiente_identificacion(con tel)} → throw.
2.  $tel = request.telefono_norm  (debe existir; si no, abortar).
3.  begin_transaction()
4.  TRY:
    a. Resolver IDs del titular:
       - $clienteIds  = SELECT id, codigo FROM clientes
                        WHERE telefono = ?  (prepared)
       - $b2cIds      = SELECT id FROM contactosb2c WHERE telefono = ?
       - $clienteIdsT = SELECT clienteId FROM telefonos WHERE telefono = ?
       - unir todos los clienteId/codigo encontrados → $ids
       - $pedidoIds   = SELECT pedidoid FROM pedidos WHERE telefono = ? OR clienteId IN ($ids)
    b. ANONIMIZAR clientes:        UPDATE clientes SET razonSocial='[ELIMINADO]', direccion='',
                                   telefono='', localidad='', latitud='0', longitud='0'
                                   WHERE telefono=? OR id IN($ids) OR codigo IN($ids)
       → audit('tabla_anonimizada','clientes', affected)
    c. ANONIMIZAR contactosb2c:    UPDATE ... SET razonSocial='[ELIMINADO]', direccion='',
                                   telefono='', localidad='', cuit='' WHERE telefono=? OR id IN($b2cIds)
       → audit
    d. ANONIMIZAR reclamos:        UPDATE reclamos SET telefono='', nick='[ELIMINADO]',
                                   detalle='[ELIMINADO]', resolucion='[ELIMINADO]'
                                   WHERE telefono=? OR clienteId IN($ids)
       → audit
    e. ANONIMIZAR consultas:       (idéntico a reclamos) → audit
    f. ANONIMIZAR pedidos:         UPDATE pedidos SET telefono='',
                                   dato5='',dato6='',dato7='',dato8='',dato9=''
                                   WHERE telefono=? OR clienteId IN($ids)
       → audit  (se conservan importes para retención fiscal)
    g. ANONIMIZAR mensajes(B2B):   por cada fila con $tel en `destino` →
                                   reemplazar $tel por '[ELIMINADO]' → audit
    h. NEUTRALIZAR vínculo B2C:    (sin DELETE) el contactosb2c del paso c ya quedó anonimizado;
                                   el pivote mensajeb2c_contactob2c se conserva apuntando a un
                                   destinatario '[ELIMINADO]' → audit('tabla_anonimizada','contactosb2c')
    i. ANONIMIZAR telefonos (PK):  $tok = 'del_'.substr(telefono_hash,0,16)
                                   UPDATE telefonos SET telefono=? (=$tok)
                                   WHERE telefono=? OR clienteId IN($ids) → audit  (token pseudónimo, §13.1)
    j. ANONIMIZAR contactos (PK):  UPDATE contactos SET id=?, telefono='', nombre='[ELIMINADO]',
                                   anterior='', mensaje='' WHERE id=? OR telefono=?   (id → $tok)
       → audit  (token pseudónimo en la PK wa_id)
    k. ANONIMIZAR fidelizar:       UPDATE fidelizar SET mensaje='[ELIMINADO]'
                                   WHERE pedidoid IN($pedidoIds) → audit  (se conserva la fila/pedido)
    l. UPDATE request SET estado='completada', resuelta_at=now(), resuelta_por=?,
            telefono_norm=NULL, otp_hash=NULL   (purga PII residual del propio request)
    m. audit('aprobada', actor='usuario:<id>')
    commit()
    return ['ok'=>true, 'tablas'=>{...affected counts...}]
5.  CATCH:
    rollback()
    audit('error', detalle=mensaje_sin_PII)
    UPDATE request SET estado='verificada'  (revertir a previo)
    return ['ok'=>false, 'error'=>'No se pudo completar la anonimización (rollback aplicado)']
```

> Nota: este flujo **anonimiza, no borra** (RESUELTO §13.1). No hay ningún `DELETE` físico. Donde la PII es PK (`telefonos`, `contactos`) se reemplaza por el token `del_<hash16>` (§6).

Garantías:
- **Atómico**: o se anonimiza todo o nada (rollback).
- **Idempotente**: re-ejecutar sobre datos ya anonimizados no cambia nada (los `UPDATE` por teléfono real no encuentran filas; el token `del_<hash16>` es determinístico → reaplicarlo da el mismo valor); el request ya en `completada` se rechaza en el paso 1.
- **Auditoría completa**: cada tabla deja una fila en `data_deletion_audit` (`accion='tabla_anonimizada'`) sin PII en claro.
- **Integridad referencial**: ninguna fila se elimina; padres (`clientes`, `contactosb2c`) se anonimizan en sitio y donde la PII es PK se usa token pseudónimo determinístico, preservando todas las FKs/vínculos.

---

## 10. Permisos

- Permiso nuevo: `Eliminación de datos` (insertar en tabla `permiso` de cada tenant).
- Migración idempotente: `INSERT INTO permiso (nombre) SELECT 'Eliminación de datos' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permiso WHERE nombre='Eliminación de datos');`
- Asignado por defecto a: **Admin tenant**. Operadores: opt-in vía `usuario_permiso`.
- En `ajax/eliminacion.php`, tras `config/auth.php`, verificar que el `idusuario` tenga el permiso (patrón existente de `ajax/usuario.php` caso `permisos`).
- Endpoints públicos (`eliminacion_publica.php`, callback) **no** requieren permiso (no hay sesión admin) — su control es captcha + OTP + HMAC.

---

## 11. Consideraciones multi-tenant

- Las tablas `data_deletion_requests` / `data_deletion_audit` viven en **cada DB de tenant**; el aislamiento es la DB misma (no hay columna `tenant_id`).
- En el **panel** (`ajax/eliminacion.php`), `WA_APP_SECRET`, `WA_PHONE_NUMBER_ID`, `WA_ACCESS_TOKEN` se cargan per-tenant en `config/Conexion.php` a partir de `$_SESSION['tenant_db']`.
- **Contexto público (sin sesión del panel):** el tenant y las credenciales WhatsApp se resuelven en **variables locales** desde `?t=<slug>` validado contra `pedidos_platform.tenants`, con cookie de sesión aislada (`session_name('ATIENDE_PUB')`). **Nunca** se escribe `$_SESSION['tenant_db']` desde contexto público (anti session-fixation / IDOR cross-tenant — §15.1).
- No afecta el flujo de provisioning de `pedidos-platform`; sí requiere que el superadmin configure la URL del callback per-tenant en Meta (§13.7).
- Funciona igual en modo B2B (`clientes`), B2C (`contactosb2c`) y Mix (ambas) — el algoritmo recorre las dos rutas y omite las tablas vacías.

---

## 12. Validaciones y flujos de error (resumen anti-abuso)

| Escenario | Manejo |
|---|---|
| OTP vencido | `otp_expira` chequeado; mensaje "El código venció, solicitá uno nuevo"; permite reenvío |
| Número inexistente | Respuesta idéntica a número existente (anti-enumeración); no se envía WA pero la API responde `ok:true` con `masked` genérico |
| Firma HMAC inválida (callback) | base64url estricto + rechazo `algorithm != HMAC-SHA256` + guard `WA_APP_SECRET` no vacío; `hash_equals` falla → HTTP 400, no se procesa, se audita (§15.4) |
| Rate limit | índice `idx_ip_created`; 3/teléfono (por `telefono_hash`) y 5/IP por hora → HTTP 429; aplica también a `op=estado` (§15.5) |
| Captcha fallido | HTTP 400 antes de generar OTP |
| IDOR cross-tenant (panel) | Imposible por diseño: el handler opera solo sobre `tenant_db` de la sesión; no acepta parámetro de tenant; el `id` se busca dentro de la DB del tenant |
| Session fixation / IDOR cross-tenant (público) | Páginas públicas NUNCA escriben `$_SESSION['tenant_db']`; cookie aislada `ATIENDE_PUB` + conexión MySQLi local (§15.1) |
| CSRF en operaciones del operador | Token CSRF requerido en `op=aprobar`/`op=rechazar` (§15.8) |
| Hashes reversibles por diccionario | `telefono_hash`/`otp_hash` con HMAC-SHA256 + pepper (no SHA-256 plano) (§15.6) |
| Timing oracle de existencia | Envío de OTP desacoplado/constante (§15.7) |
| Reuso de OTP | `otp_hash=NULL` al verificar (un solo uso) |
| Reejecución de borrado | Idempotente (§9) |
| PII en logs/audit | Prohibido guardar teléfono/nombre en claro en `data_deletion_audit.detalle` |

---

## 13. Decisiones (resueltas + recomendaciones del Analista)

> Las decisiones 1–6 fueron **RESUELTAS por el usuario el 2026-06-02**. Las 7–8 son recomendaciones del Analista Funcional ya adoptadas en el diseño.

1. **[RESUELTA 2026-06-02] Política borrado vs anonimización por tabla → ANONIMIZAR TODO.** Ninguna tabla se borra físicamente. Todas las tablas con PII del titular se **anonimizan en sitio**, incluidas ventas/pagos (`pedidos`, conserva importes) y conversaciones de WhatsApp (`contactos`, `mensajes`, `mensajesb2c`/pivote, `fidelizar`). **Excepción técnica justificada:** en `telefonos` (PK = `telefono`) y `contactos` (PK `id` = wa_id), la PII *es* la clave primaria; anonimizar a un valor fijo violaría unicidad/PK y rompería FKs. Para esos casos se usa un **token pseudónimo determinístico irreversible** `del_<hash16>` (donde `hash16` = primeros 16 hex de `telefono_hash`, HMAC-SHA256+pepper, §15.6) que preserva unicidad y vínculos sin exponer el teléfono. Mapa completo en §6.
2. **[RESUELTA 2026-06-02] SLA de resolución → 10 días hábiles.** Se muestra la fecha límite en la página de estado pública y se calcula como `created_at + 10 días hábiles`.
3. **[RESUELTA 2026-06-02] H2 (callback Meta) → SÍ entra en el alcance del release.** El callback firmado se implementa (no queda detrás de flag ni diferido). El orden de implementación sigue siendo el último (H1→H5→H4→H3→**H2**), pero forma parte del entregable. Nota técnica vigente: el `user_id` de Meta NO mapea al teléfono → las solicitudes del callback nacen en estado `pendiente_identificacion`.
4. **[RESUELTA 2026-06-02 — implícita en #5/#6] Resolución del tenant en el formulario público.** Se mantiene slug en query string (`?t=<slug>`) validado contra `tenants`, resuelto en variable local (§4). La página de *instrucciones* es genérica de Atiende; el `?t=` es necesario solo para enrutar el OTP/borrado a la DB correcta del tenant.
5. **[RESUELTA 2026-06-02] Texto por tenant vs genérico → PÁGINA GENÉRICA DE ATIENDE.** La página pública NO muestra razón social, CUIT ni datos por tenant. Texto único de marca Atiende, con responsable = Atiende (ver #6). Se eliminan los placeholders `[RAZÓN SOCIAL]`/`[EMAIL]` por tenant.
6. **[RESUELTA 2026-06-02] Responsable ante AAIP → ATIENDE (plataforma).** Atiende figura como **responsable único de la base de datos** ante la AAIP. Los tenants actúan como **encargados del tratamiento** (operan los datos por cuenta del responsable). El contacto que se publica es el de Atiende, no el del tenant.
7. **[Adoptada] Cómo Meta entrega el app_secret/tenant al callback.** **Una URL de callback por tenant** con `?t=<slug>` (Meta no envía tenant). El superadmin configura la URL específica en cada app de WhatsApp del tenant. El `app_secret` se carga per-tenant desde `tenants`.
8. **[Adoptada] Retención de la auditoría.** Conservar `data_deletion_audit` **5 años** (evidencia de cumplimiento), sin PII en claro. `data_deletion_requests` no se purga mientras tenga auditoría asociada (FK `RESTRICT`, §15.2); tras `completada` conserva solo `telefono_hash` y `confirmation_code`.

---

## 14. Criterios de Done por historia

### H1 — `eliminar-datos.php`
- [ ] Página pública en raíz, sin login, HTTPS, `<meta robots="index,follow">`.
- [ ] Enlazada desde `politica-privacidad.php`.
- [ ] Card pattern / estilo coherente con `politica-privacidad.php` (navbar-atiende).
- [ ] **Texto genérico de Atiende** (sin razón social ni datos por tenant — §13.5); responsable = Atiende (§13.6); SLA fijo "10 días hábiles" (§13.2).

### H2 — Callback Meta
- [ ] Valida HMAC-SHA256 con `WA_APP_SECRET` per-tenant usando `hash_equals`.
- [ ] Responde `{url, confirmation_code}` con la URL real del tenant.
- [ ] Idempotente por `meta_user_id`; crea estado `pendiente_identificacion`.
- [ ] Firma inválida → 400 sin procesar; auditado.

### H3 — Formulario + OTP
- [ ] OTP de 6 dígitos enviado por WhatsApp vía `WhatsAppClient::sendText` + `normalizePhone()` (strip del `9` móvil **solo para `54`** — §15.3).
- [ ] OTP **hasheado con HMAC-SHA256 + pepper** (no SHA-256 plano), expiración 10 min, un solo uso, máx 5 intentos; lockout por `telefono_hash` (§15.6).
- [ ] Anti-enumeración (respuesta uniforme + envío de OTP en tiempo constante, §15.7), rate limit, captcha.
- [ ] `confirmation_code` de 32 hex (128 bits).
- [ ] Verificación pasa la solicitud a `verificada`.

### H4 — Operador + estado público
- [ ] `vistas/eliminaciones.php` con card pattern (filtros + tabla DataTables + detalle).
- [ ] `ajax/eliminacion.php` con `config/auth.php` y permiso `Eliminación de datos`.
- [ ] Scope estricto por `tenant_db`; sin parámetro cross-tenant (sin IDOR).
- [ ] Estados: pendiente / verificada / pendiente_identificacion / en_proceso / completada / rechazada.
- [ ] `estado-eliminacion.php` muestra estado por `confirmation_code` sin PII, con plazo "hasta 10 días hábiles" desde la recepción (§13.2).
- [ ] Teléfono siempre enmascarado en la UI.

### H5 — Anonimización
- [ ] `modelos/EliminacionDatos.php` transaccional (commit/rollback).
- [ ] **Anonimiza, no borra**: ningún `DELETE` físico (RESUELTO §13.1).
- [ ] Cubre Persona, Reclamo, Consulta, Venta, Reparto, MensajeB2B, MensajeB2C según §6.
- [ ] PII-en-PK (`telefonos.telefono`, `contactos.id`) resuelta con token pseudónimo determinístico `del_<hash16>` (preserva PK/unicidad/FKs).
- [ ] Prepared statements en toda query con input del usuario.
- [ ] Auditoría completa sin PII en claro (`accion='tabla_anonimizada'`).
- [ ] Idempotente; integridad referencial preservada (sin filas eliminadas).
- [ ] Tablas creadas en dump base `_docker/mariadb/` + migración idempotente en todos los `wb_*` y `atiende`.

### Transversales
- [ ] Respuestas JSON `{ok, data/error}`.
- [ ] Funciona en modo B2B, B2C y Mix.
- [ ] Ninguna credencial ni PII en claro en logs.
- [ ] Se cumplen todos los fixes de §15 (Endurecimiento de Seguridad).

---

## 15. Endurecimiento de Seguridad (hallazgos de auditoría de diseño)

QA y Security Audit revisaron esta spec y detectaron defectos de **diseño** (no de implementación). Cada fix es requisito obligatorio; los §15.x se referencian desde las secciones afectadas.

### 15.1 [CRÍTICO] Aislamiento de sesión en contexto público
**Defecto:** la versión previa de §4 seteaba `$_SESSION['tenant_db']` desde páginas públicas → session fixation / IDOR cross-tenant (un operador logueado que abriera `?t=otroTenant` reapuntaba su panel a otra DB).
**Fix:** las páginas públicas **NUNCA** escriben `$_SESSION['tenant_db']`. Resolver el tenant en **variable local** + conexión MySQLi dedicada, y aislar la cookie con `session_name('ATIENDE_PUB')` antes de `session_start()`. Patrón de código en §4. El panel sigue usando exclusivamente su propia sesión (`PHPSESSID`).

### 15.2 [ALTO] Contradicción CASCADE vs retención de auditoría
**Defecto:** `fk_audit_request ... ON DELETE CASCADE` (§5.2) destruía la evidencia legal al purgar un request, contradiciendo la retención de 5 años (§13.8).
**Fix:** FK cambiada a `ON DELETE RESTRICT`. `data_deletion_requests` **no se purga** mientras tenga auditoría asociada; tras `completada` solo se anula su PII residual (`telefono_norm`, `otp_hash`), conservando `confirmation_code`, `telefono_hash` y toda la auditoría durante el período de retención.

### 15.3 [MEDIO] `normalizePhone()` específico de Argentina (`54`)
**Defecto:** riesgo de aplicar el strip del `9` móvil a todos los países.
**Fix:** el strip del `9` aplica **SOLO** a números que empiezan con `54` (Argentina). Para Brasil (`55`), México (`52`) y demás, el `9` es legítimo y **NO** se toca. Es requisito explícito del flujo OTP: enviar al número equivocado por mal-normalizar rompe la verificación de identidad. (Coincide con el comportamiento actual de `config/WhatsAppClient.php::normalizePhone()`, que matchea `^549(\d{8,10})$`; documentado aquí para que no se generalice por error.)

### 15.4 [ALTO] Validación robusta de la firma del callback (H2)
- Decodificación **base64url estricta** (`strtr('-_','+/')` + padding + `base64_decode($x, true)`); fallo de decodificación → 400.
- Rechazar el payload si `data['algorithm'] !== 'HMAC-SHA256'` (defensa contra downgrade de algoritmo) → 400.
- Guard previo: `if (!isset($appSecret) || $appSecret === '')` → 500 `"callback no disponible"`, **antes** de cualquier comparación HMAC (evita validar contra secret vacío).
- Comparación con `hash_equals`. (Detalle en §8.1.)

### 15.5 [ALTO] Rate limit y respuesta uniforme en `op=estado`
- `op=estado` aplica el **mismo rate limit** por `ip_solicitante` que el resto de las ops públicas (anti fuerza bruta del `confirmation_code`).
- Respuesta **uniforme HTTP 200** exista o no el código (estado `"no_encontrada"` con campos nulos) — sin 404 distinguible que actúe como oráculo de existencia. (Detalle en §8.4.)

### 15.6 [ALTO] Hashes con HMAC + pepper y lockout por teléfono
- `otp_hash` y `telefono_hash` se calculan con `hash_hmac('sha256', $valor, $pepper)`, **NUNCA** SHA-256 plano: un dump de la DB con SHA-256 plano permitiría revertir teléfonos por diccionario (espacio de números acotado).
- `$pepper` vive **fuera de la DB del tenant**, en `config/global.php` / variable de entorno (no versionado).
- **Lockout agregado por `telefono_hash`** (cuenta todas las solicitudes/intentos del número), no solo por la fila de OTP actual — impide rotar `confirmation_code` para evadir el contador.
- `confirmation_code` elevado a **32 hex / 128 bits** (`random_bytes(16)`), no enumerable.

### 15.7 [ALTO] Envío de OTP en tiempo constante / asíncrono
**Defecto:** enviar el WhatsApp solo cuando el número existe filtra existencia por **timing** (latencia distinta), rompiendo la anti-enumeración aunque el cuerpo de la respuesta sea uniforme.
**Fix:** desacoplar el envío del response (cola/job asíncrono) o normalizar el tiempo de proceso, de modo que la latencia sea indistinguible entre número existente e inexistente. (Detalle en §8.2 paso 4.)

### 15.8 [ALTO] CSRF en `ajax/eliminacion.php`
**Defecto:** `config/auth.php` valida sesión pero **no** CSRF; las operaciones estado-cambiantes quedaban expuestas a CSRF.
**Fix:** `op=aprobar` y `op=rechazar` exigen token CSRF (`$_SESSION['csrf_token']` emitido al renderizar `vistas/eliminaciones.php`, recibido en `X-CSRF-Token`/campo y comparado con `hash_equals`). `op=listar` (GET idempotente) exento. (Referenciado en §8.5–§8.7.)

### 15.9 [ALTO] `IN(...)` con placeholders dinámicos y lista vacía
**Defecto:** los `?` de MySQLi no expanden arrays; los `IN ($ids)` del §9 eran taquigrafía riesgosa.
**Fix:** construir placeholders dinámicos (`implode(',', array_fill(0, count($ids), '?'))`), bindear cada elemento, y **manejar lista vacía** omitiendo esa porción del WHERE (nunca emitir `IN ()`, SQL inválido). (Nota en §9.)
