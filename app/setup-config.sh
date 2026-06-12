#!/usr/bin/env bash
#
# setup-config.sh — Regenera los configs locales (git-ignored) desde sus templates.
#
#   config/database.php  <-  config/database.docker.<env>
#   config/global.php     <-  config/global.docker.<env>
#
# Estos archivos NO se versionan (contienen secretos / valores de entorno) y
# por eso se PIERDEN ante un `git merge`/`git pull` que los borre del working
# tree. Este script los vuelve a generar. Es idempotente: por defecto NO pisa
# un archivo que ya existe (usá --force para sobrescribir).
#
# Uso:
#   ./setup-config.sh            # entorno dev (default)
#   ./setup-config.sh --prod     # entorno prod
#   ./setup-config.sh --force    # sobrescribe aunque ya exista
#
set -euo pipefail
cd "$(dirname "$0")"

ENV="dev"
FORCE=0
for arg in "$@"; do
  case "$arg" in
    --dev)   ENV="dev" ;;
    --prod)  ENV="prod" ;;
    --force) FORCE=1 ;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "Argumento desconocido: $arg" >&2
       echo "Uso: $0 [--dev|--prod] [--force]" >&2; exit 1 ;;
  esac
done

ensure() {
  local name="$1"
  local target="config/${name}.php"
  local template="config/${name}.docker.${ENV}"
  if [ ! -f "$template" ]; then
    echo "  ✗ falta el template $template" >&2; exit 1
  fi
  if [ -f "$target" ] && [ "$FORCE" -eq 0 ]; then
    echo "  = $target ya existe (omito; --force para sobrescribir)"
  else
    cp "$template" "$target"
    echo "  + $target  <-  $template"
  fi
}

echo "Generando config de entorno: $ENV"
ensure database
ensure global
echo "Listo."
