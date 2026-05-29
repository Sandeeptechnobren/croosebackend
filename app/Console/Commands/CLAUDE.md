# app/Console/Commands/ — Artisan commands

## Purpose
Custom CLI commands run via `php artisan`. Currently holds a single command that drives scheduled WhatsApp broadcast delivery.

## Key files
- `RunBroadcastCron.php` — signature `broadcast:run`. Finds due `BroadcastHeader` rows (by `scheduled_at`/`frequency`), resolves their target customer segments, and sends WhatsApp messages through `MessageService` / WHAPI.

## Data flow
External cron (or manual run) → `artisan broadcast:run` → query due broadcasts via `BroadcastHeader` / `TargetMessage` → `MessageService` sends to each customer → message/status persisted.

## Dependencies
- **Depends on:** `app/Models/BroadcastHeader`, `app/Models/TargetMessage`, `app/Services/MessageService`, `app/Helpers/TargetCustomers`, env `WHAPI_*`.
- **Depended on by:** an external scheduler (must be configured outside the repo).

## Conventions
- ⚠️ **Not auto-scheduled.** In Laravel 11/12 the schedule belongs in `routes/console.php` (or `bootstrap/app.php`), but the only `broadcast:run` schedule lives in the **ignored** legacy `app/Http/Kernel.php`. To run on a cadence, register it in `routes/console.php` or add an OS cron entry.
- See `docs/ARCHITECTURE.md` §7 (background jobs / queues).

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan broadcast:run     # run the broadcast dispatcher
C:\php83\php.exe -c "C:\php83\php.ini" artisan list              # all available commands
```
