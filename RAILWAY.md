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

## 5. Start Command (important – 502 fix)

**Do not** set the Start Command to `admin:create` or `make:admin-user`. That command asks for a password and will hang the container, causing "Application failed to respond" / 502.

- In **Railway Dashboard** → your service → **Settings** → **Deploy** (or **Start Command**): **clear the field** or remove `php artisan admin:create ...` so the app uses the repo default.
- This repo has a [Procfile](Procfile) and [railpack.json](railpack.json) that set the correct start command (`php artisan serve --host=0.0.0.0 --port=...`). If Start Command is empty, Railpack will use them.
- If you prefer to set it explicitly in Railway, use:
  ```bash
  php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
  ```

To create an admin user on production, run **once** in Railway → your service → **Shell** (or a one-off run):
  ```bash
  php artisan make:admin-user your@email.com --password=YourSecurePassword
  ```
  Or use `admin:create your@email.com --password=YourPassword`. Do **not** use this as the Start Command.

## Summary

- **Database**: Postgres/MySQL as a Service, variables `DB_CONNECTION` and `DB_URL`.
- **Pre-Deploy**: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
- **Start Command**: Leave empty or use `php artisan serve --host=0.0.0.0 --port=${PORT:-8080}`. Never use `admin:create` as the start command.
- **Variables**: `APP_KEY`, `APP_URL`, `APP_ENV`, `LOG_CHANNEL`, and DB connection.

After the first deploy, the database will be migrated and seeded and the app will be up.

## If the app crashes after build

1. **Pre-Deploy must run** – If Pre-Deploy is not set or fails, migrations (e.g. `brand_kit_id` on `agent_runs`) will not run and the app can crash on first request. In **Settings → Deploy**, set **Pre-Deploy Command** to:
   ```bash
   chmod +x ./railway/init-app.sh && ./railway/init-app.sh
   ```
2. **Check deploy logs** – In Railway, open the **Deployments** tab and the latest deployment. Check **Build Logs** and **Deploy Logs** (runtime). If Pre-Deploy fails, fix the error (e.g. `DATABASE_URL` / `DB_URL` missing).
3. **Variables** – Ensure `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, and the database URL are set. For Postgres, the linked DB service usually provides `DATABASE_URL`; map it to `DB_URL` or set `DATABASE_URL` as in your `.env` / `config/database.php`.
4. **Health** – The app exposes a health route at `/up`. After deploy, open `https://your-app.railway.app/up` to confirm the app responds.
