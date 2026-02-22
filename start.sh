#!/usr/bin/env sh
# Railway: start web server + queue worker so agent runs work without a separate worker process.
set -e
PORT="${PORT:-8080}"
echo "Starting Laravel on 0.0.0.0:${PORT}"
# Re-cache config at runtime so DATABASE_URL and other env vars from Railway are used (build-time cache may have been created without them).
php artisan config:clear
php artisan config:cache
# Run queue worker in background (processes agent jobs from the database queue).
echo "Starting queue worker (database)..."
php artisan queue:work database --tries=3 --sleep=3 &
exec php artisan serve --host=0.0.0.0 --port="$PORT"
