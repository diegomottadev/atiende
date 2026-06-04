# pedidos-platform — Spec de Diseño

**Fecha:** 2026-05-26  
**Proyecto:** pedidos-platform  
**Stack:** PHP puro + MariaDB + PHPMailer  
**Relación con app existente:** separada, no modifica código de Atiende/Atiende

---

## Contexto

Atiende / Atiende es un sistema multi-tenant SaaS de gestión de pedidos y reclamos integrado con WhatsApp Business API (oficial de Meta). Hoy el alta de cada cliente (tenant) es 100% manual: Diego ejecuta un script bash por empresa, configura variables de entorno y conecta WhatsApp a mano.

**Objetivo:** construir `pedidos-platform`, una app PHP separada que automatiza la compra, el provisioning del tenant y ofrece autogestión al cliente — sin tocar el código del app existente.

---

## Modelo de deployment asumido

`pedidos-platform` asume que **todos los tenants comparten un único servidor MariaDB** (modelo SaaS centralizado). Esto difiere del modelo actual donde cada empresa tiene su propio stack Docker aislado. La plataforma nueva establece el nuevo patrón de alta para clientes futuros; los clientes existentes (campostrini, faustina, termoplastica) migran en una fase posterior fuera del alcance de este spec.

El servidor MariaDB centralizado contiene:
- `saas_platform` — DB exclusiva de la plataforma (tenants, plans, subscriptions, users)
- `axbot` — DB compartida del sistema de bots (tabla `empresa` con una fila por tenant)
- `wb_{slug}` — una DB operativa por tenant (ej: `wb_acme`)

El slug del tenant se usa como nombre de DB (`wb_{slug}`) y como identificador en `axbot.empresa.empresa`. El `clave` en `axbot.empresa` es un token interno generado al provisionar, distinto del Access Token de Meta.

---

## Alcance

| Incluido | Excluido |
|---|---|
| Landing pública con planes y checkout | Modificar el código del app Atiende existente |
| Cobro recurrente MercadoPago + Stripe | Embedded Signup de Meta |
| Provisioning automático de tenant al pagar | App mobile |
| Panel superadmin para Diego | Soporte multi-idioma |
| Portal de autogestión del tenant | Reportes avanzados / BI |
| Emails transaccionales via PHPMailer + SMTP | Migración de tenants existentes |

---

## Arquitectura general

```
pedidos-platform/
├── landing/            ← público: planes, formulario, checkout
├── superadmin/         ← solo Diego: tenants, WhatsApp, suscripciones
├── portal/             ← tenant autenticado: bot, usuarios, suscripción
├── webhooks/           ← endpoints públicos MP + Stripe
├── modelos/            ← lógica de negocio compartida
│   ├── ProvisioningService.php
│   ├── BillingService.php
│   ├── MailService.php
│   └── Auth.php
├── templates/
│   ├── sql/
│   │   ├── tenant_schema.sql    ← schema base de la DB wb_{slug}
│   │   └── axbot_row_insert.sql ← template INSERT para axbot.empresa
│   └── bot/
│       └── default_menu.json    ← JSON inicial del BotEngine por defecto
├── config/             ← DB, env, constantes globales
└── vendor/             ← Composer: PHPMailer, Stripe PHP SDK
```

La app vive en su propio dominio (ej: `platform.pedidos.app`). El app Atiende existente sigue operando sin cambios.

---

## Base de datos: `saas_platform`

### `plans`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED AUTO_INCREMENT PK | |
| nombre | VARCHAR(100) | Nombre del plan |
| precio_ars | DECIMAL(10,2) | Precio en pesos |
| precio_usd | DECIMAL(10,2) | Precio en dólares |
| activo | TINYINT(1) DEFAULT 1 | Si se muestra en landing |

**Nota:** todos los planes tienen las mismas funcionalidades; el precio es la única diferencia (ej: plan mensual vs anual). No hay límites por plan. Desactivar un plan lo oculta de la landing pero no afecta suscripciones existentes. Los registros de `plans` nunca se borran (integridad referencial).

### `tenants`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED AUTO_INCREMENT PK | |
| slug | VARCHAR(50) UNIQUE | `/^[a-z][a-z0-9_]{1,49}$/` — usado como nombre de DB `wb_{slug}` y como `empresa` en `axbot` |
| nombre | VARCHAR(250) | Nombre de la empresa |
| email | VARCHAR(255) | Email del admin del tenant |
| estado | ENUM | `pendiente_pago`, `pendiente_whatsapp`, `activo`, `suspendido`, `cancelado` |
| db_name | VARCHAR(60) | Nombre real de la DB (formato `wb_{slug}`) |
| plan_id | INT UNSIGNED FK | |
| whatsapp_phone_id | VARCHAR(100) NULL | Phone Number ID de Meta (carga Diego) |
| whatsapp_waba_id | VARCHAR(100) NULL | WABA ID de Meta (carga Diego) |
| whatsapp_token_enc | TEXT NULL | Access Token de Meta cifrado con AES-256-GCM (ver sección Seguridad) |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |

### `checkout_sessions`
Tabla puente entre el formulario de compra y el webhook de pago.

| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED AUTO_INCREMENT PK | |
| session_token | VARCHAR(64) UNIQUE | Token aleatorio generado al submit del form, pasado como metadata al proveedor |
| nombre | VARCHAR(250) | Nombre del comprador |
| empresa | VARCHAR(250) | Nombre de la empresa |
| email | VARCHAR(255) | Email |
| plan_id | INT UNSIGNED FK | |
| provider | ENUM | `mercadopago`, `stripe` |
| provider_ref | VARCHAR(255) NULL | ID de suscripción/preference del proveedor (se actualiza tras redirect) |
| estado | ENUM | `pendiente`, `procesando`, `completado`, `fallido` |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |

### `subscriptions`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED AUTO_INCREMENT PK | |
| tenant_id | INT UNSIGNED FK | |
| provider | ENUM | `mercadopago`, `stripe` |
| provider_subscription_id | VARCHAR(255) UNIQUE | ID de suscripción en el proveedor |
| estado | ENUM | `activa`, `vencida`, `cancelada` |
| periodo_fin | DATETIME | Próximo vencimiento; se actualiza en cada renovación exitosa |
| grace_period_fin | DATETIME NULL | Si no es NULL, tenant en período de gracia (suspender recién al vencer) |
| payment_failure_count | TINYINT UNSIGNED DEFAULT 0 | Contador de fallos de cobro; se resetea a 0 en cada renovación exitosa; al llegar a 3 dispara suspensión |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |
| deleted_at | DATETIME NULL | Soft-delete: se setea en teardown, registro se purga a los 90 días |

### `platform_users`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED AUTO_INCREMENT PK | |
| tenant_id | INT UNSIGNED FK NULL | NULL = superadmin (Diego) |
| email | VARCHAR(255) UNIQUE | |
| password_hash | VARCHAR(255) | bcrypt con cost 12 |
| rol | ENUM | `superadmin`, `tenant_admin` |
| activo | TINYINT(1) DEFAULT 1 | |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |
| last_login | DATETIME NULL | |
| deleted_at | DATETIME NULL | Soft-delete en teardown |

### `webhook_events`
Tabla de idempotencia para webhooks.

| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED AUTO_INCREMENT PK | |
| provider | ENUM | `mercadopago`, `stripe` |
| provider_event_id | VARCHAR(255) | ID único del evento en el proveedor |
| evento | VARCHAR(100) | Nombre del evento (ej: `authorized_payment`, `invoice.payment_succeeded`) |
| procesado | TINYINT(1) DEFAULT 0 | 1 = ya procesado, ignorar reenvíos |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |
| UNIQUE KEY | (provider, provider_event_id) | |

---

## Flujo de compra y provisioning

```
1. Cliente entra a landing → ve planes → completa formulario (nombre, empresa, email, plan)
2. Sistema genera session_token, inserta fila en checkout_sessions (estado: pendiente)
3. Elige proveedor de pago → redirige a checkout con session_token como metadata
4. Proveedor confirma pago → dispara webhook a /webhooks/mp.php o /webhooks/stripe.php
5. Webhook handler:
   a. Valida firma criptográfica del proveedor
   b. Idempotencia atómica: ejecuta INSERT IGNORE INTO webhook_events (...); si affected_rows = 0
      el evento ya fue procesado → responde 200 OK sin continuar (evita race condition en entregas simultáneas)
   c. Recupera checkout_session por session_token (metadata del proveedor)
   e. Llama ProvisioningService::provision()
6. ProvisioningService::provision() (pasos compensatorios, no transacción cross-DB):
   a. Genera slug único a partir del nombre de empresa (sanitizado, único en tenants.slug)
   b. Crea DB  wb_{slug}  en MariaDB
   c. Importa templates/sql/tenant_schema.sql en  wb_{slug}
   d. Inserta fila en axbot.empresa con json = contenido de templates/bot/default_menu.json
   e. Crea platform_user (tenant_admin) con password temporal
   f. Inserta tenant (estado: pendiente_whatsapp) y subscription en saas_platform
   g. Envía email de bienvenida via PHPMailer con credenciales del portal
   h. Marca webhook_event como procesado
   → Si cualquier paso b–g falla: ejecuta compensación (DROP DATABASE si fue creada,
     DELETE de axbot.empresa si fue insertada, estado tenant = pendiente_pago)
7. Diego recibe email de notificación automática → entra a superadmin
   → carga Phone Number ID + WABA ID + Token de WhatsApp
   → activa el tenant (estado: activo)
8. Tenant recibe email de activación → entra al portal
```

---

## Módulo: superadmin

**Acceso:** solo `platform_users` con `rol = superadmin`

### Vistas
- **Dashboard:** lista de tenants con estado, plan, fecha alta, `periodo_fin`. Badge visible de tenants en estado `pendiente_whatsapp`.
- **Detalle de tenant:** info del tenant, formulario para cargar/editar credenciales de WhatsApp. El token se muestra solo los últimos 8 caracteres; botón "Revelar" requiere reingreso de password de Diego.
- **Activar tenant:** solo disponible si `whatsapp_phone_id`, `whatsapp_waba_id` y `whatsapp_token_enc` están cargados. Cambia estado a `activo` y envía email de activación al tenant.
- **Planes:** CRUD de planes.
- **Suscripciones:** historial de pagos y renovaciones por tenant.

### Operaciones de ciclo de vida del tenant
- **Suspender:** cambia `tenants.estado` a `suspendido` y `axbot.empresa.activo` a 0.
- **Reactivar:** revierte suspensión.
- **Teardown (baja total):**
  - Solo disponible si la suscripción está en estado `cancelada`.
  - Requiere confirmación explícita con el slug del tenant (anti-accidente).
  - Ejecuta `ProvisioningService::teardown()`: DROP DATABASE `wb_{slug}`, DELETE de `axbot.empresa`, marca tenant como `cancelado`.
  - Los registros de `saas_platform` (tenants, subscriptions, platform_users) se conservan (soft-delete, `deleted_at` timestamp) por 90 días para auditoría.

---

## Módulo: portal del tenant

**Acceso:** `platform_users` con `rol = tenant_admin` y `tenants.estado = activo`

### Vista: Editor del bot
- Lee `axbot.empresa.json` del tenant (identificado por `tenants.slug = axbot.empresa.empresa`).
- Presenta formulario simplificado: solo edita el campo `consigna` (texto del mensaje) de cada nodo del menú. No expone la estructura JSON completa al usuario.
- Al guardar: valida que el JSON resultante sea válido contra el schema esperado por BotEngine (claves requeridas: `menuId`, `consigna`, `finaliza`). Si la validación falla, muestra error y no escribe en DB.
- Escribe JSON actualizado en `axbot.empresa.json`.

### Vista: Usuarios
- Lee la tabla `usuarios` de `axbot` filtrada por tenant (la conexión usa `db_name` del tenant para conectar a `wb_{slug}`, y la tabla `axbot.usuarios` no se expone al tenant — ver aclaración abajo).
- **Aclaración:** el portal gestiona únicamente los usuarios operativos de la DB del tenant (`wb_{slug}`). Las tablas concretas a gestionar (`vendedores`, `repartidores`, `usuarios` de la DB del tenant) se determinan al inspeccionar `atiende.sql` durante la implementación de Fase 4.
- Agregar usuario: nombre, email/usuario, rol, password temporal.
- Desactivar usuario (soft-delete: `activo = 0`).

### Vista: Mi suscripción
- Plan actual, precio, próxima fecha de cobro (`periodo_fin`).
- Si en período de gracia: banner de aviso con días restantes.
- Historial de pagos.
- Botón cancelar suscripción: llama al proveedor para cancelar la suscripción recurrente y cambia `subscriptions.estado` a `cancelada`. El tenant queda activo hasta `periodo_fin` (período pago), luego pasa a `suspendido`.

---

## Módulo: landing

- Página pública con los planes activos (query a `saas_platform.plans WHERE activo = 1`).
- Formulario: nombre completo, empresa, email, plan elegido.
- Validación de slug: el sistema genera el slug automáticamente desde el nombre de empresa; si hay colisión agrega sufijo numérico.
- Botón de pago por proveedor (MercadoPago para pagos en ARS, Stripe para USD).

---

## Módulo: webhooks

### `/webhooks/mp.php` — MercadoPago Preapproval (Suscripciones)
Eventos relevantes según la API de Preapproval de MercadoPago:
- `topic: subscription_preapproval`, `status: authorized` → primera activación → provisioning
- `topic: subscription_authorized_payment`, `status: processed` → renovación → actualizar `periodo_fin`
- `topic: subscription_preapproval`, `status: cancelled` → cancelación → iniciar grace period de 3 días, luego suspender

### `/webhooks/stripe.php` — Stripe Subscriptions
- `invoice.payment_succeeded` (primera vez, `billing_reason: subscription_create`) → provisioning
- `invoice.payment_succeeded` (renovación) → actualizar `periodo_fin`
- `customer.subscription.deleted` → iniciar grace period de 3 días, luego suspender
- `invoice.payment_failed` → incrementar contador de reintentos; tras 3 fallos suspender con email de aviso

**Grace period:** 3 días calendario entre el evento de cancelación/fallo y la suspensión efectiva del tenant. Durante el grace period el tenant opera normalmente pero recibe email de aviso.

---

## ProvisioningService (`modelos/ProvisioningService.php`)

```php
class ProvisioningService {
    public function provision(int $checkoutSessionId): void;
    public function suspend(int $tenantId): void;
    public function reactivate(int $tenantId): void;
    public function teardown(int $tenantId): void;   // solo desde superadmin, requiere suscripción cancelada
    public function updatePeriodFin(int $tenantId, DateTime $newPeriodFin): void;
}
```

`provision()` usa un modelo de **acciones compensatorias** (no transacción cross-DB):
- Cada paso registra en una variable local `$pasoActual`.
- El bloque `catch` ejecuta rollback específico según `$pasoActual`.
- Los pasos son idempotentes donde es posible (INSERT IGNORE, CREATE DATABASE IF NOT EXISTS).

---

## Auth y sesiones

- Login único en `/login.php`. Tras autenticar, redirige según rol: `superadmin` → `/superadmin/`, `tenant_admin` → `/portal/`.
- `session_regenerate_id(true)` al login y al logout.
- Cookie con atributos: `HttpOnly`, `Secure`, `SameSite=Strict`.
- La sesión almacena `user_id`, `rol`, `tenant_id`. Cada request en `portal/` verifica que `$_SESSION['tenant_id']` coincida con el recurso solicitado.
- Timeout de sesión: 2 horas de inactividad.

---

## Seguridad

### Cifrado de tokens de WhatsApp
- Algoritmo: AES-256-GCM (provee autenticación del ciphertext, evita manipulación).
- IV: 12 bytes aleatorios generados por `random_bytes(12)`, almacenados concatenados al ciphertext en `whatsapp_token_enc` (formato: `base64(iv + tag + ciphertext)`).
- Clave de cifrado: variable de entorno `PLATFORM_ENCRYPTION_KEY` (32 bytes hex). Nunca en DB ni en código.
- Rotación de clave: fuera del alcance de v1; se documenta como deuda técnica.
- El token nunca se envía al browser completo. El superadmin ve solo los últimos 8 caracteres; un botón "Revelar" requiere re-autenticación con password.

### Queries
- Todos los accesos a DB usan prepared statements con `mysqli_prepare` o PDO. Sin concatenación de strings en queries.

### Webhooks
- MercadoPago: validar header `X-Signature` con HMAC-SHA256.
- Stripe: validar header `Stripe-Signature` con `\Stripe\Webhook::constructEvent()`.
- Idempotencia garantizada por la tabla `webhook_events` con UNIQUE KEY en `(provider, provider_event_id)`.

### Base de datos — usuario de plataforma
- `pedidos-platform` usa un usuario MariaDB dedicado (`pp_app`) con privilegios mínimos:
  - `SELECT, INSERT, UPDATE, DELETE` en `saas_platform.*`
  - `SELECT, INSERT, UPDATE` en `axbot.empresa`
  - `CREATE, DROP` (solo para provisioning) gestionado por un segundo usuario `pp_provisioner` llamado exclusivamente desde `ProvisioningService`
  - Nunca `GRANT ALL`

### Slug validation
- Regex: `/^[a-z][a-z0-9_]{1,49}$/`
- Verificación de unicidad antes de usar como nombre de DB.

---

## Mailer: PHPMailer + SMTP

Dependencia: `phpmailer/phpmailer` vía Composer.  
Configuración via variables de entorno: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`.

Emails transaccionales:
| Evento | Destinatario | Contenido |
|---|---|---|
| Provisioning completado | Tenant | Credenciales de acceso al portal, link de acceso |
| Tenant en `pendiente_whatsapp` | Diego (superadmin) | Nombre de empresa, link al detalle en superadmin |
| Tenant activado | Tenant | Confirmación de activación, link de acceso |
| Inicio de grace period | Tenant | Aviso de problema de pago, días restantes |
| Suscripción cancelada | Tenant | Confirmación de cancelación, fecha de corte |

---

## Ciclo de vida completo del tenant

```
[tenants.estado]          [subscriptions.estado]

pendiente_pago
  → pago confirmado → pendiente_whatsapp / activa
  → Diego activa → activo / activa
  → renovación exitosa → activo / activa  (payment_failure_count reset a 0)
  → fallo de cobro (1er/2do) → activo / activa  (payment_failure_count++)
  → 3 fallos OR cancelación → activo + grace_period_fin = now()+3d / cancelada
  → grace_period_fin vencido (cron) → suspendido / cancelada
  → pago regularizado desde suspendido → activo / activa
  → teardown por Diego (requiere subscriptions.estado = cancelada) → cancelado (deleted_at = now()) / cancelada

Nota: "cancelado" en tenants.estado SOLO lo setea teardown (acción manual de Diego).
      La cancelación de suscripción por parte del tenant cambia subscriptions.estado = cancelada,
      pero tenants.estado permanece activo hasta que grace_period_fin venza (→ suspendido).

Re-suscripción:
- Tenant en suspendido (no teardown): nueva suscripción reactiva tenant existente, datos conservados
- Tenant en cancelado (post-teardown): nuevo alta completa, slug nuevo, DB nueva
```

**Grace period — mecanismo de ejecución:**
La transición `suspendido` al vencer `grace_period_fin` la ejecuta un cron job:
```
* * * * * php /path/to/pedidos-platform/cli/suspend_expired.php
```
`cli/suspend_expired.php` consulta subscriptions con `grace_period_fin <= NOW()` y tenants en estado `activo`, llama `ProvisioningService::suspend()` para cada uno, y resetea `grace_period_fin = NULL`.

---

## Conexión con app Atiende existente

La `pedidos-platform` **no modifica** el código del app Atiende. La integración es solo a nivel de datos en MariaDB:

| Acción en platform | Efecto en Atiende |
|---|---|
| Provisioning de nuevo tenant | Crea DB `wb_{slug}` + fila en `axbot.empresa` con `json = default_menu.json` |
| Diego carga credenciales WhatsApp | Actualiza `axbot.empresa.telefono` (número) y `tenants.whatsapp_token_enc` (token cifrado en saas_platform) |
| Tenant edita bot en portal | Actualiza `axbot.empresa.json` |
| Tenant suspendido | `axbot.empresa.activo = 0` |
| Teardown | DELETE de `axbot.empresa`, DROP DATABASE `wb_{slug}` |

**Nota sobre el Access Token de WhatsApp:** el token se almacena cifrado en `saas_platform.tenants.whatsapp_token_enc`. El app Atiende lee el token desde `config/global.php` (constante `WA_ACCESS_TOKEN`). En el modelo centralizado, el app Atiende deberá leer el token desde `saas_platform` en lugar de desde el archivo de configuración. Este es el **único cambio de comportamiento requerido en el app existente**, y es un cambio de fuente de lectura (no de lógica de negocio). Se implementa en una tarea de integración separada.

---

## Entregables por fase

### Fase 1 — Fundación
- Config, DB `saas_platform` (schema completo), auth, layout base, PHPMailer setup
- `templates/sql/tenant_schema.sql` (extraído de `atiende.sql`)
- `templates/bot/default_menu.json` (menu de bienvenida genérico)
- Usuario MariaDB `pp_app` y `pp_provisioner` con privilegios mínimos
- Inspección de `_docker/mariadb/atiende.sql` para confirmar estructura de `axbot.empresa` y tablas de usuarios/operadores del tenant (prerequisito de Fase 2 y Fase 4)
- `cli/suspend_expired.php` + entrada cron para grace period

### Fase 2 — Compra y provisioning
- Landing, checkout, `checkout_sessions`, integración MercadoPago Preapproval + Stripe Subscriptions
- Webhooks con validación de firma e idempotencia
- `ProvisioningService` con modelo compensatorio
- Emails transaccionales

### Fase 3 — Superadmin
- CRUD tenants, carga de credenciales WhatsApp (con masking), gestión de planes
- Flujo de activación, suspensión, teardown con confirmación

### Fase 4 — Portal del tenant
- Editor del bot con validación JSON
- Gestión de usuarios (tablas target definidas al inspeccionar `atiende.sql`)
- Vista de suscripción, grace period, cancelación

### Fase 5 — Integración con app existente
- Modificación mínima del app Atiende para leer `WA_ACCESS_TOKEN` desde `saas_platform` en lugar de `config/global.php`
