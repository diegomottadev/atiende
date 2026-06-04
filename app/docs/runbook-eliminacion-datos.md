# Runbook: Eliminación de Datos de Usuario (cumplimiento Meta + Ley 25.326)

Última actualización: 02/06/2026
Tiempo estimado: 10–30 min (migración por tenant) · 2–5 min (procesar una solicitud)
Riesgo: ALTO — la ejecución anonimiza datos del titular de forma irreversible.

> Audiencia: Diego (ops) y operadores/admins de tenant.
> Fuente de verdad: `docs/superpowers/specs/2026-06-02-eliminacion-datos-usuario-design.md`.
> Decisiones cerradas: SLA 10 días hábiles · responsable de datos = **Atiende (plataforma)**, los
> comercios son encargados del tratamiento · política = **anonimización total irreversible** (no
> borrado físico de datos identificatorios; se conservan solo registros transaccionales sin PII por
> obligación legal) · callback de Meta (H2) **implementado**.
> Multi-tenant: cada tenant tiene su propia DB (`wb_<slug>` / `atiende`). Todo opera sobre la
> DB del tenant activo (`$_SESSION['tenant_db']`). Las credenciales de Meta son per-tenant.

---

## Pre-requisitos

- [ ] Acceso al servidor de producción del tenant.
- [ ] Docker corriendo (`docker compose ps` muestra app/nginx/mariadb healthy).
- [ ] Credenciales de MariaDB del tenant (en `config/global.docker.prod`).
- [ ] Acceso de lectura a `pedidos_platform.tenants` (lista de slugs y credenciales de Meta).
- [ ] Para configurar el callback: acceso al **Meta Developer Portal** de la app de WhatsApp del tenant.
- [ ] Para procesar solicitudes desde el panel: usuario con el permiso **"Eliminación de datos"**.

---

## Parte A — Cómo se procesa una solicitud end-to-end

### Estados de una solicitud

```
pendiente ──(OTP verificado)──► verificada ──(operador aprueba)──► en_proceso ──► completada
    │                                                                   │
    │                                                                   └─(error)─► verificada (rollback)
    │
    └─ pendiente_identificacion  (llega por callback de Meta sin teléfono mapeable)
    │
    └─ rechazada  (operador rechaza con motivo, en cualquier punto)
```

### Flujo según el origen

1. **Formulario público** (`eliminar-datos.php?t=<slug>`)
   - El titular ingresa su teléfono → recibe OTP por WhatsApp → lo confirma.
   - La solicitud queda en `verificada`, lista para que un operador la apruebe.
2. **Callback de Meta** (`data-deletion-callback.php?t=<slug>`)
   - Meta hace POST con `signed_request` firmado. Se valida HMAC con el `app_secret` del tenant.
   - Se crea la solicitud en `pendiente_identificacion` (el `user_id` de Meta NO es el teléfono).
3. **Operador** (`vistas/eliminaciones.php`)
   - Lista las solicitudes de **su** tenant (teléfono siempre enmascarado).
   - **Aprobar** una solicitud `verificada` → ejecuta la anonimización transaccional (`EliminacionDatos::ejecutar`).
   - **Rechazar** con un motivo (queda auditado).

> Política aplicada: **anonimización total irreversible — ningún `DELETE` físico en ninguna tabla**.
> Todos los datos identificatorios (nombre, teléfono, dirección, email, contenido de conversaciones)
> se sustituyen en sitio por marcadores. Las filas se conservan (preservan importes, métricas e
> integridad referencial) pero quedan disociadas de la identidad del titular. Donde la PII es la
> clave primaria (`telefonos.telefono`, `contactos.id`) se reemplaza por un **token pseudónimo
> determinístico `del_<hash16>`** (HMAC + pepper, irreversible) para no violar la PK. Los registros
> que se conservan (pedidos/importes por retención fiscal) ya no contienen PII.

### Procesar una solicitud desde el panel (operador)

1. Ingresá al panel → **Eliminación de datos** (`vistas/eliminaciones.php`).
2. Filtrá por estado `verificada`.
3. Tocá la fila para ver el detalle: qué tablas se van a anonimizar.
4. **Aprobar y ejecutar**. Aparece un spinner mientras corre la transacción.
   - En éxito: estado `completada` + resumen de filas afectadas por tabla.
   - En error: rollback automático; la solicitud vuelve a `verificada` y queda una fila `error` en auditoría.

⚠️ La anonimización es **irreversible**. Verificá el código de seguimiento antes de aprobar. No hay "deshacer".

### Verificación post-ejecución

- [ ] La solicitud figura en `completada` con `resuelta_at` y `resuelta_por`.
- [ ] En `data_deletion_audit` hay una fila por cada tabla afectada (`tabla_anonimizada`) + una `aprobada`.
- [ ] `data_deletion_requests.telefono_norm` y `otp_hash` quedaron en `NULL` (purga de PII residual; solo queda `telefono_hash`).
- [ ] Los registros transaccionales conservados (p.ej. `pedidos`) ya no contienen datos identificatorios del titular.
- [ ] La página pública de estado (`estado-eliminacion.php?code=...`) muestra `Completada`.

---

## Parte B — Aplicar la migración de las tablas nuevas

Crea `data_deletion_requests` y `data_deletion_audit` (y el permiso) en cada DB de tenant. La
migración es **idempotente** (`CREATE TABLE IF NOT EXISTS`), así que es seguro re-ejecutarla.

### B.1 Archivos involucrados

- Migración: `_docker/mariadb/migrations/2026-06-02_data_deletion.sql`
- Dump base (para tenants futuros): el SQL base en `_docker/mariadb/` debe incluir estas tablas + el permiso.

### B.2 Correr en un solo tenant (manual)

```bash
# Reemplazá <slug> por el slug real (p.ej. campostrini). DB destino: wb_<slug>.
docker exec -i <mariadb_container> \
  mariadb -uroot -p<DB_PASSWORD> wb_<slug> \
  < _docker/mariadb/migrations/2026-06-02_data_deletion.sql
```

```bash
# La DB legacy/principal también:
docker exec -i <mariadb_container> \
  mariadb -uroot -p<DB_PASSWORD> atiende \
  < _docker/mariadb/migrations/2026-06-02_data_deletion.sql
```

### B.3 Correr en todos los tenants (runner)

El runner PHP itera todos los slugs activos y aplica la migración. Es idempotente: registra por
tenant si la tabla ya existía, se creó, o hubo error.

```
1. PDO a pedidos_platform → SELECT slug FROM tenants WHERE deleted_at IS NULL
2. Por cada slug → USE wb_<slug> → ejecutar el .sql
3. Ejecutar también en `atiende`
4. Loguear resultado por tenant (ok / ya existía / error)
```

```bash
# Ejecutar dentro del container de la app:
docker exec -it <app_container> php /var/www/html/_docker/mariadb/migrations/run_data_deletion.php
```

### B.4 Insertar el permiso "Eliminación de datos"

Va en la tabla `permiso` de cada tenant. Idempotente (no duplica si ya existe):

```sql
INSERT INTO permiso (nombre)
SELECT 'Eliminación de datos' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permiso WHERE nombre = 'Eliminación de datos');
```

Asignación por defecto: **Admin tenant**. Para operadores, opt-in vía `usuario_permiso`.

### Verificación post-migración

- [ ] `SHOW TABLES LIKE 'data_deletion_%';` en cada `wb_*` devuelve las 2 tablas.
- [ ] `SELECT * FROM permiso WHERE nombre='Eliminación de datos';` devuelve 1 fila por tenant.
- [ ] El runner no reportó `error` para ningún slug.

### Rollback de la migración

La migración solo agrega tablas; no toca datos existentes. Si hiciera falta revertir en un tenant:

```sql
-- Solo si NO hay solicitudes reales registradas. Borra auditoría primero (FK).
DROP TABLE IF EXISTS `data_deletion_audit`;
DROP TABLE IF EXISTS `data_deletion_requests`;
```

⚠️ No hagas `DROP` si ya hay solicitudes procesadas: perdés la evidencia de cumplimiento (la
auditoría se conserva 5 años — spec §13.8).

---

## Parte C — Configurar la URL de callback en el Meta Developer Portal

El **callback de Meta (H2) está implementado y debe configurarse en cada app de WhatsApp** (además
del formulario público). Cada tenant tiene su propia app de WhatsApp en Meta, con su propio
`app_secret`. Por eso la URL del callback lleva el slug del tenant en el query string (Meta no
envía el tenant).

### Pasos

1. Entrá al **Meta Developer Portal** → la app del tenant → **WhatsApp** (o **App Settings**).
2. Buscá la sección **Data Deletion Callback URL** (y "Data Deletion Instructions URL").
3. Configurá ambas URLs:
   - **Data Deletion Instructions URL** (pública, sin firma):
     `https://<dominio-del-tenant>/eliminar-datos.php?t=<slug>`
   - **Data Deletion Callback URL** (recibe el `signed_request` firmado):
     `https://<dominio-del-tenant>/data-deletion-callback.php?t=<slug>`
4. Guardá. Meta hace un POST de prueba; debe responder `{ "url": "...", "confirmation_code": "..." }`.

⚠️ El `<slug>` debe existir en `pedidos_platform.tenants` (con `deleted_at IS NULL`) y tener el
`app_secret` cargado. Sin slug válido no se puede validar la firma HMAC (el `app_secret` es per-tenant)
y el callback responde 404/500.

> El callback se configura para **todos** los tenants con WhatsApp activo: es parte del cumplimiento
> exigido por Meta, no opcional. La página de instrucciones + el formulario por sí solos no cubren
> el requisito del callback firmado.

### Verificación

- [ ] `POST https://<dominio>/data-deletion-callback.php?t=<slug>` con un `signed_request` válido devuelve `{url, confirmation_code}`.
- [ ] Una firma inválida devuelve HTTP 400 y queda auditada (no se procesa).
- [ ] La solicitud creada aparece en el panel del tenant en estado `pendiente_identificacion`.

---

## Parte D — Caso "pendiente de identificación" (callback de Meta sin teléfono)

**Por qué ocurre:** el `user_id` que Meta envía en el `signed_request` es un identificador de
Facebook *scoped a la app*. **No es el `wa_id` ni el teléfono** y no se puede mapear automáticamente
a las tablas del tenant. Por eso la solicitud nace en `pendiente_identificacion`.

### Qué hacer

1. En `vistas/eliminaciones.php`, filtrá por estado `pendiente_identificacion`. La columna de
   teléfono muestra `(s/ident.)`.
2. **No se puede aprobar** una solicitud `pendiente_identificacion` sin un teléfono (el handler
   devuelve `422 Falta identificar el teléfono del titular`). La anonimización necesita el
   `telefono_norm` para resolver los `clienteId` del titular.
3. Opciones para resolverla:
   - **Pedir al titular que use el formulario público** (`eliminar-datos.php`): al verificar por OTP,
     queda una solicitud `verificada` con el teléfono real, que sí se puede ejecutar. Esta es la vía
     recomendada y la que cierra el círculo del cumplimiento.
   - **Identificación manual por un operador**: si por otro canal (mail al correo de privacidad de
     Atiende) el titular acredita su identidad y su número, el operador carga el teléfono en la
     solicitud y recién ahí la aprueba.
   - **Rechazar** si no es posible identificar al titular, indicando el motivo (queda auditado).

⚠️ Nunca aproximes el teléfono "a ojo". Si no hay verificación de identidad o teléfono confirmado,
**rechazá** la solicitud — no anonimices datos que podrían no ser del solicitante.

### Verificación

- [ ] La solicitud `pendiente_identificacion` terminó en `completada` (vía formulario / identificación manual) o `rechazada` con motivo.
- [ ] El código de confirmación devuelto a Meta sigue resolviendo en `estado-eliminacion.php` (idempotencia por `meta_user_id`).

---

## Errores comunes

| Error / síntoma | Causa | Solución |
|---|---|---|
| Callback responde `404 tenant no encontrado` | `?t=<slug>` ausente o slug inexistente/borrado | Verificá el slug contra `tenants` (deleted_at IS NULL); corregí la URL en Meta. |
| Callback responde `500 callback no disponible` | `app_secret` del tenant no configurado en `tenants` | Cargá `whatsapp_app_secret_enc` para ese tenant antes de activar el callback. |
| Callback responde `400 firma inválida` | `app_secret` equivocado o `signed_request` alterado | Confirmá que el `app_secret` en `tenants` coincide con el de la app de Meta. |
| `422 Falta identificar el teléfono del titular` al aprobar | Solicitud en `pendiente_identificacion` sin teléfono | Resolvé por formulario público o identificación manual (Parte D). |
| `409 La solicitud no está en un estado aprobable` | La solicitud no está `verificada` (ya completada/rechazada) | Refrescá la lista; no se re-ejecuta una solicitud ya resuelta (idempotencia). |
| `500 No se pudo completar la anonimización (rollback aplicado)` | Falla a mitad de la transacción multi-tabla | La transacción revirtió todo; la solicitud volvió a `verificada`. Revisá la fila `error` en `data_deletion_audit` (sin PII) y reintentá. |
| El OTP no le llega al titular | Número en formato incorrecto, o app de Meta en modo desarrollo (error #131030) | El número debe ir SIN el `9` móvil (ej. `+54 376 427 8402`). En modo dev, agregar el número como test recipient en Meta. |
| `Requested unknown parameter 'N'` en la tabla del panel | Índice de columna desfasado en DataTables | Revisar `columnDefs` de `eliminaciones.php` (mismo gotcha que `repartos.php`). |
| Solicitud `completada` pero aún figura el teléfono en `data_deletion_requests` | La purga de PII residual no corrió | El paso final debe setear `telefono_norm=NULL` y `otp_hash=NULL`; revisar `EliminacionDatos::ejecutar`. |
