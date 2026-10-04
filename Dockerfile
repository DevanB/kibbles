# syntax=docker/dockerfile:1

FROM composer:2 AS composer

FROM dunglas/frankenphp:php8.5 AS base

WORKDIR /app

RUN install-php-extensions \
    pdo_sqlite \
    sqlite3 \
    pcntl \
    posix \
    intl \
    zip \
    opcache \
    bcmath \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

ENV SERVER_NAME=":8080" \
    CADDY_GLOBAL_OPTIONS="auto_https off"

FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM base AS assets

COPY --from=oven/bun:1.4.2 /usr/local/bin/bun /usr/local/bin/bun
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN touch /tmp/build.sqlite \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && bun install --frozen-lockfile \
    && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
       APP_ENV=production \
       APP_DEBUG=false \
       DB_CONNECTION=sqlite \
       DB_DATABASE=/tmp/build.sqlite \
       bun run build

FROM base AS app

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        storage/app/private \
        bootstrap/cache \
        database/data \
    && composer dump-autoload --no-dev --optimize --no-scripts \
    && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
       APP_ENV=production \
       APP_DEBUG=false \
       DB_CONNECTION=sqlite \
       DB_DATABASE=/tmp/build.sqlite \
       php artisan package:discover --ansi --no-interaction \
    && chmod +x /usr/local/bin/entrypoint \
    && rm -f /tmp/build.sqlite

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1:8080/up') === false ? 1 : 0);"

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
