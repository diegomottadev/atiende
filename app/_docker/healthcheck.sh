#!/usr/bin/env bash
# Healthcheck de Atiende — corre los chequeos post-sync/deploy y reporta PASS/FALLA.
#
#   Uso:   bash app/_docker/healthcheck.sh
#   Vars:  APP=atiende-app DBC=mysql8 PORT=81 DBPASS=root bash app/_docker/healthcheck.sh
#
# Sale con código 0 si todo OK, 1 si algún chequeo falla (útil para CI/automatización).
set -uo pipefail
export MSYS_NO_PATHCONV=1   # Git Bash en Windows mangulea las rutas de docker exec

APP="${APP:-atiende-app}"
DBC="${DBC:-mysql8}"
PORT="${PORT:-81}"
DBPASS="${DBPASS:-root}"
APPROOT="/var/www/atiende"

fails=0
pass() { echo "  PASS  $1"; }
fail() { echo "  FALLA $1"; fails=$((fails + 1)); }

echo "================ 1) GIT ================"
git fetch origin -q 2>/dev/null
if [ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ]; then
  pass "local == origin/main (sincronizado)"
else
  fail "difiere de origin/main -> $(git status -sb | head -1)"
fi
pend=$(git status --porcelain | grep -v "_backup_articulos_flat" | wc -l | tr -d ' ')
if [ "$pend" -eq 0 ]; then pass "sin cambios sin commitear"; else fail "$pend archivo(s) sin commitear"; fi

echo "================ 2) APP HTTP ================"
code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${PORT}/" 2>/dev/null)
if [ "$code" = "302" ] || [ "$code" = "200" ]; then pass "app responde (HTTP $code)"; else fail "app no responde (HTTP $code)"; fi
if curl -sL "http://localhost:${PORT}/" 2>/dev/null | grep -q "<title>Atiende</title>"; then
  pass "login renderiza"
else
  fail "login no renderiza"
fi

echo "================ 3) MIGRACIONES (ledger) ================"
dry=$(docker exec -i "$APP" php "$APPROOT/_docker/migrate.php" --dry-run 2>&1)
pendmig=$(echo "$dry" | grep -oE "[0-9]+ pendiente" | grep -vE "^0 " | wc -l | tr -d ' ')
if [ "$pendmig" -eq 0 ]; then
  pass "0 migraciones pendientes en todos los tenants"
else
  fail "$pendmig tenant(s) con migraciones pendientes"
  echo "$dry" | grep -vE "0 pendiente"
fi

echo "================ 4) PHP -l (archivos clave) ================"
for f in modelos/BotEngine.php ajax/configuracion.php _docker/migrate.php _docker/migrations/_lib.php; do
  if docker exec -i "$APP" php -l "$APPROOT/$f" 2>&1 | grep -q "No syntax errors"; then pass "$f"; else fail "$f"; fi
done

echo "================ 5) ESTADO EN DB (por tenant) ================"
tenants=$(docker exec -i "$DBC" mysql -uroot -p"$DBPASS" -N -e \
  "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'atiende%'" 2>/dev/null)
for db in $tenants; do
  has=$(docker exec -i "$DBC" mysql -uroot -p"$DBPASS" -N -e \
    "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='bot_config'" 2>/dev/null)
  if [ "$has" != "1" ]; then echo "  (skip $db: sin bot_config)"; continue; fi
  r=$(docker exec -i "$DBC" mysql -uroot -p"$DBPASS" "$db" -N -e "
    SELECT
      (SELECT COUNT(*) FROM motivo_reclamos WHERE opcionId='99' AND estado=0),
      (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND COLUMN_NAME='estado' AND TABLE_NAME IN ('motivo_reclamos','motivo_consultas')),
      (SELECT (menu_json LIKE '%recuperarCodigoCliente%' AND menu_json LIKE '%registrarOlvidoReclamo%') FROM bot_config WHERE id=1)
  " 2>/dev/null)
  op=$(echo "$r" | cut -f1); cols=$(echo "$r" | cut -f2); menu=$(echo "$r" | cut -f3)
  if [ "$op" = "1" ] && [ "$cols" = "2" ] && [ "$menu" = "1" ]; then
    pass "$db (op99 estado0=$op, cols estado=$cols/2, menu wiring=$menu)"
  else
    fail "$db (op99 estado0=$op, cols estado=$cols/2, menu wiring=$menu)"
  fi
done

echo "================ 6) TESTS UNITARIOS ================"
for t in BotEngineProyectarMenuTest TelefonoNormalizarTest; do
  out=$(docker exec -i "$APP" php "$APPROOT/tests/$t.php" 2>&1)
  if echo "$out" | grep -qiE "^OK|pasaron"; then pass "$t ($out)"; else fail "$t -> $out"; fi
done

echo ""
if [ "$fails" -eq 0 ]; then echo "✅ TODO OK"; exit 0; else echo "❌ $fails chequeo(s) fallaron"; exit 1; fi
