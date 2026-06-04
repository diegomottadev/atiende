# WhatsApp Cloud API Migration — Design Spec

**Date:** 2026-05-12  
**Scope:** Opción B — Reemplazo de transporte + limpieza  
**Status:** Approved

---

## Contexto

El sistema Atiende conectaba a WhatsApp via scraping de WhatsApp Web a través de un gateway externo Node.js (`WEB_MASTER`). Ese mecanismo fue bloqueado por Meta progresivamente. Este documento especifica la migración a la WhatsApp Business Cloud API oficial de Meta.

---

## Arquitectura

### Antes

```
Usuario WhatsApp
    ↕ (WebSocket)
Node.js Gateway (WEB_MASTER) — scraping de WhatsApp Web
    ↕ POST /ws/post.php  (payload formato _wabs_msg)
PHP App — retorna comandos JSON+"<ms>" para que el gateway envíe
```

### Después

```
Usuario WhatsApp
    ↕ (protocolo oficial Meta)
Meta Cloud API (servidores de Meta)
    ↕ POST /ws/webhook.php  (formato Meta estándar)
    ↕ GET  /ws/webhook.php  (verificación de webhook)
PHP App — llama directamente a Meta Graph API para enviar
```

`WEB_MASTER` sigue siendo consultado únicamente para obtener el menú JSON del tenant (configuración de empresa). Ya no interviene en el flujo de mensajes.

---

## Componentes

### 1. `ws/webhook.php` — Punto de entrada (nuevo)

Reemplaza a `ws/post.php`. Tres responsabilidades:

**GET — Verificación del webhook:**
```
Meta envía: ?hub.mode=subscribe&hub.challenge=XYZ&hub.verify_token=<token>
Responde:   echo $challenge  (solo si verify_token coincide con WA_VERIFY_TOKEN)
```

**POST — Verificación HMAC:**
Antes de procesar cualquier payload, verificar la firma `X-Hub-Signature-256`:
```php
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $rawBody, WA_APP_SECRET);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}
```
Si la firma no coincide, retornar 403 y no procesar.

**POST — Mensaje entrante:**
1. Verificar firma HMAC (arriba)
2. Decodificar el JSON de Meta
3. Verificar que exista la clave `messages` en el payload — si no existe (evento de estado de entrega), responder HTTP 200 inmediatamente y salir
4. Extraer y sanear: `$user`, `$pushname`, `$body`, `$type`
5. Manejar `bajacp` como caso especial antes de instanciar el engine (ver sección BotEngine)
6. Cargar configuración del tenant desde `WEB_MASTER` (igual que hoy)
7. Instanciar `WhatsAppClient` con credenciales del tenant
8. Instanciar `BotEngine` y llamar a `handle()`
9. Responder HTTP 200 siempre (incluso ante errores internos) para evitar re-entregas de Meta

**Formato de payload entrante (Meta):**
```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "changes": [{
      "value": {
        "metadata": { "phone_number_id": "..." },
        "contacts": [{ "profile": { "name": "Diego" }, "wa_id": "549..." }],
        "messages": [{
          "from": "549...",
          "type": "text",
          "text": { "body": "hola" }
        }]
      }
    }]
  }]
}
```

Tipos de mensaje a manejar: `text`, `location`. Todos los demás tipos (image, audio, video, document, status) responden HTTP 200 sin procesar.

**Payload de ubicación (location):**
```json
{
  "type": "location",
  "location": { "latitude": -34.87, "longitude": -58.54 }
}
```

---

### 2. `modelos/BotEngine.php` — Motor del bot (nuevo)

Extracción de la lógica de `ws/post.php` a una clase. Sin cambios en lógica de negocio.

```php
class BotEngine {
    public function __construct(
        array $menuJson,
        array $tenantConfig,
        WhatsAppClient $client
    ) { ... }

    public function handle(
        string $user,
        string $pushname,
        string $body,
        string $type,
        ?array $location = null
    ): void { ... }
}
```

**Caso especial `bajacp`:** si `$body === 'bajacp'` (case-insensitive), el engine ejecuta el DELETE + UPDATE en `telefonos`/`contactos` y envía el mensaje de confirmación, luego retorna sin llamar a `procesarAccion()`. Este caso se maneja al inicio de `handle()`.

Métodos internos extraídos de `post.php`:
- `procesarAccion()`
- `consultarReclamo()`
- `registrarContacto()`
- `buscarMenuClave()`
- `getSaludo()`

**Cambio clave:** donde antes se hacía `echo sendChat(...)`, ahora se llama a `$this->client->sendText(...)`. El retorno de strings JSON + `<ms>` desaparece completamente.

**Flows multi-destinatario:** `procesarAccion()` envía mensajes a destinatarios distintos del usuario (vendedores, supervisores de área). El `WhatsAppClient` recibe siempre el `$to` explícito — no asume que el destinatario es el usuario entrante. Esto afecta:
- Notificación de nuevo reclamo al supervisor del área → `$client->sendText($telResponsable, $resultado)`
- Notificación de nueva consulta al área → `$client->sendText($telResponsable, $resultado)`
- `sendWap()` → notificación de pedido confirmado al vendedor → `$client->sendText($vendedorTel, $pedidos)`
- `sendContacto()` → alerta urgente al vendedor → `$client->sendText($vendedorTel, $alerta)`

Estos métodos son llamados internamente por `procesarAccion()` a través de `$this->client`.

**Logging:** `saveLog()` se conserva para logging del payload entrante, adaptado al nuevo formato Meta (reemplaza el `_wabs_msg` anterior). Pendiente: agregar rotación de logs o truncado por tamaño para evitar crecimiento ilimitado en producción.

---

### 3. `config/WhatsAppClient.php` — Cliente HTTP (nuevo)

Guzzle hacia Meta Graph API. Recibe credenciales en el constructor.

```php
class WhatsAppClient {
    private string $phoneNumberId;
    private string $accessToken;
    private Client $http;  // Guzzle

    public function __construct(string $phoneNumberId, string $accessToken) { ... }

    public function sendText(string $to, string $text): void { ... }
    public function sendLink(string $to, string $url, string $title): void { ... }
    public function sendContact(string $to, array $vendorData): void { ... }
}
```

**Endpoint de envío:** `POST https://graph.facebook.com/v20.0/{phone_number_id}/messages`

> La versión de la Graph API (`v20.0`) se define como constante `WA_API_VERSION` en `global.php` para facilitar actualizaciones futuras.

**Tipos de mensaje:**
| Función | Implementación Cloud API |
|---|---|
| `sendText()` | `type: "text"` con `body` |
| `sendLink()` | `type: "text"` con URL en el body (WA auto-previewea links) |
| `sendContact()` | `type: "contacts"` |

> **Nota sobre links con imagen:** `sendLinkPromos()` enviaba thumbnail base64 via el gateway. Sin templates aprobados por Meta, esto no tiene equivalente directo. Se degrada a `sendText()` con URL — WhatsApp renderiza el preview automáticamente.

**Manejo de errores:** todas las llamadas Guzzle se envuelven en try/catch. Los errores se loggean pero no se propagan — `webhook.php` siempre retorna HTTP 200 a Meta independientemente del resultado de envío:

```php
try {
    $this->http->post($url, ['json' => $payload, 'headers' => $headers]);
} catch (\Exception $e) {
    error_log('[WhatsAppClient] Error al enviar a ' . $to . ': ' . $e->getMessage());
    // No re-throw — el webhook.php debe siempre responder 200 a Meta
}
```

---

### 4. Credenciales por tenant

Se agregan cinco constantes a `config/global.php` (y sus templates `global.docker.dev` / `global.docker.prod`):

```php
define('WA_PHONE_NUMBER_ID', 'xxxxxxxxxx');       // ID del número en Meta
define('WA_ACCESS_TOKEN',    'EAAxxxx...');        // Token permanente (System User)
define('WA_VERIFY_TOKEN',    'mi_token_secreto'); // Elegido por vos para verificar el webhook
define('WA_APP_SECRET',      'xxxxxx');            // App Secret del panel de Meta (para HMAC)
define('WA_API_VERSION',     'v20.0');             // Versión de la Graph API
```

Cada deployment (Campostrini, Faustina, Termoplastica) tiene su propio `global.php` con sus propias credenciales.

> **Token permanente:** en Meta Business Manager, el System User debe tener asignada la WABA con permisos "Full control" antes de poder generar un token permanente. El token temporal del Developer Portal expira cada 24 horas y no es válido para producción.

---

### 5. Mitigación de SQL Injection

**Problema:** la clase `Connection` actual no expone una instancia de conexión compartida — abre y cierra conexiones por query. No existe `Connection::getConnection()`.

**Solución:** usar `$conexion` del archivo `config/Conexion.php`, que crea y mantiene una conexión `mysqli` global disponible como variable global. El saneamiento se aplica en `webhook.php` antes de pasar los valores al `BotEngine`:

```php
global $conexion;  // instancia mysqli de Conexion.php
$user     = preg_replace('/\D/', '', $rawUser);  // teléfono: solo dígitos
$pushname = $conexion->real_escape_string(substr($rawPushname, 0, 100));
$body     = $conexion->real_escape_string(substr($rawBody, 0, 1000));
```

**Queries internas de BotEngine:** los métodos extraídos de `post.php` (`procesarAccion()`, `sendContacto()`, `sendWap()`, etc.) seguirán usando `Connection::runQuery()` internamente, igual que hoy. Esto es coherente con el alcance declarado: no se reescribe la lógica de negocio. La inconsistencia entre `$conexion` (para escape en el entry point) y `Connection::runQuery()` (para queries internas) es aceptada explícitamente.

**Riesgo residual conocido:** dentro de `procesarAccion()`, algunos valores son transformados antes de usarse en SQL (`json_decode($row['anterior'])`, `explode('_', $contactoMensaje)`, etc.). Esas variables intermedias (`$_area`, `$_motivo`, `$anterior`) no son re-escapadas. Este riesgo es aceptado explícitamente como fuera del alcance de esta migración. La solución completa requeriría migrar a prepared statements query por query, lo cual se deja para una etapa futura.

---

### 6. Dependencias — Composer + Guzzle

**`composer.json` (nuevo en raíz del proyecto):**
```json
{
  "require": {
    "guzzlehttp/guzzle": "^7.0"
  }
}
```

**Conflicto con volumen Docker:** el `docker-compose.yml` actual monta todo el directorio del proyecto como volumen (`.:/var/www/atiende`), lo que sobrescribe el `vendor/` construido dentro de la imagen. La solución: ejecutar `composer install` antes de levantar los containers, de modo que `vendor/` exista en el host y el volumen lo incluya. No se modifica el Dockerfile.

Agregar `vendor/` a `.dockerignore` para que no se copie innecesariamente en la imagen:
```
# .dockerignore
vendor/
```

**Desarrollo (startup.sh):** agregar antes de `docker compose up`:
```bash
composer install --no-dev --optimize-autoloader
```

**Producción (startup-prod-*.sh):** los servidores de producción pueden no tener Composer instalado. En ese caso usar la imagen oficial de Composer sin necesidad de instalarlo en el host:
```bash
docker run --rm -v "$(pwd)":/app composer:latest install --no-dev --optimize-autoloader
```
Agregar esta línea al inicio de cada script `startup-prod-campostrini.sh`, `startup-prod-faustina.sh` y `startup-prod-termoplastica.sh`, antes de `docker compose up`.

**`ws/webhook.php` y `config/WhatsAppClient.php`:**
```php
require_once __ROOT__ . '/vendor/autoload.php';
```

---

## Archivos eliminados

- `ws/npost.php` — implementaba una variante del gateway distinta que nunca llegó a producción. El config de Nginx del repositorio (`_docker/nginx/default.conf`) no referencia este archivo. Sin embargo, los deployments de producción (Campostrini, Faustina, Termoplastica) tienen sus propios configs de Nginx que **viven fuera de este repositorio** (en el servidor de producción, típicamente en `/etc/nginx/sites-available/` o montados como volumen). Antes de eliminar `npost.php`, verificar esos archivos en cada servidor de producción.

## Archivos deprecados (no eliminar hasta validar en producción)

- `ws/post.php` — queda como referencia hasta que `webhook.php` esté validado en producción

---

## Flujo de desarrollo con ngrok

Para pruebas locales sin servidor público:

```bash
# 1. Levantar el stack Docker local
./startup.sh

# 2. Exponer el puerto 80 del Nginx local
ngrok http 80

# 3. Copiar la URL HTTPS generada (ej: https://abc123.ngrok-free.app)
# 4. En Meta Developer Portal → Configuración de Webhook:
#    URL: https://abc123.ngrok-free.app/ws/webhook.php
#    Token de verificación: el valor de WA_VERIFY_TOKEN en global.php
# 5. Suscribir al evento "messages"

# IMPORTANTE: la URL cambia cada vez que se reinicia ngrok (plan gratuito).
# Cada cambio de URL requiere re-configurar y re-verificar el webhook en Meta.
# El plan pago de ngrok ofrece dominios estáticos que eliminan este problema
# para desarrollo extendido.
```

---

## Alta en Meta Business — Pasos

1. **Meta Business Manager:** crear cuenta en `business.facebook.com`
2. **Meta Developer Portal:** crear App en `developers.facebook.com` → tipo "Business" → agregar producto "WhatsApp"
3. **WhatsApp Business Account (WABA):** se crea dentro de la app; usar número de prueba de Meta inicialmente para las primeras pruebas
4. **Configurar webhook:** URL de ngrok + `WA_VERIFY_TOKEN` elegido → suscribir al evento `messages`
5. **Obtener `WA_APP_SECRET`:** en el panel de la app → Configuración básica → App Secret
6. **Obtener credenciales permanentes:**
   - En Meta Business Manager → Configuración del negocio → Usuarios del sistema → crear System User
   - Asignar la WABA al System User con permiso "Full control" (paso crítico — sin esto no se puede generar el token permanente)
   - Generar token → asignar la app → seleccionar permisos `whatsapp_business_messaging` y `whatsapp_business_management`
   - Copiar `phone_number_id` desde el dashboard de la app (sección WhatsApp → API Setup)
7. **Para producción:** reemplazar número de prueba por número real y verificar la empresa con Meta

---

## Lo que NO cambia

- Tablas de base de datos (`contactos`, `reclamos`, `consultas`, `menuitem`, `clientes`, `pedidos`, etc.)
- Lógica de negocio completa (menús, reclamos, pedidos, consultas, registro de clientes)
- Consulta a `WEB_MASTER` para obtener el menú JSON del tenant
- Panel admin (`axadmin/`), vistas (`vistas/`), modelos existentes (`modelos/`)
- WebSocket para chat en tiempo real (`__WS__`)
- Stack Docker (PHP-FPM + Nginx + MariaDB)

---

## Deuda técnica conocida (fuera de alcance)

- Prepared statements para todas las queries de `BotEngine` (riesgo residual de SQL injection en valores transformados)
- Rotación de logs en `saveLog()`
- `strftime()` deprecated en PHP 8.1+ — dos ocurrencias en `post.php` líneas 782 y 1034 (`strftime("%Y-%m-%d %H:%M:%S", time())`) que al extraerse quedarán en `BotEngine::procesarAccion()`; reemplazar por `date("Y-m-d H:i:s")`
- Upgrade de PHP 7.3 a versión soportada
