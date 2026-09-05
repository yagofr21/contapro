#!/bin/sh
set -e

# Espera o banco aceitar conexões.
echo "Aguardando PostgreSQL em ${DB_HOST:-db}:${DB_PORT:-5432}..."
until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-sistema_finan}" -d "${DB_DATABASE:-sistema_finan}" >/dev/null 2>&1; do
  sleep 1
done
echo "PostgreSQL disponível."

# Instala dependências PHP se necessário.
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  echo "Executando composer install..."
  composer install --no-interaction --prefer-dist
fi

# Garante a chave da aplicação.
if [ ! -f .env ]; then
  cp .env.example .env
fi
if [ -z "$(grep -E '^APP_KEY=.+' .env 2>/dev/null || true)" ]; then
  # Prioriza a variável de ambiente; senão gera localmente.
  php artisan key:generate --ansi --no-interaction || true
fi

# Cria o banco de testes local (idempotente) e roda as migrations.
php artisan migrate --force --no-interaction || true

echo "Iniciando servidor PHP em 0.0.0.0:8000..."
exec php artisan serve --host=0.0.0.0 --port=8000