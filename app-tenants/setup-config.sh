#!/usr/bin/env bash
#
# setup-config.sh — Regenera el .env local (git-ignored) desde su template.
#
#   .env  <-  .env.docker.<env>
#
# El .env del panel de tenants (pedidos-platform) NO se versiona (secretos /
# valores de entorno) y por eso se PIERDE ante un `git merge`/`git pull` que lo
# borre del working tree. Síntoma: el panel tira
#   Dotenv\Exception\InvalidPathException: Unable to read ... /var/www/pedidos-platform/.env
# Este script lo vuelve a generar. Idempotente: por defecto NO pisa un .env que
# ya existe (usá --force para sobrescribir).
#
# Uso:
#   ./setup-config.sh            # entorno dev (default)
#   ./setup-config.sh --prod     # entorno prod (valores a COMPLETAR a mano)
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

template=".env.docker.${ENV}"
target=".env"

if [ ! -f "$template" ]; then
  echo "  ✗ falta el template $template" >&2; exit 1
fi
if [ -f "$target" ] && [ "$FORCE" -eq 0 ]; then
  echo "  = $target ya existe (omito; --force para sobrescribir)"
else
  cp "$template" "$target"
  echo "  + $target  <-  $template"
  if [ "$ENV" = "prod" ]; then
    echo "  ! recordá completar los valores COMPLETAR_* en $target"
  fi
fi
echo "Listo."
