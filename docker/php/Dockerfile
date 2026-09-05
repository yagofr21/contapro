FROM php:8.5-cli-bookworm

# Dependências de sistema.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libicu-dev libzip-dev libonig-dev \
        libxml2-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        postgresql-client \
        curl git unzip ca-certificates pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Extensão GD (comparável com imagem oficial do PHP).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd

# Extensão Intl.
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl

# Extensões essenciais para Laravel 13 + Octane.
# OPCache já é compilado na imagem base php:8.5 (habilitado via php.ini).
RUN docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        bcmath \
        pcntl \
        sockets

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions dom

# Extensão phpredis (driver Redis recomendado para Octane/cache/filas).
RUN pecl install redis \
    && docker-php-ext-enable redis

# Configurações de memória e upload para dev.
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/zz-memory.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/zz-uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/zz-uploads.ini \
    && echo "date.timezone=America/Sao_Paulo" > /usr/local/etc/php/conf.d/zz-timezone.ini

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000
