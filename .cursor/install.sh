#!/usr/bin/env bash
# Idempotent Cloud Agent install. Runs from the repo root during each Build.
set -euo pipefail

composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress --no-scripts
composer run-script post-autoload-dump

if [[ ! -f .env ]]; then
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=.+' .env || grep -qE '^APP_KEY=[[:space:]]*$' .env; then
    php artisan key:generate --ansi --no-interaction
fi

mkdir -p database
if [[ ! -f database/database.sqlite ]]; then
    touch database/database.sqlite
fi

php artisan migrate --force --no-interaction

bun install
bun run build
bunx playwright install chromium
