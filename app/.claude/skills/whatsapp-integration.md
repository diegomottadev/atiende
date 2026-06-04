---
name: whatsapp-integration
description: Use when sending WhatsApp messages, handling the Meta webhook, working with phone numbers or WA credentials. Covers normalización de números, location pins, firma HMAC y credenciales per-tenant.
---

# Integración WhatsApp Cloud API — Atiende

## Cliente — siempre usar WhatsAppClient

```php
require_once '../config/WhatsAppClient.php';
$wa = new WhatsAppClient(WA_PHONE_NUMBER_ID, WA_ACCESS_TOKEN, WA_API_VERSION);
$result = $wa->sendText($telefono, $mensaje);
if (!$result['ok']) {
    error_log('[WA] Error: ' . $result['error']);
}
```

Métodos retornan siempre `['ok' => bool]` o `['ok' => false, 'error' => string]`.
Nunca tirar excepción — `send()` usa `http_errors: false`.

## Credenciales — per-tenant, no hardcodeadas

Las constantes `WA_PHONE_NUMBER_ID`, `WA_ACCESS_TOKEN`, `WA_APP_SECRET` se cargan automáticamente en:
- **Flujo admin** (`Conexion.php`): desde `pedidos_platform.tenants` al iniciar sesión
- **Flujo pedidos** (`pedidos/send_wa.php`): PDO inline a `pedidos_platform`

Si faltan credenciales: `['ok' => false, 'error' => 'Credenciales WhatsApp no configuradas para este tenant']`.

## Normalización de números

```php
// normalizePhone() en WhatsAppClient:
// Argentina wa_id: 549XXXXXXXXXX → 54XXXXXXXXXX (quitar el 9 mobile)
// Otros países: usar as-is
```

**Regla:** Meta entrega números AR con `9` insertado después del código de país.
La Cloud API rechaza ese formato. **Nunca agregar `15`** — era el prefijo PSTN, está obsoleto.

## sendText

```php
$wa->sendText($to, $texto);  // preview_url: true automático (muestra preview de links)
```

## sendLocation — cuidado con name/address

```php
// ✅ Pin nativo de WhatsApp (dropped pin)
$wa->sendLocation($to, $lat, $lng);

// ❌ Convierte en búsqueda de Google Maps — no usar
$wa->sendLocation($to, $lat, $lng, '', '');
$wa->sendLocation($to, $lat, $lng, 'Nombre', 'Dirección');
```

**Regla:** Cualquier valor en `name` o `address` (incluso string vacío) hace que WhatsApp
renderice un pin de búsqueda Maps en vez del pin nativo. Omitir siempre.

## Webhook — verificar firma antes de todo

```php
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WA_APP_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}
// Responder 200 inmediatamente para evitar timeout de Meta
http_response_code(200);
echo 'OK';
// Procesar el payload después del 200
```

`hash_equals()` es obligatorio — evita timing attacks.

## Verify token (GET del webhook)

```php
if ($_GET['hub_mode'] === 'subscribe' &&
    hash_equals(WA_VERIFY_TOKEN, $_GET['hub_verify_token'])) {
    echo $_GET['hub_challenge'];
    exit;
}
http_response_code(403);
```

## URLs en mensajes WA

Construir con `tenantUrl($slug)` → `http://{slug}.__TENANT_DOMAIN__/...`
Nunca hardcodear dominios. Ver `modelos/BotEngine.php` como referencia.

## Templates vs mensajes libres

- `sendText` solo funciona dentro de la ventana de 24hs desde el último mensaje del cliente
- Para mensajes fuera de ventana usar Message Templates aprobados en Meta
- El bot en `ws/webhook.php` y `modelos/BotEngine.php` maneja el flujo conversacional
