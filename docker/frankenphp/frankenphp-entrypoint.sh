#!/bin/sh
# Entrypoint do servidor FrankenPHP (Octane) para desenvolvimento local.
# O HTTPS auto-assinado é tratado pelo Caddyfile (frankenphp/Caddyfile > tls internal).

# Aguarda o banco de dados.
echo "[entrypoint] Aguardando o banco de dados PostgreSQL..."
until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -q; do
    sleep 2
done
echo "[entrypoint] Banco de dados pronto."

if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "[entrypoint] Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f /var/www/.env ]; then
    echo "[entrypoint] Criando .env a partir do .env.example..."
    cp /var/www/.env.example /var/www/.env
fi

if ! grep -q "^APP_KEY=" /var/www/.env || [ -z "$(grep '^APP_KEY=.*' /var/www/.env | cut -d= -f2-)" ]; then
    echo "[entrypoint] Gerando APP_KEY..."
    php artisan key:generate --force --ansi
fi

echo "[entrypoint] Aplicando migrações..."
php artisan migrate --force --no-interaction

if [ ! -d /var/www/public/build ]; then
    echo "[entrypoint] public/build ausente — execute 'npm install && npm run build' no serviço node."
fi

# Octane (FrankenPHP) em modo worker, servindo a app com HTTPS auto-assinado.
# O Caddyfile (frankenphp/Caddyfile) define tls internal para o certificado local.
exec frankenphp run --config /frankenphp/Caddyfile