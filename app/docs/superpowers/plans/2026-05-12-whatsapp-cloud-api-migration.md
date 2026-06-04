# WhatsApp Cloud API Migration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar el gateway Node.js de scraping por la WhatsApp Business Cloud API oficial de Meta, extrayendo la lógica del bot a `BotEngine.php` y creando un cliente Guzzle en `WhatsAppClient.php`.

**Architecture:** Un nuevo `ws/webhook.php` recibe los mensajes de Meta (verificación HMAC + parsing del payload), los sanea y los delega a `BotEngine`. El `BotEngine` contiene la lógica extraída de `post.php` y envía mensajes a través de `WhatsAppClient`, que llama directamente a la Graph API de Meta con Guzzle.

**Tech Stack:** PHP 7.3, Guzzle 7, Meta WhatsApp Business Cloud API v20.0, Docker, ngrok (dev)

**Spec:** `docs/superpowers/specs/2026-05-12-whatsapp-cloud-api-migration-design.md`

---

## File Map

| Acción | Archivo | Responsabilidad |
|---|---|---|
| Crear | `composer.json` | Dependencia Guzzle |
| Crear | `.dockerignore` | Excluir vendor/ de la imagen |
| Crear | `config/WhatsAppClient.php` | Cliente HTTP hacia Meta Graph API |
| Crear | `modelos/BotEngine.php` | Lógica del bot extraída de post.php |
| Crear | `ws/webhook.php` | Punto de entrada Meta Cloud API |
| Modificar | `config/global.docker.dev` | Constantes WA para dev |
| Modificar | `config/global.docker.prod` | Constantes WA para producción |
| Modificar | `startup.sh` | Agregar `composer install` antes de Docker |
| Modificar | `startup-prod-campostrini.sh` | Idem producción |
| Modificar | `startup-prod-faustina.sh` | Idem producción |
| Modificar | `startup-prod-termoplastica.sh` | Idem producción |
| Eliminar | `ws/npost.php` | Nunca usado en producción |
| Deprecar | `ws/post.php` | Mantener como referencia hasta validar en producción |

---

## Task 1: Composer + .dockerignore

**Files:**
- Create: `composer.json`
- Create: `.dockerignore`

- [ ] **Step 1: Crear composer.json**

```json
{
    "require": {
        "guzzlehttp/guzzle": "^7.0"
    },
    "config": {
        "optimize-autoloader": true
    }
}
```

- [ ] **Step 2: Crear .dockerignore**

```
vendor/
docs/
.git/
*.md
```

- [ ] **Step 3: Instalar dependencias**

```bash
composer install --no-dev --optimize-autoloader
```

Esperado: directorio `vendor/` creado en la raíz con Guzzle y sus dependencias.

- [ ] **Step 4: Verificar que Guzzle está disponible**

```bash
php -r "require 'vendor/autoload.php'; echo \GuzzleHttp\Client::class . PHP_EOL;"
```

Esperado: `GuzzleHttp\Client`

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock .dockerignore
git commit -m "chore: add Composer with Guzzle dependency"
```

---

## Task 2: Credenciales WhatsApp en config templates

**Files:**
- Modify: `config/global.docker.dev`
- Modify: `config/global.docker.prod`

- [ ] **Step 1: Agregar constantes WA en global.docker.dev**

Abrir `config/global.docker.dev` y agregar antes del cierre `?>`:

```php
// WhatsApp Business Cloud API
define('WA_PHONE_NUMBER_ID', 'COMPLETAR');   // ID del número en Meta Developer Portal
define('WA_ACCESS_TOKEN',    'COMPLETAR');   // Token permanente del System User
define('WA_VERIFY_TOKEN',    'dev_verify_token_local'); // Elegido por vos
define('WA_APP_SECRET',      'COMPLETAR');   // App Secret del panel de Meta
define('WA_API_VERSION',     'v20.0');
```

- [ ] **Step 2: Agregar constantes WA en global.docker.prod**

```bash
# Ver si existe el archivo template de prod
ls config/global.docker.prod
```

Agregar las mismas cinco constantes con valores `'COMPLETAR'` como placeholders.

- [ ] **Step 3: Verificar sintaxis de ambos archivos**

```bash
php -l config/global.docker.dev
php -l config/global.docker.prod
```

Esperado: `No syntax errors detected` en ambos.

- [ ] **Step 4: Commit**

```bash
git add config/global.docker.dev config/global.docker.prod
git commit -m "chore: add WhatsApp Cloud API credential constants to config templates"
```

---

## Task 3: WhatsAppClient.php

**Files:**
- Create: `config/WhatsAppClient.php`

- [ ] **Step 1: Crear el archivo**

```php
<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WhatsAppClient
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $apiVersion;
    private Client $http;

    public function __construct(string $phoneNumberId, string $accessToken, string $apiVersion = 'v20.0')
    {
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken   = $accessToken;
        $this->apiVersion    = $apiVersion;
        $this->http          = new Client(['timeout' => 10.0]);
    }

    public function sendText(string $to, string $text): void
    {
        $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => true,
                'body'        => $text,
            ],
        ]);
    }

    public function sendLink(string $to, string $url, string $title): void
    {
        // Cloud API auto-previewea URLs en mensajes de texto
        $this->sendText($to, $title . "\n" . $url);
    }

    // Nota: sendContacto() en BotEngine envía un MENSAJE DE TEXTO al vendedor,
    // no una tarjeta de contacto de WA. Usa sendText() directamente.
    // No se necesita un método sendContact() de tipo vCard.

    private function send(array $payload): void
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
        try {
            $this->http->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            error_log('[WhatsAppClient] Error al enviar a ' . ($payload['to'] ?? '?') . ': ' . $e->getMessage());
            // No re-throw — webhook.php siempre debe responder 200 a Meta
        }
    }
}
```

- [ ] **Step 2: Verificar sintaxis**

```bash
php -l config/WhatsAppClient.php
```

Esperado: `No syntax errors detected`

- [ ] **Step 3: Smoke test del cliente (requiere credenciales reales)**

Solo ejecutar si ya tenés las credenciales de Meta configuradas en global.php:

```bash
php -r "
define('__ROOT__', '.');
require 'config/global.php';
require 'config/WhatsAppClient.php';
\$client = new WhatsAppClient(WA_PHONE_NUMBER_ID, WA_ACCESS_TOKEN, WA_API_VERSION);
\$client->sendText('549TU_NUMERO', 'Test desde WhatsAppClient');
echo 'Enviado (revisar WhatsApp)' . PHP_EOL;
"
```

Si no tenés credenciales aún, saltear este paso y volver cuando estén configuradas.

- [ ] **Step 4: Commit**

```bash
git add config/WhatsAppClient.php
git commit -m "feat: add WhatsAppClient with Guzzle for Meta Cloud API"
```

---

## Task 4: BotEngine.php

**Files:**
- Create: `modelos/BotEngine.php`
- Reference: `ws/post.php` (fuente de la lógica a extraer)

Esta es la tarea más grande. Se extrae la lógica de `post.php` línea a línea sin modificar el comportamiento.

- [ ] **Step 1: Crear la estructura base de la clase**

```php
<?php
define('__ROOT__', dirname(__DIR__));
require_once __ROOT__ . '/config/Connection.php';
require_once __ROOT__ . '/config/Conexion.php';
require_once __ROOT__ . '/config/WhatsAppClient.php';

class BotEngine
{
    private array $menuJson;
    private array $tenantConfig;
    private WhatsAppClient $client;
    private string $empresa;

    public function __construct(array $menuJson, array $tenantConfig, WhatsAppClient $client)
    {
        $this->menuJson     = $menuJson;
        $this->tenantConfig = $tenantConfig;
        $this->client       = $client;
        $this->empresa      = $tenantConfig['data']['identificador'] ?? '';
    }

    public function handle(
        string $user,
        string $pushname,
        string $body,
        string $type,
        ?array $location = null
    ): void {
        // Caso especial: baja del bot
        if (strcasecmp($body, 'bajacp') === 0) {
            Connection::runQuery("DELETE FROM `telefonos` WHERE `telefono` like '" . $user . "'");
            Connection::runQuery("UPDATE `contactos` SET `anterior`='', menu='0', esperaRespuesta=0 WHERE id like '" . $user . "'");
            $this->client->sendText($user, 'La baja se realizó correctamente. Gracias!');
            return;
        }

        // Recuperar estado de sesión del usuario
        $menuID        = '0';
        $esperaRespuesta = '0';
        $ctrlLocationSendByChat = null;
        $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior FROM contactos where telefono LIKE '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row             = mysqli_fetch_assoc($request);
            $menuID          = $row['menu'];
            $esperaRespuesta = $row['esperaRespuesta'];
            if ($menuID == 802) {
                $ctrlLocationSendByChat = $row['anterior'];
            }
        }

        // Código de cliente vinculado al teléfono
        $codigoCliente = '';
        $request = Connection::runQuery("SELECT clienteId FROM `telefonos` WHERE `telefono` = '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row           = mysqli_fetch_assoc($request);
            $codigoCliente = $row['clienteId'];
        }

        // Palabras clave
        if (strlen($codigoCliente) > 0) {
            $menuclave = $this->buscarMenuClave($this->menuJson, $body);
            if (strlen($menuclave) > 0) {
                $menuID = $menuclave;
            }
        }

        // Inyectar motivos de reclamos y consultas desde DB
        $rows    = [];
        $request = Connection::runQuery("SELECT `opcionId`, `opcion`, `menuId`, IF(guardar, 'true', 'false') guardar, `area` FROM `menuitem`");
        if ($request) {
            while ($row = mysqli_fetch_assoc($request)) {
                $rows[] = $row;
            }
            $rows[] = ['opcionId' => 'S', 'opcion' => 'Salir', 'menuId' => '2.2', 'guardar' => false, 'area' => ''];
            $this->menuJson[1]['menuItem'] = $rows;
        }

        $rows    = [];
        $request = Connection::runQuery("SELECT `opcionId`, `opcion`, `menuId`, IF(guardar, 'true', 'false') guardar, `area` FROM `motivo_consultas`");
        if ($request) {
            while ($row = mysqli_fetch_assoc($request)) {
                $rows[] = $row;
            }
            $rows[] = ['opcionId' => 'S', 'opcion' => 'Salir', 'menuId' => '2.2', 'guardar' => false, 'area' => ''];
        }
        $this->menuJson[31]['menuItem'] = $rows;

        // Despachar por tipo de mensaje
        if ($type === 'text') {
            $this->procesarAccion($menuID, $esperaRespuesta, $body, $pushname, $user, $codigoCliente);
        } elseif ($type === 'location' && $menuID == 802 && $ctrlLocationSendByChat !== null) {
            $locationBody = json_encode([$location['latitude'], $location['longitude']]);
            $this->procesarAccion($menuID, $esperaRespuesta, $locationBody, $pushname, $user, $codigoCliente);
        } else {
            $this->client->sendText($user, 'No está admitido mensaje de tipo ' . $type);
        }
    }
```

- [ ] **Step 2: Reemplazar die() por return antes de copiar**

`procesarAccion()` tiene 6 llamadas a `die()` que terminarían el script antes de que Meta reciba el HTTP 200, causando re-entregas. Reemplazarlas en `post.php` **antes** de copiar, o hacerlo durante la copia en `BotEngine.php`.

Líneas con `die()` en `post.php`: 811, 817, 895, 914, 919, 1061.

En cada caso, `die()` aparece tras enviar un mensaje y no hay más lógica posterior en esa rama — reemplazarlo por `return`:

```php
// Antes:
echo sendChat($user, "mensaje");
die();

// Después (en BotEngine):
$this->client->sendText($user, "mensaje");
return;
```

- [ ] **Step 3: Copiar procesarAccion() de post.php y adaptar**

Copiar la función `procesarAccion()` de `ws/post.php` (líneas 597–1094) como método privado de la clase. Cambios a realizar:

**Buscar TODAS las llamadas a funciones de envío (no solo las que tienen `echo`):**
```bash
grep -n "sendChat\|sendLink\|sendLinkPromos" ws/post.php | grep -v "^\s*//"
```

**Reemplazar cada `echo sendChat($x, $y)` → `$this->client->sendText($x, $y)`**

**Reemplazar cada `echo sendLink($url, $user, $title)` → `$this->client->sendLink($user, $url, $title)`**

> Atención: el orden de argumentos CAMBIA — en `post.php` el orden es `($url, $telefono, $title)`, en `WhatsAppClient::sendLink` es `($to, $url, $title)`. Verificar cada reemplazo individualmente.

**Reemplazar cada `echo sendLinkPromos($url, $user, $title, $thumb, $desc)` → `$this->client->sendText($user, $desc . "\n" . $url)`**

**Cambiar la firma del método:**
```php
// Antes (función global):
function procesarAccion($menuJson, $menu, $esperaRespuesta, $mensaje, $pushname, $user, $codigoCliente, $responseWebMaster)

// Después (método de clase):
private function procesarAccion(string $menu, string $esperaRespuesta, string $mensaje, string $pushname, string $user, string $codigoCliente): void
```

Las referencias a `$menuJson` pasan a ser `$this->menuJson`, `$responseWebMaster` pasa a ser `$this->tenantConfig`, y `$empresa` pasa a ser `$this->empresa`.

Las llamadas recursivas internas (`procesarAccion($menuJson, ...)`) pasan a ser `$this->procesarAccion(...)` sin los primeros dos argumentos.

- [ ] **Step 4: Copiar métodos auxiliares de post.php como métodos privados**

Copiar y convertir a métodos privados:
- `consultarReclamo($reclamoId, $user)` → desde post.php línea ~1098
- `registrarContacto($pushname, $user, $menu, $espera_respuesta)` → desde post.php línea ~1128
- `getSaludo()` → desde post.php línea ~1133
- `buscarMenuClave($menuJson, $str)` → desde post.php línea ~1149 (el parámetro `$menuJson` pasa a ser `$this->menuJson`)
- `sendWap($clienteID, $pedidoid, $tel)` → desde post.php línea ~1249 (reemplazar `echo sendChat("549".$telefono, ...)` por `$this->client->sendText("549".$telefono, ...)`)
- `sendContacto($clienteID, $tel)` → desde post.php línea ~1209 (reemplazar `echo sendChat($tel, ...)` por `$this->client->sendText($tel, ...)`)
- `saveLog($data, $filename)` → desde post.php — mantener igual, solo escribe en disco

> **sendGCM()** (desde post.php línea 1290): función definida pero nunca llamada en ningún flujo del bot. **No extraer** — es código muerto. Se descarta formalmente.

> **Formato de teléfono en sendWap:** el número del vendedor se construye como `"549" . $telefono` donde `$telefono` viene de la tabla `vendedores`. Verificar que los números almacenados en esa tabla son locales (sin prefijo 549) y que el prefijo `549` es correcto para los tenants. Si un tenant tiene números con otro prefijo, ajustar la lógica.

Cerrar la clase con `}` al final.

- [ ] **Step 5: Verificar sintaxis**

```bash
php -l modelos/BotEngine.php
```

Esperado: `No syntax errors detected`

Si hay errores de sintaxis, corregirlos uno a uno ejecutando `php -l` después de cada corrección.

- [ ] **Step 6: Verificar que no quedaron funciones de envío sin migrar**

```bash
grep -n "sendChat\|sendLink\|sendLinkPromos" modelos/BotEngine.php | grep -v "this->"
```

Esperado: sin resultados.

- [ ] **Step 7: Verificar que no quedaron die() sin reemplazar**

```bash
grep -n "die()" modelos/BotEngine.php
```

Esperado: sin resultados.

- [ ] **Step 8: Verificar orden de argumentos en sendLink**

```bash
grep -n "sendLink" modelos/BotEngine.php
```

Para cada línea encontrada, confirmar que el primer argumento es el teléfono (`$user`) y el segundo es la URL. Si alguna llamada tiene la URL primero, invertir los argumentos.

- [ ] **Step 9: Commit**

```bash
git add modelos/BotEngine.php
git commit -m "feat: extract bot logic from post.php into BotEngine class"
```

---

## Task 5: webhook.php

**Files:**
- Create: `ws/webhook.php`

- [ ] **Step 1: Crear el archivo**

```php
<?php
define('__ROOT__', dirname(__DIR__));
require __ROOT__ . '/config/global.php';
require __ROOT__ . '/config/Conexion.php';
require __ROOT__ . '/config/WhatsAppClient.php';
require __ROOT__ . '/modelos/BotEngine.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ---------- GET: verificación del webhook ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']       ?? $_GET['hub.mode']         ?? '';
    $challenge = $_GET['hub_challenge']  ?? $_GET['hub.challenge']    ?? '';
    $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';

    if ($mode === 'subscribe' && hash_equals(WA_VERIFY_TOKEN, $token)) {
        http_response_code(200);
        echo $challenge;
    } else {
        http_response_code(403);
    }
    exit;
}

// ---------- POST: mensaje entrante ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$rawBody = file_get_contents('php://input');

// Verificación HMAC — rechazar requests no firmados por Meta
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $rawBody, WA_APP_SECRET);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}

// Responder 200 inmediatamente y flushear la respuesta a Nginx.
// Esto evita que Meta marque el webhook como fallido si el procesamiento
// posterior (cURL a WEB_MASTER + BotEngine) tarda más de ~20 segundos.
http_response_code(200);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$payload = json_decode($rawBody, true);
if (!$payload || ($payload['object'] ?? '') !== 'whatsapp_business_account') {
    exit;
}

$value = $payload['entry'][0]['changes'][0]['value'] ?? null;
if (!$value) {
    exit;
}

// Ignorar eventos de estado de entrega (sin clave 'messages')
if (!isset($value['messages'])) {
    exit;
}

$message  = $value['messages'][0];
$contact  = $value['contacts'][0]  ?? null;
$type     = $message['type']       ?? 'unknown';
$rawUser  = $message['from']       ?? '';
$rawName  = $contact['profile']['name'] ?? ($message['from'] ?? 'usuario');

// Saneamiento de entradas
global $conexion;
$user     = preg_replace('/\D/', '', $rawUser);
$pushname = $conexion->real_escape_string(substr($rawName, 0, 100));

$body     = '';
$location = null;

if ($type === 'text') {
    $rawBody2 = $message['text']['body'] ?? '';
    $body     = $conexion->real_escape_string(substr($rawBody2, 0, 1000));
} elseif ($type === 'location') {
    $location = [
        'latitude'  => $message['location']['latitude']  ?? 0,
        'longitude' => $message['location']['longitude'] ?? 0,
    ];
    $body = '';
} else {
    // Tipo no soportado: no procesar (ya respondimos 200)
    exit;
}

// Log del payload para debugging
$logData = json_encode(['user' => $user, 'type' => $type, 'body' => $body], JSON_UNESCAPED_UNICODE);
$logFile = fopen(__ROOT__ . '/ws/json_.txt', 'w');
if ($logFile) {
    fwrite($logFile, $logData);
    fclose($logFile);
}

// Obtener configuración del tenant desde WEB_MASTER
$url = WEB_MASTER . '/api/configurations/getConfigurationByName?name=' . urlencode(DB_NAME);
$ch  = curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$responseWebMaster = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!$responseWebMaster || !isset($responseWebMaster['data']['empresa']['json'])) {
    error_log('[webhook] No se pudo obtener configuración del tenant');
    exit;
}

$menuJson = json_decode($responseWebMaster['data']['empresa']['json'], true)['menu'] ?? [];

// Instanciar cliente y engine
$client = new WhatsAppClient(WA_PHONE_NUMBER_ID, WA_ACCESS_TOKEN, WA_API_VERSION);
$engine = new BotEngine($menuJson, $responseWebMaster, $client);
$engine->handle($user, $pushname, $body, $type, $location);
```

- [ ] **Step 2: Verificar sintaxis**

```bash
php -l ws/webhook.php
```

Esperado: `No syntax errors detected`

- [ ] **Step 3: Probar verificación GET localmente**

Con el stack Docker corriendo:
```bash
curl -s "http://localhost:81/ws/webhook.php?hub.mode=subscribe&hub.challenge=TEST123&hub.verify_token=dev_verify_token_local"
```

Esperado: `TEST123`

Con token incorrecto:
```bash
curl -s -o /dev/null -w "%{http_code}" "http://localhost:81/ws/webhook.php?hub.mode=subscribe&hub.challenge=X&hub.verify_token=WRONG"
```

Esperado: `403`

- [ ] **Step 4: Probar rechazo HMAC**

```bash
curl -s -o /dev/null -w "%{http_code}" -X POST \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=invalida" \
  -d '{"object":"whatsapp_business_account"}' \
  http://localhost:81/ws/webhook.php
```

Esperado: `403`

- [ ] **Step 5: Commit**

```bash
git add ws/webhook.php
git commit -m "feat: add webhook.php as new Meta Cloud API entry point"
```

---

## Task 6: Actualizar startup scripts con composer install

**Files:**
- Modify: `startup.sh`
- Modify: `startup-prod-campostrini.sh`
- Modify: `startup-prod-faustina.sh`
- Modify: `startup-prod-termoplastica.sh`

- [ ] **Step 1: Agregar composer install en startup.sh**

En `startup.sh`, agregar después del paso `[1/7]` (copia de global.php) y antes del paso `[2/7]`:

```bash
# ---- 1b. Instalar dependencias PHP ----
echo "[1b/7] Instalando dependencias Composer..."
command -v composer >/dev/null 2>&1 || { echo "ERROR: composer no encontrado. Instalarlo desde https://getcomposer.org o usar Docker."; exit 1; }
composer install --no-dev --optimize-autoloader
```

- [ ] **Step 2: Agregar composer install en cada script de producción**

En cada `startup-prod-*.sh`, agregar al inicio (antes de `docker compose up`):

```bash
echo "Instalando dependencias PHP via Docker Composer..."
docker run --rm -v "$(pwd)":/app composer:latest install --no-dev --optimize-autoloader
```

- [ ] **Step 3: Verificar que startup.sh tiene sintaxis bash válida**

```bash
bash -n startup.sh
```

Esperado: sin output (sin errores).

- [ ] **Step 4: Commit**

```bash
git add startup.sh startup-prod-campostrini.sh startup-prod-faustina.sh startup-prod-termoplastica.sh
git commit -m "chore: add composer install to startup scripts"
```

---

## Task 7: Eliminar npost.php

**Files:**
- Delete: `ws/npost.php`

- [ ] **Step 1: Verificar que el Nginx del repo no lo referencia**

```bash
grep -r "npost" _docker/
```

Esperado: sin resultados.

- [ ] **Step 2: Verificar en producción (manual)**

En cada servidor de producción, buscar el Nginx config activo:
```bash
grep -r "npost" /etc/nginx/
```
Si no hay resultados, es seguro eliminar.

- [ ] **Step 3: Eliminar el archivo**

```bash
git rm ws/npost.php
```

- [ ] **Step 4: Commit**

```bash
git commit -m "chore: remove unused npost.php"
```

---

## Task 8: Configurar Meta y probar end-to-end con ngrok

Esta tarea es manual/operativa. No produce código.

- [ ] **Step 1: Completar credenciales en config/global.php**

Seguir los pasos del spec (sección "Alta en Meta Business") para obtener:
- `WA_PHONE_NUMBER_ID`
- `WA_ACCESS_TOKEN` (System User token permanente)
- `WA_APP_SECRET`

Editar `config/global.php` (que ya existe en el proyecto después de `startup.sh`) y completar los valores `COMPLETAR`.

- [ ] **Step 2: Levantar el stack**

```bash
./startup.sh
```

Verificar que el stack está corriendo:
```bash
docker compose ps
```

Esperado: tres containers corriendo (app, nginx, mysql).

- [ ] **Step 3: Exponer con ngrok**

```bash
ngrok http 80
```

Copiar la URL HTTPS generada (ej: `https://abc123.ngrok-free.app`).

- [ ] **Step 4: Configurar webhook en Meta Developer Portal**

1. Ir a `developers.facebook.com` → tu App → WhatsApp → Configuración
2. En "Webhook": clic en "Editar"
3. URL: `https://abc123.ngrok-free.app/ws/webhook.php`
4. Token de verificación: el valor de `WA_VERIFY_TOKEN` en tu `global.php`
5. Clic en "Verificar y guardar" — Meta hará un GET al webhook
6. Suscribir al campo `messages`

Verificar en la terminal de ngrok que llegó el GET de Meta con `hub.challenge`.

- [ ] **Step 5: Enviar mensaje de prueba**

Desde el número de WhatsApp configurado como prueba en Meta, enviar un mensaje al número de la empresa.

En el dashboard local de ngrok (`http://localhost:4040`) verificar:
- Llegó un POST de Meta con el mensaje
- La respuesta fue HTTP 200

En la base de datos verificar que el contacto quedó registrado:
```bash
docker exec demo_atiende_mysql mysql -uroot -proot atiende \
  -e "SELECT * FROM contactos ORDER BY fechaHora DESC LIMIT 1;"
```

Esperado: una fila con el número de teléfono del mensaje enviado.

- [ ] **Step 6: Probar el flujo completo de reclamo**

1. Enviar `"A"` (o la opción de reclamos configurada en el menú del tenant)
2. Seguir el flujo hasta crear un reclamo
3. Verificar en DB:
```bash
docker exec demo_atiende_mysql mysql -uroot -proot atiende \
  -e "SELECT * FROM reclamos ORDER BY fecha_ingreso DESC LIMIT 1;"
```

---

## Task 9: Smoke test final y deprecación de post.php

**Files:**
- Modify: `ws/post.php` (agregar comentario de deprecación)

- [ ] **Step 1: Agregar aviso de deprecación en post.php**

Agregar al inicio de `ws/post.php`, después de la apertura `<?php`:

```php
/**
 * @deprecated Reemplazado por ws/webhook.php (Meta Cloud API).
 * Mantener como referencia hasta validar webhook.php en producción.
 * Ver: docs/superpowers/specs/2026-05-12-whatsapp-cloud-api-migration-design.md
 */
```

- [ ] **Step 2: Verificar que webhook.php sigue respondiendo correctamente**

```bash
curl -s "http://localhost:81/ws/webhook.php?hub.mode=subscribe&hub.challenge=FINAL_TEST&hub.verify_token=dev_verify_token_local"
```

Esperado: `FINAL_TEST`

- [ ] **Step 3: Commit final**

```bash
git add ws/post.php
git commit -m "chore: mark post.php as deprecated, replaced by webhook.php"
```

---

## Deuda técnica documentada (no parte de este plan)

Registradas en el spec. A abordar en iteraciones futuras:
- Prepared statements en todas las queries de `BotEngine` (SQL injection residual en valores transformados)
- Rotación de logs en `saveLog()`
- Reemplazar `strftime()` por `date()` en `BotEngine::procesarAccion()` líneas equivalentes a post.php:782 y 1034
- Upgrade de PHP 7.3
