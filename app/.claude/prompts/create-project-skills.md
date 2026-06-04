# Meta-Prompt: Crear Skills del Proyecto Atiende

Generá los siguientes archivos de skill en `.claude/skills/` para este
proyecto. Cada skill debe ser un archivo Markdown con frontmatter YAML
y cuerpo de instrucciones concretas para este stack específico.

Stack: PHP 8+ · MySQLi raw + PDO · MariaDB · Docker · Bootstrap 5 ·
jQuery · DataTables · SweetAlert2 · WhatsApp Cloud API · MercadoPago ·
Stripe · MapboxGL · multi-tenant (`wb_{slug}` por tenant).

---

## Skill 1 — `php-standards.md`

Título: **Estándares PHP — Atiende**

Cubrí:
- Naming: clases PascalCase, métodos camelCase, constantes UPPER_SNAKE
- Archivos: un modelo = un archivo en `modelos/`, un handler = un archivo
  en `ajax/`
- Siempre `declare(strict_types=1)` en archivos nuevos
- Nunca suprimir errores con `@` — capturar y loguear
- Nunca `exit`/`die` en producción — lanzar excepción o retornar JSON
  de error
- Tipado: type hints en parámetros y retornos siempre que sea posible
- Comentarios: solo cuando el WHY no es obvio; nunca comentar el WHAT
- Todo output JSON via `json_encode` con `JSON_UNESCAPED_UNICODE`
- Separar lógica de negocio (modelos/) de handlers HTTP (ajax/)
- No duplicar lógica: si el mismo bloque aparece en 2+ archivos, extraer
  a modelo o helper en config/

---

## Skill 2 — `db-patterns.md`

Título: **Patrones de Base de Datos — Atiende**

Cubrí:
- **Nunca** concatenar input del usuario en SQL → usar siempre
  prepared statements con `bind_param` o PDO
- Para queries simples usar `ejecutarConsulta()` (Conexion.php)
- Para queries con input del usuario usar PDO con `prepare/execute`
- Multi-tenant: toda query debe operar sobre la DB del tenant activo
  (`$_SESSION['tenant_db']`); nunca hardcodear el nombre de DB
- ORDER BY con input del usuario: usar whitelist de columnas permitidas,
  nunca concatenar directo
- Transactions: usar `$conexion->begin_transaction()` cuando hay
  múltiples escrituras relacionadas
- Siempre cerrar resultsets con `$result->free()` en loops largos
- Índices: toda FK debe tener índice; columnas usadas en WHERE
  frecuentes también
- Soft delete: usar `deleted_at` en lugar de DELETE físico para
  entidades críticas (tenants, usuarios, pedidos)
- Nombrado de tablas: snake_case, plural (ej: `link_pedidos`,
  `area_consultas`)

---

## Skill 3 — `security-patterns.md`

Título: **Patrones de Seguridad — Atiende**

Cubrí:
- **Input validation**: validar tipo, longitud y formato antes de usar
  cualquier `$_GET`/`$_POST`/`$_FILES`; nunca confiar en validación
  del lado cliente
- **Output escaping**: todo dato de DB o input de usuario que se
  imprima en HTML debe ir envuelto en `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`
- **CSRF**: todo endpoint POST en `ajax/` debe verificar un token CSRF
  guardado en `$_SESSION['csrf_token']`; generarlo en login con
  `bin2hex(random_bytes(32))`
- **Auth check**: todo archivo en `ajax/` y `axadmin/` debe empezar
  con verificación de sesión activa; si no hay sesión → `http_response_code(401)`
  y `exit(json_encode(['error'=>'Unauthorized']))`
- **IDOR**: al leer/modificar un recurso por ID, verificar que pertenece
  al tenant activo; nunca confiar solo en el ID del request
- **File uploads**: validar extensión contra whitelist + magic bytes;
  guardar fuera del webroot o con nombre UUID aleatorio; nunca ejecutar
  archivos subidos
- **Webhook WA**: verificar firma `X-Hub-Signature-256` con
  `hash_equals()` antes de procesar cualquier payload entrante
- **Sesiones**: llamar `session_regenerate_id(true)` después de login;
  cookies con `httponly=1` y `secure=1` en producción
- **Errores**: nunca mostrar stack traces al usuario; en producción
  loguear internamente y devolver mensaje genérico
- **Credenciales**: solo en `config/database.php` y `config/global.php`
  (git-ignored); nunca en código fuente ni comentarios

---

## Skill 4 — `frontend-patterns.md`

Título: **Patrones Frontend — Atiende**

Cubrí:
- **Estructura de vistas**: toda vista CRUD usa el card pattern definido
  en CLAUDE.md (card > card-body filtros > hr > card-body p-0 tabla >
  card-body formulario)
- **DataTables**: inicializar con `language: {url: '../public/...'}`;
  recargar con `.ajax.reload(null, false)`; stat chips con `.text()` no
  `.val()`; después de `.empty()` sobre un trigger de tooltip, llamar
  `t.hide(); t.dispose()` antes
- **SweetAlert2**: spinners con `Swal.fire({didOpen:()=>Swal.showLoading()})`
  durante async; cerrar con `icon:'success'` o `icon:'error'` + mensaje
  del servidor
- **select2**: height fix: single `height:31px;line-height:29px`;
  multiple `max-height:31px;overflow:hidden`
- **Tooltips Bootstrap**: siempre `trigger:'hover'`; nunca el default
  `'hover focus'` (deja tooltips colgados)
- **Botones de acción**: texto "Nuevo" (no "Agregar"); excepción:
  "Agregar Artículos" en ingreso.php
- **Excel import**: usar el upload-zone component (dashed border, display
  filename, clear button, submit disabled hasta tener archivo); ver
  referencias en cliente.php/articulo.php
- **Formularios**: deshabilitar submit durante el POST async para evitar
  doble envío; rehabilitar en el callback
- **AJAX**: siempre manejar el caso `error:` en $.ajax además de
  `success:`; mostrar Swal.fire con icon:'error' al usuario
- **URLs**: usar `globalUrl` (= `tenantUrl()`) para construir URLs
  absolutas del tenant; nunca hardcodear dominios en JS

---

## Skill 5 — `api-design.md`

Título: **Diseño de Endpoints — Atiende**

Cubrí:
- **Formato de respuesta**: todo endpoint en `ajax/` devuelve JSON;
  estructura estándar:
  `{"ok": true, "data": [...]}` o `{"ok": false, "error": "mensaje"}`
- **HTTP status codes**: 200 éxito, 400 input inválido, 401 sin auth,
  403 sin permiso, 404 recurso no encontrado, 500 error interno
- **Método HTTP**: GET para leer, POST para crear/modificar/eliminar
  (limitación del stack actual); nunca GET con side effects
- **Parámetros**: validar presencia y tipo de todos los parámetros
  antes de ejecutar lógica; devolver 400 si falta algo requerido
- **Paginación**: DataTables server-side usa `start` + `length`;
  siempre incluir `recordsTotal` y `recordsFiltered` en la respuesta
- **Exports**: endpoints de Excel/PDF devuelven el archivo con headers
  `Content-Disposition: attachment`; no mezclar con endpoints JSON
- **WhatsApp send** (`ajax/send_wa.php`): leer `type` antes de validar;
  solo `text` es requerido para tipos no-location; devolver el array
  real de WhatsAppClient para que el JS pueda mostrar éxito/error
- **Idempotencia**: operaciones de provisioning y webhooks deben ser
  idempotentes (INSERT IGNORE + affected_rows check)
- **Rate limiting**: endpoints de envío WA deben tener protección
  contra spam (mínimo: verificar que el pedido pertenece al tenant
  antes de enviar)

---

## Skill 6 — `whatsapp-integration.md`

Título: **Integración WhatsApp Cloud API — Atiende**

Cubrí:
- **Credenciales**: per-tenant desde `pedidos_platform.tenants`; se
  cargan en `config/Conexion.php` (flujo admin) o inline con PDO
  (flujo pedidos); nunca hardcodeadas
- **Cliente**: usar siempre `config/WhatsAppClient.php`; métodos
  retornan `['ok'=>true]` o `['ok'=>false,'error'=>msg]`
- **Números**: normalizar con `normalizePhone()` antes de enviar;
  AR wa_id `549XXXXXXXXXX` → `54XXXXXXXXXX` (quitar el `9` mobile);
  nunca agregar `15` (prefijo PSTN obsoleto)
- **Texto**: `sendText($to, $text)` usa `preview_url:true` automático
- **Ubicación**: `sendLocation($to,$lat,$lng,$name='',$address='')`;
  NO pasar name/address vacíos con string vacío — omitirlos
  completamente o WhatsApp renderiza un pin de búsqueda Maps en vez
  del pin nativo
- **Webhook**: verificar `X-Hub-Signature-256` con `hash_equals()`;
  responder 200 inmediatamente antes de procesar para evitar timeouts
  de Meta; procesar de forma asíncrona si es posible
- **Verify token**: endpoint GET del webhook compara
  `hub.verify_token` contra `WA_VERIFY_TOKEN` de config
- **Errores Meta**: capturar y loguear el mensaje de error real de la
  API; `send()` usa `http_errors:false` para no tirar excepción
- **Templates**: para mensajes fuera de la ventana de 24hs, usar
  message templates aprobados; `sendText` solo funciona dentro de
  la ventana

---

## Skill 7 — `multi-tenant.md`

Título: **Arquitectura Multi-Tenant — Atiende**

Cubrí:
- **Routing de tenant**: `$_SESSION['tenant_db']` contiene la DB activa
  (`wb_{slug}` o `atiende` para legacy); se setea en login
- **Conexión**: `config/Conexion.php` usa `$_SESSION['tenant_db']`
  para conectar; `config/Connection.php` usa `setDatabase()` para el
  flujo de pedidos sin sesión
- **Aislamiento**: toda query debe operar sobre el tenant activo;
  verificar que el recurso solicitado pertenece al tenant antes de
  devolver/modificar datos
- **Credenciales WA**: cargadas desde `pedidos_platform.tenants` en
  Conexion.php; accesibles como constantes `WA_PHONE_NUMBER_ID`,
  `WA_ACCESS_TOKEN`, `WA_APP_SECRET` dentro del request
- **tenant_mode**: `mix|b2b|b2c` desde `pedidos_platform.tenants`;
  accesible via `getWebMasterConfig()['data']`; determina lógica de
  canal y joins de clientes
- **URLs**: construir con `tenantUrl($slug)` → `http://{slug}.__TENANT_DOMAIN__`;
  nunca hardcodear dominios
- **Provisioning**: nuevo tenant = nueva DB `wb_{slug}` creada desde
  `pedidos-platform`; schema base en `_docker/mariadb/atiende.sql`
- **pedidos-platform**: app separada que maneja compra, provisioning y
  superadmin; NO modificar el app Atiende para lógica de plataforma
- **Legacy**: DB `atiende` sigue existiendo para tenants viejos;
  coexiste con el modelo `wb_*`; nunca romper las rutas legacy

---

## Instrucciones de generación

Para cada skill:
1. Crear el archivo en `.claude/skills/{nombre}.md`
2. Usar este frontmatter:
```yaml
---
name: {nombre-sin-.md}
description: "{una línea describiendo cuándo usar este skill}"
---
```
3. El cuerpo debe ser una lista de reglas concretas y accionables,
   no teoría general — reglas específicas para ESTE proyecto
4. Incluir ejemplos de código PHP/JS cuando la regla no es obvia
5. Mencionar archivos de referencia del proyecto cuando existan

Creá los 7 skills en orden. Confirmá cada uno antes de continuar
con el siguiente.
