# app/Models/ — Eloquent models

## Purpose
The data layer: ~50 Eloquent models mapping to DB tables. Each declares `$fillable` + `$casts`, defines relationships, and (for entities with a public id) auto-generates a `uuid` in a `booted()` creating hook. This is the only data-access layer — no raw SQL.

## Key files
- `Client.php` — the **authenticated principal** (Sanctum `HasApiTokens`, `SoftDeletes`); the business owner / tenant.
- `Space.php` — a storefront (belongsTo Client); products/services/orders hang off it.
- `Product.php` / `Service.php` — catalog items (belongsTo Space). `Service.php` is the cleanest model template.
- `Customer.php` — end customers; belongsToMany Client via `client_customer`.
- `Order.php` / `Appointment.php` / `Transaction.php` — commerce + payments.
- `Conversation.php` — WhatsApp chat history (session_id, context_data json).
- `Subscription.php` + `subscription_items.php` (morphTo) + `customer_subscriptions.php`.
- `space_iq.php` / `SpaceIqDocs.php` — AI prompt + knowledge docs per space.

## Data flow
Controllers/services call `Model::where('client_id', $id)->...` for reads and `Model::create([...])` for writes. JSON columns are cast to arrays; the model is then serialized into the JSON response.

## Dependencies
- **Depends on:** `database/migrations` (table schema must match `$fillable`), Sanctum (`Client`).
- **Depended on by:** `app/Http/Controllers`, `app/Services`, `app/Jobs`, `database/seeders`.

## Conventions
- See `docs/PATTERNS.md` §2 for the canonical model shape.
- ⚠️ Mixed casing: PascalCase (`Service`) and snake_case (`space_iq`, `subscription_items`) coexist.
- ⚠️ **Broken/duplicated models** (Ordiio): merge-conflict markers and one syntax error (`Licensed_track.php`) will fatal on autoload; duplicate pairs exist (`OrdiioTransaction` vs `Ordiio_transaction`). See CODEBASE_AUDIT §4 before touching Ordiio models.
- ⚠️ Some model `$fillable` columns don't exist in the migration (Appointment, Space).

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan tinker   # interactively query models
```
