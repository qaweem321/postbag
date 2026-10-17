# syntax=docker/dockerfile:1
# One image for development, tests and production. Apache + mod_php serves the app; the same
# image runs the queue worker and the scheduler with a different command.

FROM composer:2.10.2 AS composer

FROM php:8.4.25-apache-trixie AS base
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath pcntl opcache \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV APACHE_DOCUMENT_ROOT=/app/public COMPOSER_ALLOW_SUPERUSER=0
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -ri 's/Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite headers
WORKDIR /app

# Development: the source is mounted, so nothing is copied in. Apache listens on 8080 so it can
# run as an unprivileged user.
FROM base AS dev
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

FROM base AS build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM base AS app
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "opcache.validate_timestamps=0\nexpose_php=Off\n" > "$PHP_INI_DIR/conf.d/zz-app.ini" \
    && echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/conf-available/security.conf
COPY --from=build --chown=www-data:www-data /app /app
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s CMD php -r "exit(@file_get_contents('http://127.0.0.1:8080/up') === false ? 1 : 0);"
