# Railway – Setup and run

When the project is deployed on Railway, configure the following so the database and app run correctly.

---

## ✅ Start command from code (fixes 502)

This repo has **[railway.json](railway.json)** with `"startCommand": "sh start.sh"`. **Railway uses config from the repo and overrides the dashboard.** So after you push and redeploy, the correct start command runs even if the dashboard had something else (e.g. `admin:create`). No need to change the dashboard – just push, redeploy, and the app should start.

---

## ⚠️ If you still see: "Run with --password=... Do NOT use this command"

**Your dashboard Start Command was wrong.** With `railway.json` in the repo it should be overridden. If 502 persists:

1. Find the **"Start Command"** / **"Custom Start Command"** field.
2. Set it **exactly** to one of these (copy-paste, do not add anything else):
   ```bash
   sh start.sh
   ```
   Or:
   ```bash
   php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
   ```
   If the field is empty, Railway may use the Procfile; if the app still crashes, use `sh start.sh` explicitly.
3. Save and **Redeploy**. The app will then start the HTTP server instead of the admin-creation command.

---

## 1. Add a database (Postgres) – required

- In Railway: **New** → **Database** → **Postgres**.
- **Link the Postgres service to your app**: open your **app service** → **Variables** → **Add variable** (or use the **Connect** tab on the Postgres service to connect to your app). The app must receive **`DATABASE_URL`** (or set `DB_URL` to the same value). Without this, the app uses SQLite (`/app/database/database.sqlite`), which is ephemeral on Railway and not suitable for production. If logs show `Connection: sqlite`, the app is **not** using Postgres – link the database and redeploy.

## 2. Environment variables

In the app service **Variables**, add at least:

| Variable | Value / note |
|----------|--------------|
| `APP_KEY` | Run locally `php artisan key:generate` and paste the value, or leave empty and the init script will generate it on first run. |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | The URL Railway gives (e.g. `https://xxx.railway.app`) |
| `DB_CONNECTION` | Optional if `DATABASE_URL` is set (app then defaults to `pgsql`). Otherwise set `pgsql`. |
| `DATABASE_URL` or `DB_URL` | **Required for production.** From linked Postgres (e.g. reference `${{Postgres.DATABASE_URL}}` in the app’s Variables). |
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

- In **Railway Dashboard** → your service → **Settings** → **Deploy** → **Start Command**: set **exactly** to `sh start.sh` or to `php artisan serve --host=0.0.0.0 --port=${PORT:-8080}`. Do **not** leave here `admin:create` or `make:admin-user`.
- This repo has [start.sh](start.sh) (run with `sh start.sh`), a [Procfile](Procfile), and [railpack.json](railpack.json). If your app keeps crashing, set Start Command to `sh start.sh` explicitly.

**Default login after seed:** `admin@example.com` / `password` (change after first login). To create another admin in production, run **once** in Railway → your service → **Shell**:
  ```bash
  php artisan make:admin-user your@email.com --password=YourSecurePassword
  ```
  Or `admin:create your@email.com --password=YourPassword`. Do **not** use this as the Start Command.

## Summary

- **Database**: Postgres/MySQL as a Service, variables `DB_CONNECTION` and `DB_URL`.
- **Pre-Deploy**: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
- **Start Command**: Set to `sh start.sh` or `php artisan serve --host=0.0.0.0 --port=${PORT:-8080}`. Never use `admin:create` or `make:admin-user`.
- **Variables**: `APP_KEY`, `APP_URL`, `APP_ENV`, `LOG_CHANNEL`, and DB connection.

After the first deploy, the database will be migrated and seeded and the app will be up.

## Pre-deploy command failed

If the deploy fails at **Pre deploy command**:

1. Open the deployment and expand **Pre deploy command** in the log. The script prints `[Pre-deploy] ...` before each step. The line after the last successful step shows which command failed.
2. **"ERROR: migrate failed"** – The database is not reachable or a migration errored. Ensure the **Postgres** service is **linked** to your app so `DATABASE_URL` is set. In the same log, Laravel prints the SQL or exception (e.g. connection refused, syntax error). Fix the cause (link DB, fix env, or fix the migration) and redeploy.
3. **key:generate / config:cache failed** – Usually non-fatal; the script continues. If the deploy still fails, check that the app has write access to `storage` and `bootstrap/cache`.
4. **"chmod" or "script not found"** – Ensure **Pre-Deploy Command** is exactly: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh` and that `railway/init-app.sh` is in the repo.

---

## If the app crashes after build

1. **Start Command must NOT be admin:create** – If deploy logs show "Run with --password=... Do NOT use this command as the app start command", the Start Command is set to create an admin user. Clear it or set to `php artisan serve --host=0.0.0.0 --port=${PORT:-8080}` (see warning at top of this file).
2. **Pre-Deploy must run** – If Pre-Deploy is not set or fails, migrations (e.g. `brand_kit_id` on `agent_runs`) will not run and the app can crash on first request. In **Settings → Deploy**, set **Pre-Deploy Command** to:
   ```bash
   chmod +x ./railway/init-app.sh && ./railway/init-app.sh
   ```
3. **Check deploy logs** – In Railway, open the **Deployments** tab and the latest deployment. Check **Build Logs** and **Deploy Logs** (runtime). If Pre-Deploy fails, fix the error (e.g. `DATABASE_URL` / `DB_URL` missing).
4. **Variables** – Ensure `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, and the database URL are set. For Postgres, the linked DB service usually provides `DATABASE_URL`; map it to `DB_URL` or set `DATABASE_URL` as in your `.env` / `config/database.php`.
5. **Health** – The app exposes a health route at `/up`. After deploy, open `https://your-app.railway.app/up` to confirm the app responds.

## 500 error on the site

If the app starts but the site returns **500 Internal Server Error**:

1. **Database** – The app uses **Postgres** on Railway. Ensure the Postgres service is **linked** to your app (Variables will get `DATABASE_URL`). The app uses `DATABASE_URL` when `DB_URL` is not set. If you use a different DB variable, set `DB_CONNECTION=pgsql` and `DB_URL` to your connection string.
2. **Pre-Deploy ran** – Migrations must run. In **Settings → Deploy**, **Pre-Deploy Command** must be: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`. Redeploy so that migrations (including `brand_kit_id` on `agent_runs`) are applied.
3. **APP_KEY** – Must be set. If empty, run locally `php artisan key:generate` and paste the value into **Variables**.
4. **See the real error** – In Railway → your service → **Deployments** → open the latest deployment → **View Logs** (or **Deploy Logs**). Reload the site and check the logs; Laravel will print the exception there (when `LOG_CHANNEL=stderr`). Fix the reported error (e.g. missing column = run migrations, connection refused = wrong DB_URL/DATABASE_URL).
5. **Test /up** – Open `https://your-app.railway.app/up`. If that returns 200 but `/` or `/admin` returns 500, the problem is likely DB or session (migrations, DB connection, or APP_KEY).

## 502 Bad Gateway

502 means Railway’s proxy **did not get a valid response** from your app (app not running, crashed, or wrong port).

1. **Start Command** – It **must** start the web server. In **Settings → Deploy → Start Command** set exactly to:
   ```bash
   sh start.sh
   ```
   Not `admin:create`, not `make:admin-user`, not empty if your platform ignores the Procfile.
2. **Deploy Logs** – In **Deployments** → latest deploy → **View Logs**. After deploy you should see:
   - `Starting Laravel on 0.0.0.0:XXXX` (then the server is starting).
   - If you see `Run with --password=...` again, the Start Command is still wrong (see top of this file).
   - If you see a PHP fatal error, fix that (missing env, missing DB, etc.).
3. **Pre-Deploy** – If Pre-Deploy fails (e.g. `migrate` fails because DB is missing), the deploy might be marked failed and the container might not start. Set **Pre-Deploy Command** to: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`, and ensure **Variables** include `DATABASE_URL` (from linked Postgres) or `DB_URL` and `DB_CONNECTION=pgsql`.
4. **Port** – The app must listen on the port Railway sets (`PORT`). `start.sh` uses `$PORT`; do not override it in the Start Command.
