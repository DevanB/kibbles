#!/bin/sh
set -e

APP_USER="${APP_USER:-www-data}"
DB_FILE="${DB_DATABASE:-/app/database/data/database.sqlite}"

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    storage/app/private \
    bootstrap/cache \
    "$(dirname "$DB_FILE")"

if [ ! -f "$DB_FILE" ]; then
    touch "$DB_FILE"
fi

if [ -n "$DB_DATABASE" ] && [ "$DB_DATABASE" != ":memory:" ]; then
    php -r 'try { $pdo = new PDO("sqlite:".getenv("DB_DATABASE")); $pdo->exec("PRAGMA journal_mode=WAL"); } catch (Throwable $exception) { fwrite(STDERR, $exception->getMessage().PHP_EOL); }'
fi

ln -sfn /app/storage/app/public /app/public/storage

run_as_app() {
    if [ "$(id -u)" = "0" ] && command -v setpriv >/dev/null 2>&1; then
        setpriv --reuid="$APP_USER" --regid="$APP_USER" --init-groups "$@"
    else
        "$@"
    fi
}

if [ "$(id -u)" = "0" ]; then
    chown -R "$APP_USER":"$APP_USER" storage bootstrap/cache "$(dirname "$DB_FILE")" /config /data 2>/dev/null || true
    chmod -R ug+rwX storage bootstrap/cache "$(dirname "$DB_FILE")" 2>/dev/null || true
fi

run_as_app php artisan optimize --no-interaction || echo "entrypoint: optimize failed, continuing uncached" >&2

if [ "$1" = "frankenphp" ]; then
    run_as_app php artisan migrate --force --no-interaction
fi

if [ "$(id -u)" = "0" ] && command -v setpriv >/dev/null 2>&1; then
    exec setpriv --reuid="$APP_USER" --regid="$APP_USER" --init-groups "$@"
fi

exec "$@"
