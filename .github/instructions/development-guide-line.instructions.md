---
applyTo: '**'
---

# AM Trading PLC – Development Guidelines for AI Assistants

This document provides context and opinionated guidelines for generating code, answering questions, or reviewing changes for this repository.

## Project context

- Framework: Laravel 12 (PHP 8.2)
- Frontend tooling: Vite 6, Laravel Vite plugin, Node 18
- Realtime/SPA: Livewire v3
- Auth/roles: spatie/laravel-permission
- Excel import/export: maatwebsite/excel
- Database: MySQL 8
- Timezone: Africa/Addis_Ababa
- Key app domains: Inventory, sales, purchases, credits/payments, transfers, warehouses, categories, users/roles
- Notable app code locations:
  - Models: `app/Models/*` (Item, Stock, Sale, Purchase, Transfer, etc.)
  - Livewire: `app/Livewire/**`
  - Policies: `app/Policies/**` (+ spatie permissions)
  - Observers/Auditing: `app/Observers/AuditObserver.php` (+ providers)
  - Console commands: `app/Console/Commands/**`
  - Services: `app/Services/**` (e.g., StockMovementService, TransferService)
  - Docs: `docs/CLOSING_PRICE_LOGIC.md`, `docs/NOTIFICATION_STANDARDS.md`, `docs/DEVELOPMENT_RULES.md`

## Run and develop (Docker-first)

- Use Docker Compose v2 syntax: prefer `docker compose` (not `docker-compose`).
- Services (see `docker-compose.yml`):
  - `php`: Laravel app served via `php artisan serve` on 8000
  - `node`: front-end tooling (install deps, run Vite). Dev exposes 5173
  - `db`: MySQL 8 with named volume persistence
- Ports:
  - App: host 8000 → container 8000
  - DB: host 3306 → container 3306
  - Vite dev (optional): host 5173 → container 5173
- Volumes: project mounted into containers to enable hot reload of PHP/Livewire and Vite assets

### Common commands

- Start (build + up):
  - `docker compose up -d --build`
- Logs:
  - `docker compose logs -f php`
  - `docker compose logs -f node`
  - `docker compose logs -f db`
- Exec:
  - `docker compose exec php php artisan <command>`
  - `docker compose exec php composer <command>`
  - `docker compose exec node npm <command>`
- Database:
  - `docker compose exec php php artisan migrate` (use `--seed` to seed)
  - `docker compose exec php php artisan tinker`

### .env (Docker defaults)

When generating or updating environment guidance, prefer these defaults:

```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Africa/Addis_Ababa

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=amtradingplc
DB_USERNAME=root
DB_PASSWORD=root
```

## Coding standards

- Follow PSR-12. Prefer Laravel Pint defaults. Keep existing file style; avoid unrelated reformatting.
- PHP 8.2: use typed properties/return types, readonly where sensible, enums when appropriate.
- Controllers: keep thin. Move domain logic to Services (`app/Services/**`) or model methods.
- Validation: prefer Form Requests. For Livewire, use typed rules and `validate()`.
- Eloquent:
  - Define relationships explicitly with return types.
  - Guard against N+1 — eager-load where needed.
  - Use casts for dates, enums, and JSON.
- Livewire v3:
  - Prefer small, focused components.
  - Use events and `wire:navigate` thoughtfully; keep state predictable.
- Background work: prefer queues for heavy tasks; keep commands idempotent.
- Naming:
  - DB tables: plural snake_case (default Laravel conventions).
  - Foreign keys: `<model>_id` via `foreignId()`/`foreignIdFor()`.

## Security & access control

- Authorization: use Policies and spatie/laravel-permission. Do not bypass checks in controllers/components.
- Validation: never trust request input; always validate. Use `bail` and sensible rules.
- Mass assignment: define `$fillable` or guarded patterns; avoid `->create($request->all())` without filtering.
- Secrets: never commit secrets; rely on `.env` and container env vars.

## Data & migrations

- Every schema change requires a migration; write reversible migrations.
- Seeders should be safe to run multiple times (idempotent where possible).
- Long-running data fixes: implement as Artisan commands under `app/Console/Commands` and log progress.

## Observability & auditing

- Use `app/Observers/AuditObserver.php` and configured providers for create/update/delete traces.
- Logging: leverage Laravel logging; for local dev, `laravel/pail` can tail logs.

## Performance

- Avoid N+1 queries; profile with telescope (if enabled) or logging.
- Use database indexes where needed (migrations).
- Batch large operations with chunks/queues.

## Frontend (Vite)

- Dev: `node` service runs `npm run dev` (Vite on 5173). PHP consumes built or dev-served assets via the Vite plugin.
- Build: when producing static assets, run in the node container: `npm ci && npm run build`.
- SCSS: ensure `sass` is present in `devDependencies` when using `.scss`.

## Reviews & changes (for AI)

- Do:
  - Use Docker-based commands (no host PHP/Node assumptions).
  - Keep changes minimal and localized; respect existing public APIs and style.
  - Update or add tests for behavior changes (PHPUnit 11).
  - Update docs `README.md` and/or this instruction file when workflows change.
- Don’t:
  - Introduce breaking schema changes without migrations and clear upgrade notes.
  - Commit secrets or alter production-critical env defaults without discussion.
  - Reformat entire files unrelated to the change.

## Acceptance checklist (PRs/changes)

- Build: containers up via `docker compose up -d --build`.
- App reachable at http://localhost:8000; DB connects to `db:3306`.
- Lint/format: Pint clean (or no new style deviations).
- Tests: run `docker compose exec php php artisan test` and ensure green.
- Data: migrations applied; seeders run if required.
- Docs: README updated when commands or ports change.

## Troubleshooting

- Node container unhealthy or failing build:
  - Ensure `npm ci` can install; if using SCSS, verify `sass` devDependency.
  - For dev HMR, ensure port 5173 is exposed and `--host 0.0.0.0` is used.
- PHP not serving:
  - Check logs: `docker compose logs -f php`
  - Ensure `.env` DB settings point to `db` host.
- Port conflicts (8000/3306/5173):
  - Stop other services on the host or change host-side ports in compose.
- Database persistence:
  - Data lives in named volume `db_data`. Dropping containers won’t delete data. Remove the volume to reset DB.

## References in repo

- `docs/CLOSING_PRICE_LOGIC.md` — follow business logic for price/closing calculations.
- `docs/NOTIFICATION_STANDARDS.md` — consistent user/system notifications.
- `docs/DEVELOPMENT_RULES.md` — additional in-repo standards to observe.