#!/usr/bin/env sh
# Railway: start the web server only. Use this as Start Command in Railway.
# Do NOT use: admin:create, make:admin-user, or any other artisan command here.
set -e
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
