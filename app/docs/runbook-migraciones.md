# Runbook — Migraciones de schema (runner con ledger)

Cómo crear y aplicar cambios de schema/datos a **todos los tenants** de forma
idempotente y auditable. Aplica tanto en dev como en producción.

> **TL;DR para una migración nueva:**
> 1. Crear `app/_docker/migrations/YYYY-MM-DD-descripcion.php` (copiar de una existente).
> 2. Que reciba `$db = $argv[1]`, use `mig_connect($db)` y sea idempotente.
> 3. Probar: `docker exec -i atiende-app php /var/www/atiende/_docker/migrate.php --dry-run`
> 4. Aplicar: `docker exec -i atiende-app php /var/www/atiende/_docker/migrate.php`

---

## Las 3 piezas

| Archivo | Rol |
|---|---|
| `_docker/migrate.php` | **Runner**: descubre tenants, mantiene el ledger, aplica las pendientes |
| `_docker/migrations/_lib.php` | `mig_connect($db)` — conexión por tenant; resuelve credenciales dev vs prod |
| `_docker/migrations/*.php` | Las migraciones (una por cambio, nombradas por fecha) |
| `config/migrations.example.php` | Plantilla del usuario migrador DDL (el real `migrations.php` está git-ignored) |

## Cómo funciona el runner

1. **Descubre los tenants** desde `pedidos_platform.tenants WHERE estado='activo'`
   (fuente de verdad de qué bases migrar). No usa `SHOW DATABASES`.
2. **Ledger por tenant**: crea/lee una tabla `schema_migrations(filename, applied_at)`
   en cada base. Calcula las **pendientes** = migraciones en disco (orden por nombre)
   que no estén registradas.
3. **Aplica cada pendiente** ejecutando el script PHP como subproceso, pasándole la `db`.
   Si vuelve `rc=0`, la registra en el ledger (`INSERT IGNORE`). Si **falla**, detiene
   ese tenant y no aplica las siguientes (no deja el schema a medias).
4. **Doble idempotencia**: cada migración chequea `information_schema`/`SHOW COLUMNS`
   antes de actuar **y** el ledger evita re-correrlas.

```
docker exec -i atiende-app php /var/www/atiende/_docker/migrate.php [--dry-run] [db]
```

- `--dry-run` → lista las pendientes por tenant **sin aplicar nada**.
- `[db]` (opcional) → corre **solo** ese tenant en vez de todos.

> **Nota Git Bash:** prefijá con `MSYS_NO_PATHCONV=1` para que no reescriba la ruta
> absoluta del container (`/var/www/...` → `C:/Program Files/Git/var/www/...`).

---

## Escribir una migración nueva

1. **Nombre**: `app/_docker/migrations/YYYY-MM-DD-descripcion.php`. El orden alfabético
   ES el orden de aplicación → usá la fecha como prefijo. **No** uses prefijo `_`
   (esos se excluyen — son helpers como `_lib.php`).

2. **Esqueleto** (copiá de `2026-06-19-estado-motivos.php`, la referencia más limpia):

```php
<?php
// Migración idempotente por tenant: <qué hace>.
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/<este-archivo>.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

require_once __DIR__ . '/_lib.php';
$m = mig_connect($db);

// Chequear ANTES de actuar (MySQL 8 no tiene ADD COLUMN IF NOT EXISTS):
$res = $m->query("SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='mi_tabla' AND COLUMN_NAME='mi_col'");
if ($res && $res->num_rows > 0) {
    echo "[$db] = mi_tabla.mi_col (ya existe)\n";
} else {
    $m->query("ALTER TABLE `mi_tabla` ADD COLUMN `mi_col` tinyint(1) NOT NULL DEFAULT 1");
    echo "[$db] + mi_tabla.mi_col\n";
}

$m->close();
```

3. **Reglas**:
   - Usá **siempre `mig_connect($db)`** — nunca credenciales hardcodeadas
     (el viejo `'root'/'root'` no conecta en prod, ver más abajo).
   - **Idempotente**: chequeá existencia antes de cada `ALTER`/`UPDATE`/`INSERT`.
   - Si la tabla puede no existir en un tenant, chequeá con `SHOW TABLES LIKE`
     y saltá con un `echo` (no falles).
   - Defaults que **no cambien** las filas existentes (ej. `DEFAULT 1` = mantener
     visible lo que ya estaba).
   - `exit(1)` ante error real → el runner detiene ese tenant y no lo registra.

4. **Para tenants nuevos**: si el cambio también debe estar en la base recién creada,
   reflejalo además en `_docker/mariadb/atiende.sql` (schema base) y/o
   `_docker/mariadb/bot_config_seed.sql` (menú/bot_config).

---

## Credenciales: por qué `mig_connect()` y no `root`

`mig_connect($db)` (en `_lib.php`) resuelve credenciales con prioridad:

1. **Prod** — si existe `config/migrations.php` → usuario migrador DDL dedicado.
2. **Dev** — si no existe → cae a `config/database.php` (root/root).

**Por qué un usuario aparte en prod:** `root` en MySQL 8 usa `caching_sha2_password`
y el `mysqli` de PHP 7.3 **no lo negocia** → las migraciones con `root` hardcodeado
*no conectaban en prod*. El migrador usa `mysql_native_password` y privilegios mínimos
(solo DDL, no el usuario runtime de la app).

### Setup del usuario migrador en PROD (primera vez)

`config/migrations.php` está **git-ignored** (tiene el password). En el servidor:

1. Crear el usuario en MySQL:
   ```sql
   CREATE USER 'atiende_migrator'@'%' IDENTIFIED WITH mysql_native_password BY '<pass>';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER
     ON `atiende\_%`.* TO 'atiende_migrator'@'%';
   ```
   > Ojo: el `GRANT` cubre `atiende_%`. Si el runner debe escribir el ledger
   > `schema_migrations` en `pedidos_platform` (o leer `tenants` ahí), asegurá
   > también ese grant según el naming real de tus bases.
2. Copiar `config/migrations.example.php` → `config/migrations.php` y completar
   `MIG_HOST`/`MIG_USER`/`MIG_PASS`.
3. Verificar con `--dry-run` antes de aplicar.

---

## Flujo de deploy típico

```bash
# 1. git pull en el servidor (trae las migraciones nuevas)
# 2. Ver qué se aplicaría, sin tocar nada:
docker exec -i atiende-app php /var/www/atiende/_docker/migrate.php --dry-run
# 3. Aplicar a todos los tenants activos:
docker exec -i atiende-app php /var/www/atiende/_docker/migrate.php
# 4. (opcional) healthcheck post-deploy:
./_docker/healthcheck.sh   # o el path real del script
```

Re-correr el runner es seguro: el ledger + la idempotencia hacen que no re-aplique
nada ya hecho.

## Troubleshooting

- **"Conexión a '<db>' falló"** → falta/está mal `config/migrations.php` en prod, o
  el usuario migrador no tiene grant sobre esa base.
- **Una migración falla y detiene el tenant** → corregí el script (es idempotente,
  podés re-correrlo); el runner sigue desde donde quedó porque el ledger solo
  registra las que terminaron en `rc=0`.
- **Quiero re-aplicar una migración ya registrada** → borrá su fila del ledger:
  `DELETE FROM schema_migrations WHERE filename='YYYY-MM-DD-...php'` en esa base, y
  volvé a correr el runner.
- **Acentos/UTF-8 al editar datos** → no uses `mysql` CLI por `docker exec` (corrompe
  multibyte); hacelo desde el script PHP de la migración (maneja UTF-8 nativo).
