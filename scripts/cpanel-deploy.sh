#!/bin/bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

PHP_BIN="${PHP_BIN:-php}"
if [ -x /opt/cpanel/ea-php83/root/usr/bin/php ]; then
    PHP_BIN="/opt/cpanel/ea-php83/root/usr/bin/php"
fi

COMPOSER_BIN="${COMPOSER_BIN:-composer}"
if [ -x /opt/cpanel/composer/bin/composer ]; then
    COMPOSER_BIN="/opt/cpanel/composer/bin/composer"
fi

"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan event:clear || true
"$PHP_BIN" artisan optimize
