# Servidor FrankenPHP + Octane para desenvolvimento local.
# HTTPS auto-assinado (tls internal / certificado local) e workers persistentes.
FROM dunglas/frankenphp:1.12.7-php8.5-trixie

# Extensões necessárias para a aplicação (Postgres, Redis, intl, etc.).
RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        mbstring \
        bcmath \
        dom \
        pcntl \
        sockets \
        opcache \
        redis

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        postgresql-client \
        git \
        unzip \
        ca-certificates \
        curl \
    && rm -rf /var/lib/apt/lists/*

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 80 443 8000 2019
