#!/bin/bash
set -e

echo "=== Installing system deps ==="
apt-get update -qq
apt-get install -y -qq libpq-dev libicu-dev libzip-dev libonig-dev \
    libxml2-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev pkg-config

echo "=== Configuring and installing GD ==="
docker-php-ext-configure gd --with-freetype --with-jpeg
docker-php-ext-install -j4 gd
echo "GD OK"

echo "=== Configuring and installing Intl ==="
docker-php-ext-configure intl
docker-php-ext-install -j4 intl
echo "INTL OK"

echo "=== Installing remaining extensions ==="
docker-php-ext-install -j4 pdo_pgsql pgsql mbstring zip bcmath pcntl sockets
echo "ALL EXTENSIONS OK"

echo "=== Installing Composer ==="
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer || curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
echo "COMPOSER OK"

echo "=== Final PHP check ==="
php -m
echo "DONE"
