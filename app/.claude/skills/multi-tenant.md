---
name: multi-tenant
description: Use when adding any feature that depends on tenant context, DB routing, credentials, URLs or tenant mode. Covers wb_* schema isolation, pedidos-platform integration and legacy coexistence.
---

# Arquitectura Multi-Tenant — Atiende

## Cómo funciona el routing de tenant

| Flujo | Mecanismo | DB activa |
|---|---|---|
| Admin app (vistas/, ajax/) | `$_SESSION['tenant_db']` seteado en login | `wb_{slug}` o `atiende` |
| Pedidos (pedidos/index.php) | `Connection::setDatabase('wb_'.$slug)` via `?t=` o header `HTTP_X_TENANT` | `wb_{slug}` |
| Webhook (ws/webhook.php) | Extrae slug del número entrante → conecta a `wb_{slug}` | `wb_{slug}` |

## Conexión

- `config/Conexion.php`: setea `$conexion` a `$_SESSION['tenant_db']` automáticamente al ser incluido
- `config/Connection.php`: clase estática; usar `Connection::setDatabase('wb_slug')` antes de las queries en flujo sin sesión
- Obtener DB activa en Connection: `Connection::getDatabase()`

## Aislamiento — verificar siempre

```php
// ❌ VULNERABLE: cualquier tenant puede pedir datos de otro
$pedido = ejecutarConsulta("SELECT * FROM pedidos WHERE id = ".$_POST['id']);

// ✅ Verificar que pertenece al tenant activo
$stmt = $conexion->prepare("SELECT * FROM pedidos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_POST['id']);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
// $conexion ya apunta al tenant correcto — la query solo ve datos de ese tenant
```

## Credenciales WA per-tenant

Cargadas en `config/Conexion.php` desde `pedidos_platform.tenants`:
- `WA_PHONE_NUMBER_ID` — constante disponible después del include
- `WA_ACCESS_TOKEN` — descifrado con AES-256-GCM
- `WA_APP_SECRET` — descifrado con AES-256-GCM
- `$GLOBALS['_tenant_mode']` — `mix|b2b|b2c`

Si el tenant no tiene credenciales configuradas, las constantes no se definen.
`WhatsAppClient` devuelve `['ok'=>false,'error'=>'Credenciales WA no configuradas...']`.

## tenant_mode — determina lógica de canal

```php
$cfg = getWebMasterConfig();  // definida en config/global.php
$isB2B = $cfg['data']['b2b']; // true si mode es 'b2b' o 'mix'
$isB2C = $cfg['data']['b2c']; // true si mode es 'b2c' o 'mix'
```

Determina: qué join usar en clientes (`.codigo` vs `.id`), qué formulario mostrar, etc.
Configurado en `pedidos_platform.tenants.tenant_mode` por el superadmin.

## URLs del tenant

```php
// Genera: http://{slug}.__TENANT_DOMAIN__
echo tenantUrl();                    // usa DB_NAME para derivar slug
echo tenantUrl('miempresa');         // slug explícito
echo tenantUrl($tenantSlug);         // variable de pedidos/index.php
```

Definida en `config/global.php`. Usar en todos los links generados dinámicamente.

## Provisioning — pedidos-platform

- Nuevo tenant = nueva DB `wb_{slug}` creada por `pedidos-platform`
- Schema base: `_docker/mariadb/atiende.sql` (sin INSERTs de negocio)
- Credenciales WA las conecta Diego manualmente desde el superadmin
- **No modificar el app Atiende** para lógica de plataforma — eso va en `pedidos-platform`
- La única integración permitida: leer `pedidos_platform.tenants` en Conexion.php

## Coexistencia legacy

- DB `atiende` sigue existiendo para tenants viejos que no migraron
- `DB_NAME = 'atiende'` en `config/database.php` es el default
- Nunca romper el flujo legacy — los bloques `if ($_SESSION['x'] == 10)` (Bootstrap 3) no se tocan
- Scripts de startup por empresa (`startup-prod-campostrini.sh`, etc.) son legacy — no modificar
