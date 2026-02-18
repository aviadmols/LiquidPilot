# Railway – Setup and run

When the project is deployed on Railway, configure the following so the database and app run correctly.

## 1. Add a database (Postgres or MySQL)

- In Railway: **New** → **Database** → **Postgres** (or MySQL).
- Railway will set the `DATABASE_URL` variable (or Postgres provides `DATABASE_URL`).

## 2. Environment variables

In the app service **Variables**, add at least:

| Variable | Value / note |
|----------|--------------|
| `APP_KEY` | Run locally `php artisan key:generate` and paste the value, or leave empty and the init script will generate it on first run. |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | The URL Railway gives (e.g. `https://xxx.railway.app`) |
| `DB_CONNECTION` | `pgsql` (if using Postgres) or `mysql` |
| `DB_URL` | If you added Postgres: `${{Postgres.DATABASE_URL}}` (reference to the DB service variable). For MySQL: the URL provided by the MySQL service. |
| `LOG_CHANNEL` | `stderr` (recommended on Railway) |

## 3. Pre-Deploy command (DB setup and seed)

In **Settings** → **Deploy** → **Pre-Deploy Command** enter:

```bash
chmod +x ./railway/init-app.sh && ./railway/init-app.sh
```

This runs on every deploy:

- `php artisan migrate --force`
- `php artisan db:seed --force`
- If `APP_KEY` is missing, it will be generated.

After saving, Railway will run this command and then start the app.

## 4. Build (if needed)

If you have a frontend (Vite etc.) – in **Build** you can set **Custom Build Command**:

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

(Otherwise Nixpacks will run `composer install`.)

## Summary

- **Database**: Postgres/MySQL as a Service, variables `DB_CONNECTION` and `DB_URL`.
- **Pre-Deploy**: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
- **Variables**: `APP_KEY`, `APP_URL`, `APP_ENV`, `LOG_CHANNEL`, and DB connection.

After the first deploy, the database will be migrated and seeded and the app will be up.
