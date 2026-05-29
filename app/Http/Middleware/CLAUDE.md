# app/Http/Middleware/ — HTTP middleware

## Purpose
Per-request filters. In Laravel 12 the active middleware stack is configured in `bootstrap/app.php`, not a Kernel — only the classes wired there actually run. This folder holds three custom middleware, but only `AdminMiddleware` is registered (as the `admin` alias).

## Key files
- `AdminMiddleware.php` — aliased `admin`, applied to `routes/admin.php`. ⚠️ Its `handle()` is a **no-op** (`return $next($request)`) — it performs **no** admin authorization. Any authenticated Sanctum user reaches admin routes. Add a real role check here if implementing admin gating.
- `ApiCacheMiddleware.php` — would cache GET 200/201 responses for 10 min. ⚠️ **Not wired** into the live stack (only referenced by the ignored legacy `Http/Kernel.php`).
- `UpdateOnlineStatus.php` — would broadcast presence per request. ⚠️ **Not wired** (legacy Kernel only).

## Data flow
Request → global framework middleware (CORS via `HandleCors`) → `auth:sanctum` (per route group) → `admin` alias (admin routes only) → controller. The two unwired middleware never execute.

## Dependencies
- **Depends on:** `bootstrap/app.php` for registration; `auth:sanctum` runs before `admin`.
- **Depended on by:** `routes/admin.php` (via the `admin` alias), `app/Providers/RouteServiceProvider.php` (which also registers admin routes — duplicate).

## Conventions
- Register new middleware/aliases in `bootstrap/app.php` (the `->withMiddleware(...)` closure), not in `Http/Kernel.php` (ignored by L11/12).
- See `docs/ARCHITECTURE.md` §6 for the auth/authz picture.

## Common commands
None module-specific.
