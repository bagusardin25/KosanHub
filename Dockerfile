# syntax=docker/dockerfile:1
#
# Multi-stage build KosanHub
#   base     : PHP 8.3 + ekstensi yang dibutuhkan Laravel
#   deps-dev : dependency lengkap (termasuk PHPUnit) untuk stage test
#   test     : menjalankan seluruh test saat build -> build GAGAL jika ada test merah
#   deps-prod: dependency production saja (tanpa dev)
#   runtime  : image akhir yang di-push ke Docker Hub (dipakai service app & worker)
#
# Jenkins:  docker build --target test .   -> stage Test
#           docker build .                  -> stage Build Image (stage test dilewati)

ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache unzip \
    && docker-php-ext-install pdo_mysql \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# ---------------------------------------------------------------------------
FROM base AS deps-dev
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# ---------------------------------------------------------------------------
FROM deps-dev AS test
COPY . .
RUN composer dump-autoload --optimize \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && php artisan test

# ---------------------------------------------------------------------------
FROM base AS deps-prod
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader --no-dev

# ---------------------------------------------------------------------------
FROM base AS runtime
COPY --from=deps-prod /var/www/html/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi \
    && rm -rf tests .env \
    && rm -f /usr/bin/composer \
    && sed -i 's/\r$//' docker/entrypoint.sh \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

# Nomor build Jenkins ikut ditanam ke image, tampil di GET /health ("version").
ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION} \
    PHP_CLI_SERVER_WORKERS=4

USER www-data
EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
