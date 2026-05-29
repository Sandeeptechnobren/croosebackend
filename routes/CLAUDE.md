# routes/ — route registration

## Purpose
Maps URLs to controller methods. `bootstrap/app.php` loads `web.php` for the `web` group and **both** `api.php` and `admin.php` for the `api` group (default `api/` prefix). Auth is applied per-block, not globally.

## Key files
- `api.php` (~228 lines) — the main REST surface. A top section is public (`/register`, `/login`); a `Route::middleware('auth:sanctum')->group(...)` block holds all owner-facing endpoints; **after** that block a large set of payment / phone-keyed storefront / DelayDog / complaint routes sits **outside** auth (i.e. public).
- `admin.php` — `prefix('admin')` + `auth:sanctum` + no-op `admin` middleware → `Admin/AdminController`.
- `web.php` — Blade payment landing pages. ⚠️ Imports `OrdiioPaymentsController` / `OrdiioLicenseController` that **do not exist** → those routes throw; breaks `route:list`.
- `channels.php` — one private channel `App.Models.User.{id}`.
- `console.php` — only `inspire` (no broadcast schedule — see Console/Commands).
- `Ordiio/` — stray empty route dir (the Ordiio module has no live HTTP routes).

## Data flow
Incoming request → `bootstrap/app.php` route loading → match in `api.php`/`admin.php`/`web.php` → `auth:sanctum` if inside an auth block → controller.

## Dependencies
- **Depends on:** `app/Http/Controllers/*` (imported at file top), `app/Http/Middleware` (the `admin` alias).
- **Depended on by:** the whole API; `croose_frontend/app/Apis/publicapi.tsx` targets these paths.

## Conventions
- One `Route::verb('/path', [Ctrl::class, 'method'])` per line, grouped by resource with `//` comments. See `docs/PATTERNS.md` §1.
- ⚠️ **Duplicate routes silently override** earlier ones (`/stripe/webhook` ×3, `/stripe/instance/{uuid}` ×2, `PUT /products/{id}`). Admin routes are also registered twice (here + `RouteServiceProvider`).

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan route:list
```
