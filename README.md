# Theme Catalog & AI Agent Pipeline

Laravel application that ingests a Shopify Theme 2.0 ZIP, builds a Theme Catalog, and runs an AI agent (OpenRouter) to generate an updated Home page JSON and export a new theme ZIP.

## Requirements

- PHP 8.3+
- Composer
- MySQL or PostgreSQL (or SQLite for development)
- Redis (optional; for queues; can use `database` driver)
- Node.js & NPM (for Filament assets)

## Installation

1. Clone and install dependencies:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

2. Configure `.env`:

- `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for your database
- `QUEUE_CONNECTION=redis` (or `database` for local dev without Redis)
- Optional: `OPENROUTER_BASE_URL` (default: https://openrouter.ai/api/v1)
- Optional: `THEME_ZIP_MAX_SIZE_BYTES` (default: 100MB)

3. Run migrations and seed prompt templates:

```bash
php artisan migrate
php artisan db:seed
```

4. Log in to the admin panel:

- After `php artisan db:seed`, use **admin@example.com** / **password** (change after first login).

5. Build frontend assets (Filament):

```bash
npm install && npm run build
```

6. Start the queue worker (required for theme analysis and agent runs):

```bash
php artisan queue:work
```

On Windows, Laravel Horizon is not used (requires `ext-pcntl`). Use `queue:work` instead.

7. Start the app:

```bash
php artisan serve
```

Visit `/admin` and log in. Create a Project, add a Brand Kit, upload a Theme ZIP (Theme Revisions), then run "Full Run" or "Test Run" from the project.

## Project flow

1. **Projects** – Create a project and attach a Brand Kit (colors, typography, tone, etc.).
2. **Theme Revisions** – Upload a Shopify theme ZIP. The system extracts it, computes a signature, and either loads a cached catalog from `.zyg/` or scans sections and writes the catalog.
3. **Model Configs & Prompt Bindings** – Per project, set the OpenRouter API key (Project Secrets) and which model to use for each prompt (Prompt Bindings).
4. **Agent Run** – From a project, start "Full Run" (all sections) or "Test Run" (one section). The pipeline: Summarize → Plan → Compose → Media Plan → Generate Media → Export. Progress and AI logs appear on the run view page (polling every 3s).
5. **Export** – Download the generated theme ZIP from the run’s export.

## Config

- `config/theme.php` – ZIP max size, allowed extensions, `.zyg` filenames, signature globs.
- `config/services.php` – `openrouter.base_url` (no API key in config; keys are stored encrypted per project in `project_secrets`).

## Tests

```bash
php artisan test
```

Minimum tests: zip-slip prevention, schema extraction, signature/catalog reuse, index JSON validation, AI progress logger.

## Documentation

- All non-trivial functions have English docblocks (inputs, outputs, side effects).
- No Hebrew in code or comments.
