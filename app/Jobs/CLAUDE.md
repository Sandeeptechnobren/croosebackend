# app/Jobs/ — queued background jobs

## Purpose
Asynchronous work pushed onto the `database` queue. Jobs implement `ShouldQueue`, capture a lightweight reference in the constructor, and do their work (usually an outbound API call) in `handle()`, logging outcomes rather than returning a response.

## Key files
- `VerifyPaystackPayment.php` — the functional reference job. Takes a payment `$reference`, calls `https://api.paystack.co/transaction/verify/{ref}` with `PAYSTACK_SECRET_KEY`, then updates the matching `Order.payment_status` to `paid`/`failed` and logs.
- ⚠️ `SendWhatsAppTextJob.php` — **empty stub** (`handle()` returns void); do not use as a template.

## Data flow
Controller calls `VerifyPaystackPayment::dispatch($reference)` → row written to `jobs` table → `queue:listen` worker picks it up → `handle()` re-fetches the `Order` model and updates it → `\Log` records the result. Failures land in `failed_jobs`.

## Dependencies
- **Depends on:** `app/Models/Order`, the `Http` facade, env `PAYSTACK_SECRET_KEY`, `config/queue.php` (driver `database`).
- **Depended on by:** payment controllers that dispatch verification (e.g. `PayStackController`).

## Conventions
- Standard traits: `Dispatchable, InteractsWithQueue, Queueable, SerializesModels`.
- Pass an id/reference, never assume a full model is serialized; re-query in `handle()`.
- Log each branch with `\Log::info/warning/error`; `return;` early on missing data.
- See `docs/PATTERNS.md` §8.

## Common commands
A worker must be running for jobs to execute:
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan queue:listen --tries=1
C:\php83\php.exe -c "C:\php83\php.ini" artisan queue:failed   # inspect failures
```
