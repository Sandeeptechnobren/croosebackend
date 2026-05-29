# database/ — migrations, seeders, factories

## Purpose
Defines and populates the schema. ~55 migrations build the SQLite (default) schema plus framework tables (queue/cache/session). Seeders insert demo/reference data; only one factory exists.

## Key files
- `migrations/0001_01_01_000000_create_users_table.php` + `..._cache_table` + `..._jobs_table` — framework base tables.
- `migrations/2025_06_30_065151_clients.php` — the tenant/auth table (`Client`).
- `migrations/2025_07_07_064256_create_spaces_table.php` — storefronts (note legacy `class CreateSpacesTable`, kept because its name matches the studly filename).
- `migrations/2025_07_12_061606_space_iq.php` + `..._space_iq_docs.php` — converted to anonymous-class format during local setup.
- `seeders/DatabaseSeeder.php` → `DemoDataSeeder.php` — ⚠️ inserts 10 orders referencing client/customer/product IDs 1–10 it does **not** create → FK failure on an empty DB.
- `seeders/OrdiioLicenseCategorySeeder.php` — exists but is **not** called by `DatabaseSeeder`.
- `factories/UserFactory.php` — the only factory.

## Data flow
`artisan migrate` applies `migrations/*` in filename order → tables created. `artisan db:seed` runs `DatabaseSeeder`. Models in `app/Models` read/write these tables.

## Dependencies
- **Depends on:** `config/database.php`, the `.env` `DB_CONNECTION` (sqlite → `database/database.sqlite`).
- **Depended on by:** `app/Models/*` (column names must match `$fillable`), tests (in-memory sqlite).

## Conventions
- New migrations use the anonymous-class form `return new class extends Migration`.
- ⚠️ Duplicate Ordiio migrations (`2025_08_23_*` vs `2025_11_15_*`) recreate the same tables; the Nov ones were guarded with `Schema::hasTable()` to let `migrate` finish. Don't add more duplicates.

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan migrate
C:\php83\php.exe -c "C:\php83\php.ini" artisan migrate:status
C:\php83\php.exe -c "C:\php83\php.ini" artisan migrate:fresh   # drops & rebuilds (DemoDataSeeder will FK-fail)
```
