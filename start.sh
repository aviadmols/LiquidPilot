#!/usr/bin/env sh
# Railway: start the web server only. Use this as Start Command in Railway.
# Do NOT use: admin:create, make:admin-user, or any other artisan command here.
set -e
PORT="${PORT:-8080}"
echo "Starting Laravel on 0.0.0.0:${PORT}"
# Re-cache config at runtime so DATABASE_URL and other env vars from Railway are used (build-time cache may have been created without them).
php artisan config:clear
php artisan config:cache
exec php artisan serve --host=0.0.0.0 --port="$PORT"
