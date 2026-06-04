# Bitácora de Operaciones — WhatsApp Bot (Atiende / Atiende)

> Documento vivo. Cada vez que hagas un cambio operativo, anotalo al final en el [Registro de cambios](#registro-de-cambios).

---

## Resumen de credenciales actuales

> ⚠️ **IMPORTANTE (actualizado 2026-06-02):** Para los tenants `wb_*` (ej. `wb_corp`) las credenciales de WhatsApp **ya NO van en `config/global.php`**. Ahora son **por-tenant y están CIFRADAS** en la tabla `pedidos_platform.tenants` (columnas `whatsapp_*`). `global.php` solo tiene config global (verify token, versión de API, clave de cifrado).

| Credencial | Dónde vive HOY | Descripción |
|---|---|---|
| `WA_ACCESS_TOKEN` | `pedidos_platform.tenants.whatsapp_token_enc` (cifrado AES-256-GCM) | Token de acceso de Meta. **Usar System User token permanente** (no el temporal de 24 h) |
| `WA_APP_SECRET` | `pedidos_platform.tenants.whatsapp_app_secret_enc` (cifrado) | Clave secreta de la app — valida la firma HMAC del webhook |
| `WA_PHONE_NUMBER_ID` | `pedidos_platform.tenants.whatsapp_phone_id` (texto plano) | ID del número de WhatsApp Business del tenant |
| `WA_VERIFY_TOKEN` | `config/global.php` línea 9 | Token de verificación del webhook (fijo: `dev_verify_token_local`) |
| `WA_API_VERSION` | `config/global.php` línea 10 | Versión del Graph API (`v25.0`) |
| `PLATFORM_ENCRYPTION_KEY` | `config/global.php` línea 13 | Clave AES-256-GCM que descifra los tokens. En dev = 64 ceros |

Flujo: `webhook.php` identifica el tenant por `phone_number_id`, busca la fila en `pedidos_platform.tenants`, **descifra** token y app_secret en cada request, y construye `WhatsAppClient` con ellos. `Conexion.php` hace lo mismo para el resto de la app (routing por `$_SESSION['tenant_db']`).

---

## Procedimiento completo para arrancar el bot en desarrollo

Seguir estos pasos **en orden** cada vez que se quiera usar el bot localmente.

---

### Paso 1 — Cargar/renovar el Access Token de Meta (por tenant, cifrado en DB)

Cuando el bot deja de responder, este es el primer lugar a revisar.

**Dónde obtenerlo — usar token PERMANENTE de System User (recomendado):**

1. Ir a [business.facebook.com/settings](https://business.facebook.com/settings)
2. **Usuarios → Usuarios del sistema** → seleccionar (o crear) el system user de la app
3. **Generar token** → elegir la app → permisos `whatsapp_business_messaging` **y** `whatsapp_business_management` → **Caducidad: Nunca**
4. Copiar el token (empieza con `EAA...`, ~200 caracteres)

> El token **temporal** de la pantalla "API Setup" dura solo ~24 h → el bot se cae cada día. Usá siempre el de System User.

**Requisito imprescindible:** ese System User tiene que tener **la WABA (cuenta de WhatsApp Business) del número asignada como activo**. En Business Settings → Usuarios del sistema → **Agregar activos** → Cuentas de WhatsApp → marcar la WABA → **Control total**. Sin esto el token es válido pero no puede tocar el número (error 100/33, ver tabla de diagnóstico).

**Dónde pegarlo — cifrado en `pedidos_platform.tenants` (NO en global.php):**

Reemplazá `corp` por el slug del tenant y pegá tu token. Este comando lo cifra con la clave del entorno (en dev = 64 ceros) y lo guarda:

```powershell
docker exec demo_atiende_app php -r '
$key=hex2bin(str_repeat("0",64));   # = PLATFORM_ENCRYPTION_KEY (dev). En prod usar el valor real.
$tok="EAA...PEGÁ_TU_TOKEN...";
$iv=random_bytes(12);$tag="";
$ct=openssl_encrypt($tok,"aes-256-gcm",$key,OPENSSL_RAW_DATA,$iv,$tag);
$enc=base64_encode($iv.$tag.$ct);
$pdo=new PDO("mysql:host=mysql8;dbname=pedidos_platform;charset=utf8mb4","root","root");
$pdo->prepare("UPDATE tenants SET whatsapp_token_enc=? WHERE slug=?")->execute([$enc,"corp"]);
echo "Token actualizado OK\n";
'
```

No hace falta reiniciar nada: el webhook lee el token de la DB en cada mensaje.

**Verificar el token contra Meta (sin enviar mensaje):**

```powershell
docker exec demo_atiende_app php -r '
$key=hex2bin(str_repeat("0",64));
$pdo=new PDO("mysql:host=mysql8;dbname=pedidos_platform;charset=utf8mb4","root","root");
$r=$pdo->query("SELECT whatsapp_phone_id,whatsapp_token_enc FROM tenants WHERE slug=\"corp\"")->fetch(PDO::FETCH_ASSOC);
$d=base64_decode($r["whatsapp_token_enc"]);
$tok=openssl_decrypt(substr($d,28),"aes-256-gcm",$key,OPENSSL_RAW_DATA,substr($d,0,12),substr($d,12,16));
$ch=curl_init("https://graph.facebook.com/v25.0/".$r["whatsapp_phone_id"]."?fields=display_phone_number,verified_name");
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_HTTPHEADER=>["Authorization: Bearer $tok"]]);
echo curl_exec($ch)."\n";
'
```

Si devuelve el `display_phone_number` y `verified_name` → token + número OK. Si devuelve un `error`, ver la tabla de diagnóstico de errores de Meta más abajo.

---

### Paso 2 — Verificar el App Secret

El App Secret **no expira** y raramente cambia. Solo actualizarlo si Meta lo regeneró.

**Valor actual:** `2ed5ce245d0bc992c9a6a8bb62ada44c`

**Dónde verlo en Meta:**

1. Ir a [developers.facebook.com](https://developers.facebook.com)
2. Seleccionar la app → **Configuración** → **Básica**
3. Sección **Secreto de la aplicación** → clic en **Mostrar**

**Dónde pegarlo:**

```php
// config/global.php línea 18
define('WA_APP_SECRET', 'tu_app_secret_aqui');
```

---

### Paso 3 — Levantar los contenedores Docker

```powershell
# Desde la raíz del proyecto
docker compose up -d
```

Verificar que los 3 contenedores estén corriendo:

```powershell
docker compose ps
```

Deben aparecer: `app` (PHP-FPM), `nginx`, `mariadb` con estado `Up`.

---

### Paso 4 — Iniciar el túnel de Cloudflare

```powershell
cloudflared tunnel --url http://localhost:80
```

Esperar hasta ver una línea como:

```
https://xyz-algo-random.trycloudflare.com
```

Copiar esa URL. **Cambia cada vez que se reinicia el tunnel.**

> **Punto debil identificado:** La URL es efímera. Ver sección [Mejoras pendientes](#mejoras-pendientes) para solución con dominio fijo.

---

### Paso 5 — Actualizar el webhook en Meta

Cada vez que la URL del tunnel cambie, hay que actualizarla en Meta.

1. Ir a [developers.facebook.com](https://developers.facebook.com)
2. Seleccionar la app → **WhatsApp** → **Configuración**
3. En la sección **Webhooks**, hacer clic en **Editar**
4. Pegar la nueva URL con la ruta del webhook:

```
https://xyz-algo-random.trycloudflare.com/ws/webhook.php
```

5. En **Token de verificación** ingresar: `dev_verify_token_local`
6. Hacer clic en **Verificar y guardar**

Meta enviará un GET al endpoint para verificarlo. Si el servidor está corriendo y el token coincide, se confirma.

> **Verificación rápida:** Abrir en el navegador `https://tu-url.trycloudflare.com/ws/webhook.php` — debe responder `403` (correcto, el GET sin parámetros válidos es rechazado).

---

### Paso 6 — Probar el bot

Enviar un mensaje de WhatsApp al número configurado y verificar que responde.

**Ver logs en tiempo real:**

```powershell
docker compose logs -f app
```

Los errores del webhook aparecen con el prefijo `[webhook]`.

---

## Checklist rápido (para uso diario)

```
[ ] 1. (Solo si el token cambió) Cargar token cifrado en pedidos_platform.tenants — ver Paso 1
       Con token permanente de System User esto casi nunca hace falta.
[ ] 2. docker compose up -d
[ ] 3. cloudflared tunnel --url http://localhost:80
[ ] 4. Copiar URL del tunnel → pegar en Meta > WhatsApp > Configuración > Webhooks
[ ] 5. Verificar que Meta confirme el webhook
[ ] 6. Probar con un mensaje de WhatsApp
```

---

## Diagnóstico de problemas comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| Bot no responde a mensajes | Token expirado | Renovar `WA_ACCESS_TOKEN` (Paso 1) |
| Meta rechaza la verificación del webhook | URL del tunnel incorrecta o contenedores caídos | Repetir Pasos 3, 4 y 5 |
| Error 403 en el webhook ante mensajes reales | `WA_APP_SECRET` incorrecto | Verificar en Meta y actualizar `config/global.php` |
| `docker compose up` falla | Puerto 80 ocupado por otro proceso | `netstat -ano \| findstr :80` para identificar el proceso |
| Logs muestran `WEB_MASTER falló` | El API externo de Atiende no responde | Verificar conectividad con `api.atiende.com` |

---

## Diagnóstico de errores de Meta (el bot RECIBE pero no RESPONDE)

Si en los logs ves `[webhook] ... body=Hola` (200) pero después `[WhatsAppClient] Error al enviar`, el bot recibe bien pero falla al enviar la respuesta. El error exacto de Meta dice qué arreglar:

| Error de Meta | Significado | Solución |
|---|---|---|
| `code 190` — *"Invalid OAuth access token / Cannot parse access token"* | El token guardado no es un access token válido (expiró, o se pegó otra cosa — ej. el App Secret de 32 chars en vez del `EAA...`) | Cargar un token `EAA...` válido (Paso 1) |
| `code 100, subcode 33` — *"Object ... does not exist, cannot be loaded due to missing permissions"* sobre el `phone_id` | El token es válido pero el **System User no tiene la WABA asignada** como activo (o el número está en otro Business) | Asignar la WABA al System User (Paso 1, "Requisito imprescindible") |
| `code 131030` | App en modo **Development**: solo recibe números pre-aprobados | Agregar el número en Meta → WhatsApp → Configuración → Destinatarios de prueba (sin el `9`), o pasar la app a modo Live |
| `code 131047` / *"re-engagement message"* | Pasaron +24 h desde el último mensaje del usuario (ventana de servicio cerrada) | Solo se pueden enviar **templates** aprobados fuera de la ventana de 24 h |
| `Credenciales WhatsApp no configuradas` | El tenant no tiene token/phone_id, o falló el descifrado (clave incorrecta) | Verificar fila en `pedidos_platform.tenants` y `PLATFORM_ENCRYPTION_KEY` |

**Cómo ver el error exacto:** `docker compose logs -f app` y buscar el prefijo `[WhatsAppClient]`. Para diagnóstico fino, usar los comandos de verificación del Paso 1 (validan token + phone_id + WABA contra Meta sin enviar nada).

---

## Mejoras pendientes

- [x] **Token permanente:** ✅ Hecho (2026-06-02). El tenant `corp` usa un System User token permanente (`expires_at: 0`). Ruta para otros tenants: [business.facebook.com](https://business.facebook.com) → Configuración → Usuarios del sistema → Generar token (ver Paso 1).
- [ ] **URL fija para el webhook:** Registrar un subdominio y usar `cloudflared tunnel` con cuenta y nombre fijo (no efímero). Requiere cuenta Cloudflare y dominio propio.
- [ ] **Script de arranque único:** Crear un `start-dev.bat` o `start-dev.ps1` que ejecute `docker compose up -d` y `cloudflared tunnel` en un solo paso.
- [ ] **Alerta de token próximo a vencer:** Agregar un chequeo en el webhook que loguee cuando el token está por expirar.

---

## Problemas conocidos resueltos

| Fecha | Problema | Causa | Solución |
|---|---|---|---|
| 2026-05-26 | Bot no respondía a ningún mensaje | Tabla `motivo_reclamos` no existía en el contenedor `mysql8` | `docker exec mysql8 mysql ... CREATE TABLE motivo_reclamos` |
| 2026-05-26 | Consultas/reclamos no se registraban en DB | Tablas `consultas` y `reclamos` con charset `utf8mb3` — los emojis del nombre de WhatsApp (4 bytes) hacían fallar el INSERT silenciosamente | `ALTER TABLE consultas CONVERT TO CHARACTER SET utf8mb4` + ídem para `reclamos` |
| 2026-05-26 | Fatal error en panel de ventas (`venta.php:126`) | MySQL 8 tiene `ONLY_FULL_GROUP_BY` activado por defecto, incompatible con las queries existentes | `SET GLOBAL sql_mode = ...` + archivo permanente en `/etc/mysql/conf.d/custom.cnf` dentro del contenedor `mysql8` |
| 2026-06-02 | Bot `wb_corp` recibía "Hola" pero no respondía (`[WhatsAppClient] Authentication Error`) | En `whatsapp_token_enc` estaba guardado el **App Secret** (32 chars), no un access token (`EAA...`). Meta devolvía `code 190` | Generar System User token permanente y cargarlo cifrado en `pedidos_platform.tenants.whatsapp_token_enc` (Paso 1) |
| 2026-06-02 | Token válido pero `code 100/33 "missing permissions"` sobre el phone_id | El System User del token **no tenía la WABA asignada** como activo | Business Settings → Usuarios del sistema → Agregar activos → Cuentas de WhatsApp → marcar la WABA → Control total. Tras asignarla, envío de prueba `{"ok":true}` ✅ |

> **Nota MySQL:** Si el contenedor `mysql8` se recrea desde cero, hay que volver a crear el archivo de config y la tabla `motivo_reclamos`.

---

## Registro de cambios

| Fecha | Acción | Token/URL nuevo | Notas |
|---|---|---|---|
| 2026-05-26 | Actualización de `WA_ACCESS_TOKEN` | `EAANk0Z...Sx0EZD` | Renovación de rutina |
| 2026-05-26 | Nueva opción E (Hacer una consulta) en el menú del bot | — | Flujo dinámico desde tabla `motivo_consultas` → menuId 15 → guarda en `consultas` |
| 2026-05-26 | Reclamos ahora usan tabla `motivo_reclamos` | — | Reemplaza el menú hardcodeado (100→101→1011) por flujo dinámico desde DB |
| 2026-05-26 | Respuesta a reclamo "En analisis" por WhatsApp chat | — | `S_Respuesta.php` envía botones interactivos al usuario; `webhook.php` captura respuesta y guarda en `msj_reclamos` |
| 2026-06-02 | Token permanente + número de producción para `wb_corp` | System User token (`EAAN...`, no vence) | Número "Atiende" `+54 9 3743 63-2029` (phone_id `1181887995003553`). Token cifrado en `pedidos_platform.tenants`, WABA asignada al System User. Verificado con envío real. Doc de credenciales y diagnóstico de errores de Meta actualizados en esta bitácora |
| 2026-06-02 | **Baja / opt-out con palabras estándar + confirmación** | — | Reemplaza `bajacp`. Palabras: `BAJA`/`STOP`/`CANCELAR`/`DESUSCRIBIR` (exacto) → pide `SI` → borra de `telefonos` + reset `contactos`. Detalle en `docs/CAMBIOS-2026-06-02.md` §1 |
| 2026-06-02 | Mejoras de flujo del bot | — | Menú solo ante saludo; palabra clave no secuestra el detalle del reclamo; números sin ceros; textos de reclamo/consulta. Ver `docs/CAMBIOS-2026-06-02.md` §2 |
| 2026-06-02 | `tenantUrl()` config-driven (dev/prod) | — | `__TENANT_SCHEME__` http/https; links del bot y de pedidos respetan el entorno. §3 |
| 2026-06-02 | Configuración: permiso 11, token/secret/phone_id con ojito, editor de menús real | — | Token ahora se guarda cifrado en `pedidos_platform.tenants` desde la UI. §5 |
| 2026-06-02 | Endurecimiento de seguridad | — | HMAC sin app_secret rechazado; upload seguro; contraseñas hasheadas (script `rehash_passwords.php`); session_regenerate; guard de permisos. §8 |
| 2026-06-02 | Limpieza | — | Scripts prod legacy borrados (queda `startup.sh`); permiso "Ventas" duplicado eliminado. §9 |

> **Changelog completo de la sesión 2026-06-02:** ver [`docs/CAMBIOS-2026-06-02.md`](docs/CAMBIOS-2026-06-02.md).
