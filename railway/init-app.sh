#!/usr/bin/env sh
# Railway: run before app starts. Installs DB (migrate + seed).
# Requires: DB connection env vars (DATABASE_URL or DB_URL) and APP_KEY.

# Do not use set -e so we can report which step failed
FAILED=0

echo "[Pre-deploy] Generating APP_KEY if missing..."
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  php artisan key:generate --force || FAILED=1
fi

echo "[Pre-deploy] Clearing config cache..."
php artisan config:clear || true

echo "[Pre-deploy] Running migrations..."
if ! php artisan migrate --force; then
  echo "[Pre-deploy] ERROR: migrate failed. Check DATABASE_URL / DB_URL and that the database is reachable."
  exit 1
fi

echo "[Pre-deploy] Seeding database..."
php artisan db:seed --force || true

echo "[Pre-deploy] Caching config..."
php artisan config:cache || FAILED=1

if [ "$FAILED" = "1" ]; then
  echo "[Pre-deploy] One or more optional steps failed; continuing."
fi
echo "[Pre-deploy] Done."
