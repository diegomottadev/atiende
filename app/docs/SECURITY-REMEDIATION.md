# Remediación de seguridad — Atiende (2026-06-12)

Resultado de la auditoría (`.claude/agents/security-audit.md`) y su corrección.
Causa raíz transversal: `Connection::runQuery`/`ejecutarConsulta` ejecutan SQL crudo y los
callers concatenaban input; y ausencia de `htmlspecialchars` en la capa de salida.

## ✅ Corregido y verificado (en código, lint OK)

### Autenticación de endpoints abiertos
- `ajax/exports/exportar{Clientes,Articulos,Vendedores,Repartidores}.php` → `require auth.php` (devolvían dumps sin login). Verificado: 401 sin sesión.
- `pedidos/excel.php`, `pedidos/reporteAjax.php` (legacy) → `require auth.php`.
- `ws/post.php` (legacy, sin HMAC, con SQLi) → **neutralizado** (`http_response_code(410); exit`). El webhook real es `ws/webhook.php` (firma HMAC OK).

### SQLi (escape `Connection::escape` / `intval` / prepared-equivalente)
- Públicos: `pedidos/S_Pedidos.php`, `S_Pedidos_bis.php`, `R_Cliente.php`, `pedidos/index.php` (`$_GET[ped]`).
- `ws/m/`: `resp.php`, `respc.php`, `S_Respuesta.php`, `C_Respuesta.php`, `movil.php`, `movilc.php`.
- Autenticados: `ajax/mapa.php` (fechas + `IN()` + aislamiento tenant), `ajax/aExcelConsulta.php`, `ajax/excel.php`, `ajax/consultas.php`, `ajax/motivo.php`, `ajax/motivoConsulta.php`.
- Modelos: `Consultas.php` (`$year` int + fechas validadas), `Reparto.php` (IN-lists), `subirarchivo.php` (import: escape de celdas + extensión por whitelist), `up_file.php` (whitelist de tabla/ext + auth), `BotEngine.php` (2º orden: `$pushname`/`$detalle`/`$mensaje`/`$user` escapados en INSERT reclamos/consultas/contactos).

### XSS (output encoding `htmlspecialchars` / `json_encode` HEX)
- `ajax/reclamo.php`, `ajax/consulta.php`, `ajax/reparto.php`, `ajax/venta.php` (mensajes/razonSocial/descripcion en celdas y `onclick`).
- `pedidos/index.php` (bloque `<script>` con datos de cliente → `json_encode` anti-breakout).
- `vistas/venta.php`, `repartos.php` (`$_GET[pedidoid]`→int), `escritorio.php`, `escritorioAnterior.php` (year/fecha reflejados), `headerv1.php` (`$_SESSION[nombre]`).

### Aislamiento multi-tenant
- `ajax/mapa.php`, `aExcel.php`, `aExcelVentas.php`, `bajarPedidos.php` → `Connection::setDatabase($_SESSION['tenant_db'])` (corrían sobre `DB_NAME`).

### Exposición de datos
- `modelos/Usuario.php` `mostrar()`/`listar()` → columnas explícitas, sin `clave`.

### Hardening de PHP/sesión (requiere reinicio del contenedor)
- `_docker/php/php.ini` + montaje en `docker-compose.yml`: `display_errors=Off`, `expose_php=Off`,
  `session.cookie_httponly=1`, `session.cookie_samesite=Lax`, `session.use_strict_mode=1`.
  **Aplicar:** `docker compose up -d app`.

## ⏳ Pendiente — requiere decisión/infra (NO aplicado en vivo)

1. **Bajar de root MySQL.** Script listo: `sql/grant_app_user.sql` (crear usuario `atiende_app`,
   poner credenciales en env, ajustar `config/database.php`). Es lo que contiene el blast-radius
   de cualquier SQLi residual.

2. **`PLATFORM_ENCRYPTION_KEY`.** Dev usa ceros (OK, sin tokens reales). **En prod** completar el
   placeholder `COMPLETAR_64_HEX_CHARS` de `config/global.docker.prod` con 64 hex aleatorios
   (`openssl rand -hex 32`). Si en algún prod ya se cifraron tokens con la clave de ceros, hay que
   re-cifrar (descifrar con ceros → cifrar con la nueva) antes de cambiarla, o se pierde el acceso
   al bot. Formato cripto: AES-256-GCM, payload = `iv(12)||tag(16)||ciphertext` (ver `config/Conexion.php`).

3. **`session.cookie_secure=1`** en `_docker/php/php.ini`: activar SOLO cuando prod sirva 100% HTTPS
   (en dev http rompería el login). Está comentado y señalado.

4. **IDOR del link de pedido.** `pedidos/index.php` valida solo `link_pedidos.id` (autoincrement
   secuencial); el `token` existe en la fila pero no se compara. Fix correcto (cambio de diseño,
   no aplicado): (a) generar `token` aleatorio largo al crear el link (hoy `post.php`/flujo guarda
   el nombre de empresa, predecible), (b) distribuir el link como `/pedidos/{id}/{token}`,
   (c) validar `hash_equals($row['token'], $tokenDeLaURL)` además del id. Requiere tocar la
   generación del link en `BotEngine.php` y el formato de la URL.

5. **CSRF en login** (`vistas/login.php`): no implementado para no romper el flujo pre-sesión;
   mitigado parcialmente por `SameSite=Lax`. Pendiente token pre-sesión si se quiere cerrar del todo.
