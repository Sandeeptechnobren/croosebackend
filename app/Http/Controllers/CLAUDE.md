# app/Http/Controllers/ — HTTP request handlers

## Purpose
Every API endpoint resolves to a controller method here. Controllers validate input, enforce tenant ownership (`client_id`), call Eloquent models / `app/Services`, and return JSON. There is no shared base behavior — `Controller.php` is an empty abstract stub.

## Key files
- `ServicesController.php` / `ProductsController.php` — the cleanest reference implementations (CRUD, validation, transactions, bulk upload via PhpSpreadsheet).
- `SpaceController.php` — Spaces (storefronts) + Space-IQ prompts; central to the product.
- `TransactionController.php` / `PayStackController.php` / `Stripe.php` — payment flows (Stripe + Paystack).
- `WhapiController.php` — WhatsApp instance create / QR / activation.
- `ChatController.php` — Google Gemini AI replies (`gemini-2.5-flash`).
- `BroadcastController.php` — broadcast messaging (uses `Http/Requests` + `Http/Resources`).
- `API/AuthController.php` — register/login/OTP/password-reset, issues Sanctum tokens.
- `Admin/AdminController.php` — admin dashboard endpoints (see `routes/admin.php`).

## Data flow
Route (`routes/api.php`) → controller method → `$request->validate([...])` → `$request->user()->id` for tenant scope → `Models/*` / `Services/*` → `response()->json(['success'=>..., 'message'=>..., ...], $status)`.

## Dependencies
- **Depends on:** `app/Models`, `app/Services`, `app/Jobs`, `app/Http/Requests`, `app/Http/Resources`, Sanctum (`$request->user()`).
- **Depended on by:** `routes/api.php`, `routes/admin.php`, `routes/web.php`.

## Conventions
- Subfolders carry sub-namespaces: `API/`, `Admin/`, `OrdiioApiController/`.
- Validation is mostly inline; Form Requests exist only for Broadcast/YouTube.
- Response/error pattern: see `docs/PATTERNS.md` §3 and §10.
- ⚠️ Non-PSR names exist (`paymentController.php`, `Stripe.php`, `Ordiio_settings_controller.php`); the entire `OrdiioApiController/` set + several Subscription/Ordiio controllers have **no live routes**.

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan route:list   # see which methods are wired
```
