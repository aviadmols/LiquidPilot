#!/usr/bin/env sh
# Railway: run before app starts. Installs DB (migrate + seed).
# Requires: DB connection env vars (e.g. DB_URL, DB_CONNECTION) and APP_KEY.

set -e

# Generate key if missing (e.g. first deploy)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  php artisan key:generate --force
fi

php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
