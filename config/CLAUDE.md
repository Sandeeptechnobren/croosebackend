# config/ — framework & service configuration

## Purpose
15 Laravel config files. Each returns an array and reads `env()` values (with defaults). This is the **only** place `env()` should be called; app code should read `config('...')`.

## Key files
- `auth.php` — ⚠️ critical: default guard `api` (driver `sanctum`); default provider `clients` → `App\Models\Client` (the principal is **Client**, not User). Second provider `ordiio_users` → `OrdiioUser`; password-reset brokers for both.
- `services.php` — third-party credentials: `stripe` (key/secret/ordiio_secret_key), `postmark`, `resend`, `ses`, `slack`. ⚠️ `services.stripe.webhook_secret` and a `whapi` block are referenced in code but **missing** here → resolve to null.
- `database.php` — connections; default `sqlite`. `queue.php`, `cache.php`, `session.php` — all default to the `database` driver.
- `sanctum.php` — token/guard settings. `cors.php` — CORS (open by default).
- `broadcasting.php` + `reverb.php` — WebSocket config (driver defaults to `log`; inert).
- `octane.php` — RoadRunner config (configured, unused). `mail.php` — default mailer `log`.

## Data flow
`.env` → `env()` in these files at boot (or at `config:cache` time) → `config('key.path')` everywhere in `app/`.

## Dependencies
- **Depends on:** `.env` (copy from `.env.example`).
- **Depended on by:** the entire framework + `app/` (especially `auth.php` for Sanctum, `services.php` for integrations).

## Conventions
- Add every new third-party key here (under `services.php`) and read via `config()`. See `docs/PATTERNS.md` §5.
- ⚠️ Many required keys are absent from `.env.example` (Stripe/Paystack/WHAPI/Gemini/Google/SourceAudio) — add them when wiring an integration.

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan config:clear   # after editing config or .env
C:\php83\php.exe -c "C:\php83\php.ini" artisan config:cache   # ⚠️ breaks any direct env() calls in app code
```
