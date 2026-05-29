# app/Events/ (+ Listeners) — broadcast events

## Purpose
Scaffolding for real-time presence/typing over WebSockets. Events implement `ShouldBroadcast`; listeners in `app/Listeners/` flip a cache flag. ⚠️ The whole mechanism is currently **inert** — `BROADCAST_CONNECTION` defaults to `log` and no frontend client subscribes.

## Key files
- `UserOnlineStatus.php` — `ShouldBroadcast` event for presence changes.
- `UserTyping.php` — `ShouldBroadcast` event for typing indicators.
- `../Listeners/MarkUserOnline.php` — sets an online cache flag. ⚠️ References `Cache`/`UserOnlineStatus` **without `use` imports** → would fatal if invoked.
- `../Listeners/MarkUserOffline.php` — clears the online cache flag.

## Data flow
(Intended) controller `event(new UserOnlineStatus(...))` → broadcast driver → channel `App.Models.User.{id}` (`routes/channels.php`) → client. (Actual) driver = `log`, so events are only written to the log; listeners would update the cache if dispatched. The frontend instead **polls** `GET /user-status/{id}` and `GET /user-typing/{id}`.

## Dependencies
- **Depends on:** `config/broadcasting.php`, `config/reverb.php`, `routes/channels.php`, the cache store.
- **Depended on by:** nothing live — no controller currently dispatches these events; no client subscribes.

## Conventions
- To actually enable real-time: set `BROADCAST_CONNECTION=reverb`, run Reverb, add a laravel-echo/pusher-js client in `croose_frontend`, and fix the missing imports in `MarkUserOnline`.
- See `docs/ARCHITECTURE.md` §11 (real-time / event flows).

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan reverb:start   # only if BROADCAST_CONNECTION=reverb
```
