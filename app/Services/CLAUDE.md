# app/Services/ — integration & business logic

## Purpose
The main code-reuse layer. Each service wraps one third-party integration or a cross-controller workflow so controllers stay thin. Services are plain classes instantiated by controllers/jobs (no interface bindings).

## Key files
- `MessageService.php` — WHAPI (WhatsApp) send/list messages via `gate.whapi.cloud`.
- `BroadcastService.php` — broadcast CRUD + audit stamping (used by `BroadcastController`).
- `GoogleCalendarService.php` — appointment event creation via `google/apiclient`.
- `StripeWebhookHandler.php` — Stripe webhook signature handling + event dispatch.
- `SourceAudioService.php` / `SonicSearchService.php` — Ordiio music-data lookups (SourceAudio API).
- `YouTubeAllowlistService.php` — YouTube allowlist logic.
- `Ordiio/SourceAudioApiService.php`, `Ordiio/OrdiioAdminService.php` — Ordiio sub-product services.
- ⚠️ `UserService.php` — **empty stub**, not a template.

## Data flow
Controller/Job constructs the service → service makes an outbound `Http::` call (WHAPI, SourceAudio, Stripe, Google) or runs DB logic → returns data/DTO back to the caller, which shapes the JSON response.

## Dependencies
- **Depends on:** `app/Models`, `app/DTOs` (`CuratedDTO`, `TrackDTO`), `app/Helpers` (`TrackHelper`), config/env keys (`WHAPI_*`, `SOURCEAUDIO_API_KEY`, `STRIPE_*`, `GOOGLE_*`).
- **Depended on by:** `app/Http/Controllers/*`, `app/Console/Commands/RunBroadcastCron`, `app/Jobs`.

## Conventions
- Outbound HTTP uses the `Http` facade; secrets are read via `env()`/`config()` (see `docs/PATTERNS.md` §5).
- ⚠️ Several files (`SourceAudioService`, `YouTubeAllowlistService`, `Ordiio/SourceAudioApiService`) contain **unresolved Git merge-conflict markers** → will fatal if loaded. Resolve before use (CODEBASE_AUDIT §4).
- ⚠️ `config('services.whapi.token')` is referenced but the key is undefined in `config/services.php`.

## Common commands
No service-specific commands; exercised through controller endpoints and `queue:listen`.
