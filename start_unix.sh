#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
command -v php >/dev/null || { echo "PHP 8.3+ chưa được cài."; exit 1; }
command -v composer >/dev/null || { echo "Composer chưa được cài."; exit 1; }
[ -f .env ] || cp .env.example .env
[ -f vendor/autoload.php ] || composer install
grep -q "^APP_KEY=base64:" .env || php artisan key:generate --force
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
