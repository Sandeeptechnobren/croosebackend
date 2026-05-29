# app/ — Laravel application code

## Purpose
Root of the Croose backend application code, autoloaded under the PSR-4 namespace `App\` (`composer.json` maps `App\` → `app/`). Holds every domain class: HTTP handling, Eloquent models, integration services, queue jobs, events, mail, and console commands for the Laravel 12 REST API.

## Key files / subdirectories
- `Http/Controllers/` — request handlers (35+); the bulk of the app. See its CLAUDE.md.
- `Models/` — ~50 Eloquent models (CRM, Space-IQ, subscriptions, DelayDog, Ordiio).
- `Services/` — third-party + business logic (WHAPI, Stripe, Google Calendar, SourceAudio).
- `Jobs/` — database-queued jobs (`VerifyPaystackPayment`).
- `Events/` + `Listeners/` — presence/typing broadcast events (scaffolded, inert).
- `Http/Middleware/` — `AdminMiddleware`, `ApiCacheMiddleware`, `UpdateOnlineStatus`.
- `Console/Commands/` — `RunBroadcastCron` (broadcast dispatch).
- `Mail/` — `SendOtpMail`, `ResetPasswordMail`. `Helpers/`, `DTOs/`, `Traits/` — small utilities (`Traits/ApiResponse` is unused).
- `Providers/` — `AppServiceProvider` (no-op), `RouteServiceProvider`.

## Data flow
HTTP request → `routes/api.php` (+`admin.php`) → a `Http/Controllers/*` method → validation → `Models/*` (Eloquent) and/or `Services/*` → `response()->json([...])`. Async work is pushed to `Jobs/*` on the `database` queue.

## Dependencies
- **Depends on:** `config/` (settings), `routes/` (entry wiring), `database/` (schema), `vendor/` (Laravel framework, Sanctum, Stripe SDK, google/apiclient).
- **Depended on by:** `routes/*` reference these controllers; `croose_frontend` consumes the resulting API.

## Conventions
Type-based folders (group by kind, not feature). Full project conventions live in `docs/PATTERNS.md` and `docs/ARCHITECTURE.md` at the repo root. ⚠️ `app1/`, `app2/`, `app.zip` are dead backup copies of this directory — never edit them.

## Common commands
Use the PHP 8.3 binary (the project requires 8.3):
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan route:list
C:\php83\php.exe -c "C:\php83\php.ini" artisan tinker
```
