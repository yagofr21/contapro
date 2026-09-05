#!/bin/bash
# Scripts utilitários para desenvolvimento dentro do container app.
# Uso: ./dev.sh <comando...>
set -euo pipefail

cd "$(dirname "$0")"

if [ $# -eq 0 ]; then
  echo "Uso: $0 <comando> [args...]"
  echo "Ex.: $0 composer install"
  echo "     $0 artisan migrate"
  echo "     $0 php -v"
  echo "     $0 shell"
  exit 1
fi

CMD="$*"
docker compose exec -T app bash -lc "$CMD"
