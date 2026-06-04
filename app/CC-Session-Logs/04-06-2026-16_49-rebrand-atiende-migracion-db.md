# Session Log: 04-06-2026 16:49 - rebrand-atiende-migracion-db

## Quick Reference (for AI scanning)
**Confidence keywords:** atiende, rebrand, whatsbus, clubpedidos, wb_, atiende_, mysql8, migracion-db, dump, mysqldump, docker, docker-compose, atiende_net, launch360_net, pedidos-platform, ProvisioningService, slug-collision, provisioning_failures, teardown-dry-run, MercadoPago, atiende.lat, abogado-del-diablo, PHPUnit, container-rename
**Projects:** Atiende (app legacy `whatsbus2021-main`), `pedidos-platform` (repo GitHub `diegomottadev/atiende`)
**Outcome:** Rebrand total Whatsbus/ClubPedidos → Atiende: migración de bases vivas, prefijo `wb_`→`atiende_`, red/containers Docker renombrados, dominio→atiende.lat, hardening de provisioning, y dumps demo para prod. Todo verificado (apps sirviendo, tests 14/14).

## Decisiones Made
- **Abogado del diablo** sobre pedidos-platform: el veredicto fatal es que NO es self-serve (alta de WhatsApp es manual). El usuario decidió cobrar igual y mantener el alta manual.
- **Prefijo de DB de tenants:** `wb_` → `atiende_` (decisión del usuario entre atiende_/at_/tenant_).
- **Migración de bases:** migrar TODO ahora, incluida `clubpedidos`→`atiende`.
- **Alcance del rebrand:** pedidos-platform + app legacy `whatsbus2021-main`.
- **Dominio de producción:** `atiende.lat` (reemplazó `whatsbus.com.ar`).
- **Siempre Docker, nunca xampp** (xampp local es PHP 7.4; PHPUnit 10 necesita ≥8.1). Guardado en memoria + CLAUDE.md.
- **Container/imagen names:** `demo_clubpedidos_*`→`atiende-app`/`atiende-nginx`, `whatsbus_landing`→`atiende-landing`, imagen `whatsbus2021-main-app`→`atiende-app`.
- **No renombrar** la carpeta física `whatsbus2021-main` (rompe paths/git/IDE).
- **No tocar** librerías de terceros (`public/`, `PHPExcel`, `fpdf181`, `vendor/`) ni `substr($user,3)` de teléfono.
- `cli/` y `memory/` sacados del repo pedidos-platform y movidos a `.claude/` del proyecto (gitignored).

## Soluciones & Fixes
- **Bug de fuga de datos por colisión de slug** (`ProvisioningService::ensureUniqueSlug`): ahora chequea existencia física de la DB vía `information_schema.SCHEMATA`, no solo `tenants`. `CREATE DATABASE` sin `IF NOT EXISTS` + `$paso=1` movido DESPUÉS del CREATE (evita que la compensación dropee una DB ajena).
- **`generateSlug` determinista:** reemplazado `iconv('ASCII//TRANSLIT')` (depende de libc glibc vs musl) por mapa propio de acentos → mismo slug en cualquier entorno.
- **Split de SQL robusto** (`splitSqlStatements`): respeta comillas/backticks/comentarios; descarta fragmentos solo-comentario (evita error 1065 en `PDO::exec`). Reemplaza `explode(';')`.
- **`compensate()` con registro durable:** tabla `provisioning_failures` + `cli/reconcile_provisioning.php`.
- **`teardown($id, $dryRun=true)`:** previsualiza plan y valida precondiciones; el superadmin muestra el plan y bloquea si la suscripción no está cancelada.
- **Migración de DB sin RENAME DATABASE:** `mysqldump --routines --triggers --events --single-transaction --no-tablespaces SRC | mysql DST`, verificar counts, luego DROP.
- **Reemplazo de texto byte-level (ISO-8859-1 round-trip):** preserva acentos/encoding/saltos de línea al hacer reemplazos de tokens ASCII en masa. Protección de path con regex `whatsbus(?!2021-main)`.
- **Largos de prefijo:** al cambiar `wb_`(3)→`atiende_`(8), corregir `strncmp(...,'atiende_',8)` y `substr(...,8)` en routing (Conexion.php, global.php, webhook.php, configuracion.php, pedidos/index.php).

## Files Modified
**pedidos-platform (repo git, pusheado):**
- `modelos/ProvisioningService.php`: fix slug-collision, generateSlug determinista, splitSqlStatements, compensate→provisioning_failures, teardown dry-run, prefijo `atiende_`.
- `db/pedidos_platform.sql`: tabla `provisioning_failures`, GRANT provisioner `atiende_%`.
- `superadmin/teardown.php`: preview dry-run + bloqueo por precondición.
- `superadmin/dev_provision.php`, `tenant_save.php`, `tenant_permissions_save.php`: prefijo `atiende_`.
- `docker-compose.yml`: red `atiende_net`.
- `tests/ProvisioningServiceTest.php`, `tests/bootstrap.php`: regresiones + carga de config DB.
- Commits clave: `9b2823b` (hardening), `7612c20` (axbot→bot_config), `4b23018` (prefijo atiende_ + atiende_net).

**App legacy `whatsbus2021-main` (NO es repo git — cambios solo en disco):**
- `config/database.php`, `database.docker.dev`, `database.docker.prod`: `DB_NAME=atiende`.
- `config/Conexion.php`, `config/global.php`: prefijo `atiende_` (largo 8).
- `docker-compose.yml`: container_name `atiende-app`/`atiende-nginx`, `image: atiende-app`, volúmenes `/var/www/atiende`, red `atiende_net`, extra_hosts `*.atiende.test`.
- `Dockerfile`, `startup.sh`, `startup.ps1`: paths `/var/www/atiende`, `APP_CONTAINER=demo_atiende_app`.
- `_docker/nginx/default.conf`: root `/var/www/atiende`.
- `ws/webhook.php`, `ajax/configuracion.php`, `pedidos/index.php`, `fin.php`, `finbis.php`, `send_wa.php`, `S_Pedidos_bis.php`: prefijo `atiende_`.
- `pedidos/pago.php`, `indexbis.php`, `vistas/footer.php`, `reportes/exTicket*.php`: dominio `atiende.lat`.
- ~58 archivos con texto de marca (clubpedidos/whatsbus→atiende) vía reemplazo byte-level.
- Renombrados: `_docker/mariadb/clubpedidos.sql`→`atiende.sql`, `public/css/whatsbus.css`→`atiende.css`.
- `landing/docker-compose.yml`: container_name `atiende-landing`.

**Dumps generados** (`C:\Users\ACER\Downloads\whatsbus2021-main\dumps\`):
- `atiende.sql` (480 KB, 40 tablas), `atiende_demo.sql` (74 KB, 42 tablas), `pedidos_platform_demo_tenant.sql` (5.7 KB).

## Setup & Config
- **Motor DB:** MySQL 8.0 (container `mysql8`, `root/root`, puerto 3306). El CLAUDE.md decía "MariaDB" — es MySQL.
- **Bases vivas finales:** `atiende`, `atiende_corp`, `atiende_demo`, `atiende_demo_1`, `pedidos_platform` (+ ajenas: `launch360*`, `botiquines_prod`). Dropeadas: `clubpedidos`, `wb_corp`, `wb_demo`, `wb_demo_1`.
- **Red Docker:** `atiende_net` (creada, externa) con `mysql8` conectado. Reemplaza `launch360_net`.
- **Containers:** `atiende-app` (img `atiende-app`, php:7.3-fpm-alpine), `atiende-nginx` (:81), `atiende-landing` (:8083), `pedidos_platform_nginx` (:8082), `pedidos_platform_app`, `mysql8`.
- **`PLATFORM_ENCRYPTION_KEY`** dev = 64 ceros (en `config/global.php`). Debe ser 64 hex (32 bytes).
- **Repo git:** solo `pedidos-platform` → `github.com/diegomottadev/atiende` (rama `main`). Autor Diego Motta, sin co-autor Claude.
- **CC-Session-Logs / cli / memory** del proyecto viven en `.claude/` del proyecto.

## Pending Tasks
- **Verificar `atiende.lat` en prod:** confirmar que el dominio existe y está configurado como URL de retorno en MercadoPago. Las URLs quedaron `https://atiende.lat/atiende/pedidos/...` (el subpath `/whatsbus/`→`/atiende/`) — confirmar que esa ruta exista en el deploy.
- **Carpeta `whatsbus2021-main`:** el directorio físico del repo legacy sigue con ese nombre (renombrarlo es riesgoso; quedó fuera de scope). El contenido ya es Atiende.
- **Clave de cifrado en prod:** `whatsapp_token_enc`/`app_secret_enc` del tenant demo están cifrados con la clave dev (ceros). En prod, usar la misma `PLATFORM_ENCRYPTION_KEY` o re-cargar las credenciales WhatsApp desde el superadmin. `whatsapp_phone_id` (`1181887995003553`) queda en claro.

## Key Exchanges
- Abogado del diablo (2 rondas) sobre pedidos-platform → fixes técnicos 5/6/7 implementados y verificados.
- Subida del repo a GitHub `atiende` sin atribución de Claude; `cli/`+`memory/` sacados y movidos a `.claude/`.
- Prompt de instalación en servidor + cómo instalar/autenticar Claude Code headless.
- Rebrand total a Atiende (DBs, código, Docker, dominio) con verificación HTTP/DB en cada paso.
- Dumps demo para probar en prod.

## Custom Notes
- "Abogado del diablo": la grieta #1 (no es self-serve, alta WhatsApp manual) sigue sin resolver — es decisión de negocio del usuario, no técnica.
- El reemplazo byte-level (Latin1) es la técnica segura para sweeps de tokens ASCII sin corromper acentos.

---

## Quick Resume Context
El proyecto fue rebrandeado de Whatsbus/Club Pedidos a **Atiende**: bases migradas a `atiende`/`atiende_*` en `mysql8`, prefijo de tenant `wb_`→`atiende_` (largo 8), containers/red Docker renombrados (`atiende-app`/`atiende-nginx`/`atiende-landing`/`atiende_net`), dominio `atiende.lat`. `pedidos-platform` está pusheado a GitHub (`diegomottadev/atiende`, último commit `4b23018`) con tests 14/14 verde; el app legacy `whatsbus2021-main` NO es repo git (cambios solo en disco). Hay dumps demo en `dumps/`. Pendiente: verificar `atiende.lat`+MercadoPago en prod y la clave de cifrado.

---

## Raw Session Log

1. **Usuario:** "ejecuta al abogado del diablo" → Invoqué skill `abogado-del-diablo` sobre pedidos-platform. Veredicto: no es self-serve (alta WhatsApp manual = killer), economía $15/mes inviable, riesgo plataforma Meta (ban ene-2026), bug de colisión de slug (fuga de datos), provisioning best-effort, bus factor Diego/sin git, competencia. Investigué con WebSearch (Meta ban chatbots LATAM, churn SaaS low-ARPU).

2. **Usuario:** respondió punto por punto (1 ok, 2 cobra igual, 3 alta manual, arreglá bug slug, axbot se elimina, 6/7 ok) → Arreglé el bug de slug-collision en `ProvisioningService.php` (chequeo de DB en `ensureUniqueSlug` + CREATE sin IF NOT EXISTS + paso=1 después). Detecté que la compensación habría dropeado DB ajena.

3. **Usuario:** "hazlo tu" (tests) → Escribí tests de regresión. PHP local era 7.4 → PHPUnit 10 no corre.

4. **Usuario:** "no uses xampp usa siempre Docker, agrego a memoria y claude.md" → Guardé memoria `feedback_use_docker.md` + actualicé CLAUDE.md. Commiteé y pusheé a GitHub `diegomottadev/atiende` (sin co-autor). Verifiqué .env gitignored.

5. **Usuario:** "/code-review style follow-ups" → corrí tests en Docker (PHP 8.1 + mysql8): falló `generateSlug` por iconv/musl → fix transliteración determinista → 14/14 verde → push.

6. **Usuario:** "qué dice el abogado del diablo ahora" → 2da ronda: lethal cracks siguen (premisa autoconfirmada), + grietas en mis propios fixes (race TOCTOU, reconciliación incompleta).

7. **Usuario:** "en lo técnico hacé lo del abogado" → Implementé hardening 6b (splitSqlStatements), 6a (provisioning_failures + reconcile CLI), 7 (teardown dry-run). Tests 14/14 (corregí un test que reveló error 1065). Push `9b2823b`.

8. **Usuario:** "subí a git" / "quita memory y cli de git" / "todos a .claude" → Pusheé; saqué `cli/` y `memory/` del repo y los moví a `.claude/` del proyecto (gitignored). Arreglé los `require` de los scripts movidos. Flag: `.claude` no montado en el container.

9. **Usuario:** "prompt para instalar en servidor" + "cómo instalar claude" → Generé prompt de instalación; consulté claude-code-guide para instalación/auth headless de Claude Code.

10. **Usuario:** "launch360_net por qué? debe ser atiende_net" + "no debe sobrevivir whatsbus/clubpedidos, todo atiende" + "la DB usa mysql?" → Confirmé MySQL 8.0. Mapeé superficie (290 ocurrencias/72 archivos). Decisiones vía AskUserQuestion: prefijo `atiende_`, migrar todo, scope pedidos-platform+legacy.

11. **Ejecución del rebrand:** edité config legacy (DB_NAME, prefijo largo 3→8); migré 4 bases vía mysqldump|mysql (counts verificados); actualicé `tenants.db_name`; dropeé bases viejas; creé `atiende_net`+conecté mysql8; rebrandeé pedidos-platform (push `7612c20`,`4b23018`); verifiqué HTTP (legacy 302/login "Atiende", platform 200) + conexión DB.

12. **Usuario:** "funciona?" → Verifiqué con curl + docker ps: sí.

13. **Usuario:** "los names del docker compose deben ser atiende-app no whatsbus2021, lo mismo landing" → Edité compose legacy + landing (container_name/image/volúmenes/red/extra_hosts), recreé stacks → `atiende-app`/`atiende-nginx`/`atiende-landing`. Verifiqué HTTP + DB OK.

14. **Usuario:** "es atiende.lat" → Reemplazo byte-level de texto de marca (58 archivos), corregí dominio a `atiende.lat`, renombré `clubpedidos.sql`→`atiende.sql` y `whatsbus.css`→`atiende.css`, fixes quirúrgicos de prefijo en routing (sin tocar `substr($user,3)` de teléfono), purgué imagen vieja. Verifiqué login "Atiende" sin errores.

15. **Usuario:** "dump de la base demo y el tenant demo para probar en prod" → Generé `atiende.sql`, `atiende_demo.sql`, `pedidos_platform_demo_tenant.sql` en `dumps/`. Flag: clave de cifrado dev.

16. **Usuario:** "quiero guardar la sesión" → expliqué opciones → "/compress" → este log.
