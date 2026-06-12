#!/usr/bin/env sh
# migrate-all-tenants.sh
# -----------------------------------------------------------------------------
# Corre TODAS las migraciones idempotentes de _docker/migrations/ sobre TODOS
# los tenants reales del entorno, y verifica el resultado.
#
# Pensado para resolver el SCHEMA DRIFT entre tenants: columnas que los modelos
# ya escriben (clientes.cuil/dni, contactos.vendedor_codigo, bot_config.costo_*/
# admin_*) pero que faltan en tenants donde la migración nunca se corrió → el
# form responde "No se pudo actualizar los datos" (MySQL error 1054).
#
# Auto-descubre los tenants reusando la MISMA conexión mysqli('mysql8','root',
# 'root') que usan los scripts de migración, así enumera exactamente las bases
# que las migraciones van a alcanzar. Por eso sirve igual en dev y en prod
# AUNQUE prod tenga tenants distintos: no hay nombres de DB hardcodeados.
#
# Uso:
#   # auto-descubrir y migrar todos los tenants atiende*:
#   APP_CONTAINER=atiende-app ./migrate-all-tenants.sh
#
#   # o apuntar a tenants específicos (saltea el auto-descubrimiento):
#   APP_CONTAINER=atiende-app ./migrate-all-tenants.sh atiende_corp atiende_xyz
#
# Variables de entorno:
#   APP_CONTAINER  nombre del contenedor de la app (default: atiende-app)
#   MIG_DIR        ruta de las migraciones DENTRO del contenedor
#                  (default: /var/www/atiende/_docker/migrations)
#
# NOTA prod: las migraciones se conectan a host 'mysql8' con root/root
# (hardcodeado en cada script). Si en producción el servicio MySQL o las
# credenciales difieren, ajustá esos scripts o el entorno antes de correr esto.
# -----------------------------------------------------------------------------
set -eu

APP_CONTAINER="${APP_CONTAINER:-atiende-app}"
MIG_DIR="${MIG_DIR:-/var/www/atiende/_docker/migrations}"

# ¿docker disponible?
command -v docker >/dev/null 2>&1 || { echo "!! docker no está en PATH"; exit 1; }

echo ">> Contenedor app: $APP_CONTAINER"
echo ">> Migrations dir: $MIG_DIR"

# --- 1) Tenants: por argumento, o auto-descubrir ----------------------------
if [ "$#" -gt 0 ]; then
  TENANTS="$*"
  echo ">> Tenants (por argumento):"
else
  echo ">> Descubriendo tenants vía $APP_CONTAINER (misma conexión que las migraciones)..."
  TENANTS="$(docker exec "$APP_CONTAINER" php -r '
    $m = new mysqli("mysql8","root","root");
    if ($m->connect_errno) { fwrite(STDERR, "conexion: ".$m->connect_error."\n"); exit(1); }
    $r = $m->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE \"atiende%\" ORDER BY SCHEMA_NAME");
    while ($row = $r->fetch_row()) echo $row[0], "\n";
  ')"
  echo ">> Tenants encontrados:"
fi

[ -n "$TENANTS" ] || { echo "!! No se encontraron tenants atiende*"; exit 1; }
for db in $TENANTS; do echo "   - $db"; done

# --- 2) Migraciones: glob del directorio, orden cronológico (date-prefixed) --
MIGRATIONS="$(docker exec "$APP_CONTAINER" sh -c "ls \"$MIG_DIR\"/*.php 2>/dev/null" | sort)"
[ -n "$MIGRATIONS" ] || { echo "!! No se encontraron migraciones en $MIG_DIR"; exit 1; }
echo ">> Migraciones a aplicar (idempotentes, en orden):"
for m in $MIGRATIONS; do echo "   - $(basename "$m")"; done

# --- 3) Correr cada migración en cada tenant --------------------------------
for db in $TENANTS; do
  echo ""
  echo "############ $db ############"
  for mig in $MIGRATIONS; do
    docker exec "$APP_CONTAINER" php "$mig" "$db" || echo "!! falló $(basename "$mig") en $db"
  done
done

# --- 4) Verificación de columnas por tenant ---------------------------------
echo ""
echo ">> Verificación final:"
for db in $TENANTS; do
  docker exec "$APP_CONTAINER" php -r '
    $db = $argv[1];
    $m = new mysqli("mysql8","root","root",$db);
    if ($m->connect_errno) { printf("   %-28s ERROR conexion\n", $db); exit; }
    $q = function($sql) use ($m){ $r = $m->query($sql); if(!$r) return -1; $x = $r->fetch_row(); return (int)$x[0]; };
    $cli = $q("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\"$db\" AND TABLE_NAME=\"clientes\" AND COLUMN_NAME IN (\"cuil\",\"dni\")");
    $con = $q("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\"$db\" AND TABLE_NAME=\"contactos\" AND COLUMN_NAME=\"vendedor_codigo\"");
    $bot = $q("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\"$db\" AND TABLE_NAME=\"bot_config\" AND COLUMN_NAME IN (\"costo_envio\",\"costo_envio_activo\",\"admin_telefono\",\"admin_envio_activo\")");
    printf("   %-28s clientes.cuil/dni=%d/2  contactos.vendedor_codigo=%d/1  bot_config.costo/admin=%d/4\n", $db, $cli, $con, $bot);
  ' "$db"
done

echo ""
echo ">> Listo. (clientes sin tabla bot_config — p.ej. la DB legacy 'atiende' — mostrarán 0/4, es esperado)"
