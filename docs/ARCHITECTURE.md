# Croose Architecture

> Factual architecture reference derived from the source of `croosebackend` (Laravel 12 API) and `croose_frontend` (Next.js 15 dashboard). Only documents what exists in the code. Where the code is broken, inconsistent, or absent, that is stated rather than smoothed over. Companion to [CODEBASE_AUDIT.md](CODEBASE_AUDIT.md).

---

## Table of contents
1. [System overview](#1-system-overview)
2. [High-level architecture](#2-high-level-architecture)
3. [Directory map](#3-directory-map)
4. [Database schema](#4-database-schema)
5. [API surface](#5-api-surface)
6. [Authentication & authorization](#6-authentication--authorization)
7. [Background jobs / queues](#7-background-jobs--queues)
8. [Third-party integrations](#8-third-party-integrations)
9. [Deployment architecture](#9-deployment-architecture)
10. [Key environment variables](#10-key-environment-variables)
11. [Real-time / event flows](#11-real-time--event-flows)
12. [Server access](#12-server-access)

---

## 1. System overview

Croose is a multi-tenant WhatsApp commerce/CRM platform. A business owner ("Client") registers, creates one or more "Spaces" (each a storefront with its own WhatsApp channel, products, services, and an AI chatbot persona), and manages customers, orders, appointments, subscriptions, and broadcast messaging from a dashboard. End customers interact through WhatsApp (via the WHAPI gateway) and through phone-number-keyed public API endpoints that drive ordering and booking; an AI assistant (Google Gemini) powers chat replies. The repository also contains two partially-built, currently route-less or partially-broken sub-products: **Ordiio** (music licensing, backed by the SourceAudio API) and **DelayDog** (UK rail delay-repay claims). The backend is a Laravel 12 REST API using Sanctum bearer tokens; the frontend is a Next.js 15 single-page dashboard that talks to that API over axios.

---

## 2. High-level architecture

Major components that actually exist in the code:

- **Next.js dashboard** (`croose_frontend`) — browser SPA; all API calls funnel through `app/Apis/publicapi.tsx`; auth token kept in `localStorage`.
- **Laravel API** (`croosebackend`) — REST endpoints under `api/`, served via a root `index.php` + `.htaccess` (Apache).
- **Relational DB** — SQLite by default; MySQL/MariaDB/Postgres/SQL Server connections also configured. Also backs the **queue**, **cache**, and **session** stores (all `database` driver).
- **WHAPI** (`gate.whapi.cloud`) — outbound/inbound WhatsApp messaging per Space.
- **Google Gemini** — AI chat responses (`gemini-2.5-flash`).
- **Payment providers** — Stripe (direct SDK) and Paystack (raw HTTP) for orders, appointments, WhatsApp-instance activation, and subscriptions.
- **Google Calendar** — appointment event creation.
- **SourceAudio / SonicSearch / Airtable** — Ordiio music-licensing data (sub-product).

```
                         ┌─────────────────────────────┐
                         │  Browser (business owner)   │
                         │  Next.js 15 SPA dashboard   │
                         │  croose_frontend            │
                         │  app/Apis/publicapi.tsx     │
                         └──────────────┬──────────────┘
                                        │ HTTPS + Bearer token (localStorage)
                                        │ NEXT_PUBLIC_API_BASE_URL
                                        ▼
   WhatsApp end-user           ┌─────────────────────────────────────────┐
        │                      │  Laravel 12 API (croosebackend)          │
        │ WHAPI webhook /      │  Apache → root index.php → bootstrap/    │
        │ phone-keyed public   │  app.php → routes/api.php (+admin.php)   │
        │ endpoints            │  → Controllers → Eloquent → JSON         │
        ▼                      │                                          │
  ┌──────────────┐  HTTP       │  auth: Sanctum (guard=api, provider=     │
  │   WHAPI      │◀───────────▶│        clients) ; admin mw = no-op       │
  │ gate.whapi   │             └───┬───────┬───────┬───────┬───────┬──────┘
  │   .cloud     │                 │       │       │       │       │
  └──────────────┘                 ▼       ▼       ▼       ▼       ▼
                              ┌────────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────────────┐
                              │  DB    │ │Stripe│ │Pay-  │ │Google│ │ Google       │
                              │ sqlite │ │ SDK  │ │stack │ │Gemini│ │ Calendar     │
                              │(+queue,│ └──────┘ │ HTTP │ │ HTTP │ │ apiclient    │
                              │ cache, │          └──────┘ └──────┘ └──────────────┘
                              │session)│        ┌──────────────────────────────────┐
                              └────┬───┘        │ SourceAudio / SonicSearch /       │
                                   │            │ Airtable  (Ordiio sub-product)    │
                                   ▼            └──────────────────────────────────┘
                          ┌─────────────────┐
                          │ queue worker    │  database queue
                          │ (queue:listen)  │  jobs: VerifyPaystackPayment
                          └─────────────────┘  RunBroadcastCron (manual cmd)
```

Notes grounded in the code:
- There is **no** message broker, Redis, or container orchestration wired in (Redis is configured but `*_CONNECTION`/`*_STORE` default to `database`/`log`).
- Reverb/Pusher (WebSockets) is configured but `BROADCAST_CONNECTION` defaults to `log`; no client subscribes (see §11).
- The frontend bypasses `NEXT_PUBLIC_API_BASE_URL` for several flows, hardcoding `https://api.joincroose.com` and one raw IP (see §5/§12).

---

## 3. Directory map

### Backend `croosebackend`
| Folder / file | Purpose |
|---|---|
| `app/Http/Controllers/` | Request handlers (35+); subfolders `Admin/`, `API/`, `OrdiioApiController/`. |
| `app/Http/Middleware/` | `AdminMiddleware` (no-op), `ApiCacheMiddleware`, `UpdateOnlineStatus` (last two not wired into the live stack). |
| `app/Http/Requests/` | Form-request validators (Broadcast store/update, YouTubeAllowlist). |
| `app/Http/Resources/` | API resource transformers (only `BroadcastResource`). |
| `app/Models/` | ~50 Eloquent models (CRM, Space-IQ/Whapi, subscriptions, DelayDog, Ordiio). |
| `app/Services/` | Integration/business services (Message/WHAPI, Broadcast, GoogleCalendar, StripeWebhookHandler, SourceAudio, SonicSearch, YouTubeAllowlist, `Ordiio/`). |
| `app/Jobs/` | Queued jobs (`VerifyPaystackPayment`; `SendWhatsAppTextJob` is an empty stub). |
| `app/Events/`, `app/Listeners/` | Presence events (`UserOnlineStatus`, `UserTyping`) + listeners. |
| `app/Mail/` | Mailables (`SendOtpMail`, `ResetPasswordMail`). |
| `app/Console/Commands/` | `RunBroadcastCron`. (`Console/Kernel.php` is legacy/ignored.) |
| `app/Providers/` | `AppServiceProvider` (no-op), `RouteServiceProvider`. |
| `app/DTOs/`, `app/Helpers/`, `app/Traits/` | `CuratedDTO`/`TrackDTO`; `TrackHelper`/`TargetCustomers`; `ApiResponse` (unused). |
| `bootstrap/` | `app.php` (routing + middleware config), `providers.php`, `cache/`. |
| `config/` | 15 config files (app, auth, broadcasting, cache, cors, database, filesystems, logging, mail, octane, queue, reverb, sanctum, services, session). |
| `database/migrations/` | ~55 schema migrations. |
| `database/seeders/` | `DatabaseSeeder` → `DemoDataSeeder`; `OrdiioLicenseCategorySeeder`. |
| `database/factories/` | `UserFactory` only. |
| `public/` | Apache docroot assets (`index.php`, favicon, logo, robots.txt). |
| `resources/views/` | Blade views: `billing`, `emails`, `payment`, `payments`, `payments_ordiio`. |
| `resources/css/`, `resources/js/` | Vite-built assets for Blade. |
| `routes/` | `api.php`, `admin.php`, `web.php`, `console.php`, `channels.php` (+ stray empty `Ordiio/Ordiio_api`). |
| `storage/`, `tests/` | Runtime files; PHPUnit tests (2 stubs). |
| `index.php`, `.htaccess` (root) | Non-standard front controller + Apache rewrite (repo root = webroot). |
| ⚠️ `app1/`, `app2/`, `app.zip` | Dead backup snapshots of `app/`. |

### Frontend `croose_frontend`
| Folder / file | Purpose |
|---|---|
| `app/(public)/` | Unauthenticated pages: `login`, `signup`, `emailverification`, `forgotcard`; shared `component/` incl. client-side guard `publiroute.tsx`. |
| `app/(private)/` | Authenticated app: `dashboard/` (home, overview, space, appointment, customers, product, orders, messaging, subscription, payments, liveagent*), `customerspace/`, `customisespace/`, `spacebusiness/`, shared `components/` (~40, incl. `material/`), `Iqcontext.tsx`. |
| `app/Apis/` | `publicapi.tsx` — the single axios API client. |
| `app/context/`, `app/content/` | `SettingContext`; `Content.tsx` (global setting modals). |
| `app/layout.tsx`, `app/page.tsx`, `app/globals.css` | Root layout (providers + react-toastify), home, Tailwind v4 entry. |
| `public/` | ~75 static image/SVG assets. |
| `next.config.ts`, `tsconfig.json`, `postcss.config.mjs`, `.env.local` | Config. No `.env.example`, no ESLint config, no `tailwind.config`. |
| ⚠️ `how`, `how --stat 6da408b`, `Croose V1 MVP - June/` | Stray git-output artifacts + stray asset folder. |

---

## 4. Database schema

Models live in `app/Models/`. Key fields and relationships as defined in the models/migrations. (⚠️ Several model↔migration mismatches and broken Ordiio models exist — see CODEBASE_AUDIT §4.)

### Core CRM
| Model | Table | Key fields | Relationships |
|---|---|---|---|
| `User` | users | name, email, password | (auth model; none defined) |
| `Client` | clients | name, business_name, business_location, phone_number, email, password, security_*, uuid | hasMany Appointment; belongsToMany Customer (pivot `client_customer`) |
| `Space` | spaces | client_id, name, chatbot_name, space_phone, category, currency, image, start/end_time, uuid | belongsTo Client |
| `Product` | products | client_id, space_id, name, price, stock, category, tags(json), uuid | belongsTo Space |
| `Service` | services | client_id, space_id, name, duration_minutes, price, available_days(json), ai_tags(json) | belongsTo Space |
| `Customer` | customers | name, phone, email, whatsapp_number, address, meta, uuid | hasMany Appointment; hasMany Order; belongsToMany Client |
| `Appointment` | appointments | client_id, space_id, customer_id, service_id, amount, status, uuid | belongsTo Client/Customer/Service |
| `Order` | orders | uuid, client_id, space_id, customer_id, product_id, order_quantity, order_amount, payment_*, status | belongsTo Client/Customer/Product/Space |
| `Transaction` | transactions | uuid, client_id, customer_id, type, reference_id, amount, payment_*, stripe_session_id, meta(json) | belongsTo Client/Customer |
| `ClientCustomer` | client_customer | client_id, space_id, customer_id, first_interaction_at | belongsTo Customer (pivot) |
| `Categories` | categories | name, type, client_id, is_active | hasMany Product/Service; belongsTo Client |
| `BusinessCategory` | business_categories | name, template, description, main_products_services, uuid | — |
| `Country` | countries | country_code, country_name, uuid | — |
| `Conversation` | conversations | client_id, space_id, customer_id, whatsapp_number, user_message, bot_response, session_id, intent_detected, context_data(json) | belongsTo Client/Space/Customer |

### Space-IQ / WhatsApp (Whapi)
| Model | Table | Relationships |
|---|---|---|
| `space_iq` | space_iqs | — (prompt content per space) |
| `SpaceIqDocs` | spaces_iq_docs | — (uploaded knowledge docs) |
| `Space_whapichannel_details` | Space_whapichannel_details | belongsTo Space/Client (instance_id, token, server, status) |
| `SpaceWhapiPaymentDetail` | Space_whapi_payment_details | belongsTo Client/Space (activation payments) |
| `WhitelistChannel` | whitelist_channels | belongsTo User |

### Broadcast / messaging
| Model | Table | Relationships |
|---|---|---|
| `BroadcastHeader` | broadcast_headers | belongsTo TargetMessage (target_id, frequency, scheduled_at, content, audit fields) |
| `TargetMessage` | target_messages | dynamic `Customers()` segment query (new/recent/active/all) — not a real relation |
| `FeedBackManagement` | feedback_management | — (complaints/feedback) |

### Subscriptions
| Model | Table | Relationships |
|---|---|---|
| `Subscription` | subscriptions | hasMany subscription_items; belongsTo Space/Service; hasMany customer_subscriptions |
| `subscription_items` | subscription_items | belongsTo Subscription; **morphTo `item`** (product/service) |
| `customer_subscriptions` | customer_subscriptions | belongsTo Subscription/Customer/Service; hasMany subscription_transaction |
| `subscription_transaction` | subscription_transactions | belongsTo customerSubscription |

### DelayDog (rail-delay sub-product)
| Model | Table | Relationships |
|---|---|---|
| `DelayDogUserDetail` | delay_dog_user_details | hasMany DelayDogJourney |
| `DelayDogJourney` | delay_dog_journeys | belongsTo DelayDogUserDetail |
| `DelayDogClaims` | delay_dog_claims | belongsTo DelayDogJourney/DelayDogUserDetail |
| `DelayDogTickets` | delay_dog_tickets | belongsTo DelayDogJourney |

### Ordiio (music-licensing sub-product — partially broken, no live routes)
`OrdiioUser` (Cashier Billable), `OrdiioSubscription`, `OrdiioTransaction`/`Ordiio_transaction`, `OrdiioLicenseCategory`/`ordiio_license_categories`, `OrdiioLicensePurchase`/`ordiio_license_purchases`, `Ordiio_cart`, `Ordiio_favourites`, `Ordiio_playlists`, `ordiio_playlist_tracks`, `OrdioCheckoutSession`, `Licensed_track`. ⚠️ Several contain unresolved merge-conflict markers / syntax errors and duplicate-table migrations.

### Framework/support tables
`personal_access_tokens` (Sanctum), `password_resets`/password-reset brokers (clients, ordiio_users), `otp_codes`, `jobs`/`job_batches`/`failed_jobs`, `cache`, `sessions`.

### Relationship map (core)
```
Client 1─* Space 1─* Product / Service / Order / Appointment / Conversation
Client *─* Customer  (pivot client_customer, +space_id)
Customer 1─* Appointment / Order
Order *─1 Product/Customer/Client/Space
Appointment *─1 Service/Customer/Client
Transaction *─1 Client/Customer
Space 1─* Subscription 1─* subscription_items (morphTo product|service)
Subscription 1─* customer_subscriptions 1─* subscription_transactions
TargetMessage 1─* BroadcastHeader
DelayDogUserDetail 1─* DelayDogJourney 1─* {DelayDogClaims, DelayDogTickets}
```

---

## 5. API surface

All API routes use the default `api/` prefix. `auth:sanctum` is applied per-block, not globally. (Full route-level notes incl. duplicates/malformed URIs are in CODEBASE_AUDIT §5.)

### Auth — public (`AuthController`)
`POST /register` · `POST /login` · `POST /reset-password` · `GET /clients` · `POST /send_otp` · `POST /reset_password` · `POST /ordiio/forgot-password` · `POST /ordiio/reset-password` · `POST /verify-reset-password` · `POST /find_account/{email}`

### Account — auth
`POST /account_profile` · `POST /account/profile/update` · `POST /update_password` · `POST /logout`

### Services — auth
`GET /services` · `POST /services` · `POST /services/show` · `PUT /services/{id}` · `DELETE /services/{id}` · `POST /services/bulkupload` · `GET /getServicesBySpace`

### Products — auth
`GET /products` · `POST /products` · `PUT /products/{id}` · `DELETE /products/{id}` · `POST /products/bulkupload` · `GET /getProductBySpace`

### Customers — auth
`POST /customer/register` · `GET /getCustomer` · `GET /getCustomerByPhone` · `GET /customer_statistics`

### Appointments — auth
`GET /appointments` · `GET /appointment_statistics` · `POST /appointments_status_update` · `DELETE /appointments/{id}`

### Orders — auth
`GET /orders` · `GET /order_statistics` · `POST /orders_status_update` · `POST /createmanualorder`

### Spaces — auth
`POST /create_space` · `GET /space` · `POST /update_space` · `POST /check-user-space` · `POST /checkspaceIQincresed` · `POST /space_iq` · `GET /get_space_list` · `GET /get_space_prompt` · `POST /update_space_prompt` · `GET /space_chat_stats` · `GET /space_chat_list` · `POST /space_activation_charge`

### Categories — auth
`GET /get_categories` · `POST /get_template/{type}`

### Conversations / Chat — auth
`GET /get_conversations` · `GET /total_chats` · `POST /chat`

### WhatsApp (Whapi) automation — auth
`POST /whapi/instance` · `POST /whapi/instancenew` · `GET /whapi/instance/qr` · `GET /whapi/instance_activation_status`

### Subscriptions — auth
`POST /create_subscription` · `GET /subscription_list` · `POST /check_name` · `GET /subscribers_list` · `GET /subscriber_statistics` · `GET /subscriptions` · `PUT /subscriptions/{id}` · `POST /subscriptions/{id}/archive` · `POST /subscriptions/{id}/unarchive` · `DELETE /subscriptions/{id}`

### Broadcast / messaging — auth
`GET /broadcast/list` · `GET /broadcast/show{id}` *(malformed)* · `POST /broadcast/add` · `PUT /broadcast/update/{id}` · `DELETE /broadcast/delete/{id}` · `GET /target/list` · `GET /target/messages/{id}` · `POST /sendBroadcastMessage` · `GET /Message/{phone}` · `POST /sendWhatsapp` · `GET /user-status/{id}` · `POST /typing-start` · `POST /typing-stop`

### Payment (dashboard) — auth
`POST /payment_details`

### ⚠️ Public (NOT behind auth) — phone-keyed storefront/booking
`GET|POST /orders/{client_phone}/{customer_phone}` · `GET|POST /appointments/{space_phone}/{customer_phone}` · `GET /products/{phoneNumber}` · `GET /services/{phoneNumber}` · `GET /available-slots/{space_phone}` · `GET /manual-token-check` · `GET /countries` · `POST /categories` · `GET|POST /business_categories` · `GET /business_categories/{id}` · `POST /store_transaction/{client_phone}/{customer_phone}` · `GET /get_transaction/{client_phone}/{customer_phone}` · `PUT /products/{id}` *(duplicate, public)*

### ⚠️ Public — payments (Stripe & Paystack)
`GET /stripe/order/{uuid}` · `GET /stripe/appointment/{uuid}` · `GET /stripe/instance/{uuid}` *(declared 2×)* · `GET /stripe-checkout/{uuid}/{phone}` · `GET /stripe/success` · `POST /stripe/webhook` *(declared 3×; last wins)* · `GET /paystack/order/{uuid}` · `GET /paystack/appointment/{uuid}` · `GET /paystack/instance/{uuid}` · `GET /paystack/subscription/{uuid}` · `GET /paystack/whapi/{space_uuid}` · `GET /paystack/callback` · `POST /paystack/webhook` · `POST /paystack/webhook_test` · `POST /payment/status` · `POST /subscribe/{uuid}/{phone}`

### ⚠️ Public — DelayDog
`POST /delaydogusers/{user_phone}` · `POST /delaydogjourney/{user_phone}` · `POST /delaydogclaims/{user_phone}/{journey_uuid}` · `POST /delaydogtickets`

### ⚠️ Public — Complaints/Feedback (prefix `complain`)
`GET /complain/list` · `POST /complain/add` · `GET /complain/show/{id}` · `POST /complain/update/{id}` · `DELETE /complain/delete/{id}`

### Admin (`routes/admin.php`, prefix `admin`, `auth:sanctum` + no-op `admin` mw)
`GET /admin/dashboard` · `GET /admin/users` · `POST /admin/user/status/{id}` · `GET /admin/orders` · `POST /admin/orders/status` · `GET /admin/products` · `DELETE /admin/products/{id}` · `GET /admin/services` · `DELETE /admin/services/{id}` · `GET /admin/subscriptions` · `POST /admin/subscription/archive/{id}`

### Web (`routes/web.php`) — Blade payment landing pages
`GET /` · `GET /payment-success` · `GET /payment-cancel` · `GET /payment/{order|appointment|instance|subscription}/{uuid}` · `POST /ordiio/create-checkout` · ordiio success/cancel + license success/cancel · `GET /subscribe/{uuid}/{phone}`. ⚠️ Several reference controllers that don't exist (`OrdiioPaymentsController`, `OrdiioLicenseController`).

---

## 6. Authentication & authorization

- **Mechanism:** Laravel **Sanctum** personal-access tokens (bearer). `config/auth.php` default guard `api` (driver `sanctum`), default provider `clients` → `App\Models\Client` (the business owner is the primary principal, **not** `User`). A second provider `ordiio_users` → `App\Models\OrdiioUser` and two password-reset brokers (`clients`, `ordiio_users`) exist for the Ordiio sub-product.
- **Token issuance:** `AuthController@login` / `@register` issue tokens via `HasApiTokens` on `Client`; tokens persist in the `personal_access_tokens` table.
- **Token storage (client):** frontend stores the token in `localStorage` and sends `Authorization: Bearer <token>` on each axios call (`app/Apis/publicapi.tsx`). ⚠️ The token is also `console.log`-ed in several functions.
- **Stateful/SPA cookie auth:** **disabled** — `EnsureFrontendRequestsAreStateful` is commented out in `bootstrap/app.php:24`. Auth is token-only. `SANCTUM_STATEFUL_DOMAINS` defaults to `localhost:5173` but is unused given the above.
- **Route protection:** applied per-block via `auth:sanctum` in `routes/api.php`. A large set of payment, phone-keyed storefront, DelayDog, business-category, and complaint endpoints sit **outside** the auth group and are therefore **public** (see §5).
- **Authorization / roles:** ⚠️ effectively none. `AdminMiddleware` (alias `admin`, applied to `routes/admin.php`) is a **no-op** (`return $next($request)`), so any authenticated Sanctum user can reach admin endpoints. No Gates, Policies, or role/permission tables exist.
- **OTP / password reset:** `otp_codes` table + `SendOtpMail`; reset flows via `reset-password` / `reset_password` / `verify-reset-password` (and Ordiio-specific variants). Frontend has two reset endpoints (`/reset_password` vs `/reset-password`), one likely stale.
- **Frontend guards:** purely client-side — `publiroute.tsx` (redirect if token present) and `dashboard/layout.tsx` (`verifyToken()` → redirect to `/login` on failure). Not a security boundary.

---

## 7. Background jobs / queues

- **Queue driver:** `database` (`config/queue.php`); failed jobs in `failed_jobs` (`database-uuids`); job batching enabled (`job_batches`). No Redis/SQS/Horizon configured.
- **Worker:** run via `php artisan queue:listen --tries=1` (included in the composer `dev` script). No supervisor/daemon config committed.
- **Jobs defined (`app/Jobs/`):**
  - `VerifyPaystackPayment` — functional; verifies a Paystack transaction (reads `PAYSTACK_SECRET_KEY`).
  - ⚠️ `SendWhatsAppTextJob` — **empty stub** (`handle()` returns void).
- **Scheduled/cron work:**
  - `app/Console/Commands/RunBroadcastCron` — broadcast dispatch command; intended to run on a schedule, but ⚠️ the only `broadcast:run` schedule lives in the **legacy, ignored** `app/Http/Kernel.php` (Laravel 11/12 does not load it), so it is **not auto-scheduled** in the current code — it would need an external cron or `routes/console.php` registration (neither present beyond `inspire`).
- **Events processed async:** `UserOnlineStatus` / `UserTyping` are `ShouldBroadcast` events (see §11), not queue jobs in the work sense.

---

## 8. Third-party integrations

| Service | How integrated | Used by (module) | Credentials |
|---|---|---|---|
| **Stripe** | `stripe/stripe-php` SDK | `StripeWebhookHandler`, `Stripe.php`, `TransactionController`, `OrdiioController`, `API\SubscriptionsController` (orders, appointments, instance activation, subscriptions) | `STRIPE_KEY`, `STRIPE_SECRET`, `ORDIIO_STRIPE_SECRET_KEY`, `ORDIIO_STRIPE_WEBHOOK_SECRET` ⚠️ `services.stripe.webhook_secret` config key referenced but undefined |
| **Paystack** | raw Guzzle/HTTP | `PayStackController`, `Jobs\VerifyPaystackPayment` (payments, webhooks, callbacks) | `PAYSTACK_SECRET_KEY` |
| **WHAPI (WhatsApp)** | raw HTTP (`gate.whapi.cloud`) | `MessageService`, `WhapiController`, `RunBroadcastCron`, `Space_whapichannel_details` (instance create/QR/activation, send/list messages) | `WHAPI_URL`, `WHAPI_MASTER_TOKEN` + per-space token in DB ⚠️ `services.whapi.token` config key referenced but undefined |
| **Google Gemini** | raw HTTP | `ChatController` (`/api/chat` AI replies, model `gemini-2.5-flash`) | `GEMINI_API_KEY` |
| **Google Calendar** | `google/apiclient` | `GoogleCalendarService` (appointment events) | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |
| **SourceAudio / SonicSearch** | raw HTTP | `SourceAudioService`, `SonicSearchService`, `SourceAudioApiController`, `Helpers\TrackHelper`, `YouTubeAllowlistService` (Ordiio music data) | `SOURCEAUDIO_API_KEY` |
| **Airtable** | raw HTTP | `SourceAudioApiController` (Ordiio) | `AIRTABLE_TOKEN` |
| **PhpSpreadsheet** | `phpoffice/phpspreadsheet` | `ProductsController`, `ServicesController` (bulk import) | — |
| **Email** | Laravel Mail (default `MAIL_MAILER=log`) | `SendOtpMail`, `ResetPasswordMail` | `MAIL_*` (Postmark SDK installed but unused) |
| **Reverb/Pusher** | configured, not wired | `Events\*` declare `ShouldBroadcast`; driver defaults to `log` | `REVERB_*` / `PUSHER_*` (defaults) |

**Installed but unused (no live code path):** `laravel/cashier`, `twilio/sdk`, `wildbit/postmark-php` (deprecated), `outhebox/blade-flags`, `stidges/laravel-country-flags`, `laravel/octane` + `spiral/roadrunner` + `swoole/ide-helper`, AWS/S3.

**Frontend client-side:** Paystack via backend-provided redirect/QR URLs. No Stripe.js, analytics, or realtime client.

---

## 9. Deployment architecture

Documenting only what the repo reveals — there is no committed IaC, Dockerfile, or pipeline.

- **Runtime:** PHP application served by **Apache** (evidenced by root `.htaccess`). The root `index.php` was edited to load `./vendor/autoload.php` and `./bootstrap/app.php`, meaning the **repository root is the web docroot** — a shared-hosting / cPanel-style layout rather than pointing the docroot at `public/`.
  - ⚠️ Consequence: `app/`, `config/`, `.env`, `routes/`, and the `app1/`/`app2/`/`app.zip` backups are under the served directory and rely on `.htaccess` rules to avoid exposure.
- **`.htaccess`:** rewrites non-file requests to `index.php`; forwards `Authorization` and `X-XSRF-Token` headers.
- **PHP version:** composer declares `php: ^8.2`, but the lockfile pulls `maennchen/zipstream-php` (via `phpoffice/phpspreadsheet`) which requires **PHP 8.3** — so the deployed PHP must be **8.3+** for `composer install` to succeed (PHP 8.3.31 was installed locally to satisfy this).
- **Datastore:** SQLite file by default (`database/database.sqlite`); production may switch `DB_CONNECTION` to MySQL/Postgres (connections preconfigured). Queue/cache/session all use the DB.
- **Asset build:** `npm run build` (Vite) for Blade assets; the Next.js frontend builds separately (`next build`) and is deployed independently (it points at the API via `NEXT_PUBLIC_API_BASE_URL`).
- **Process model:** plain PHP-FPM/Apache request handling. Octane/RoadRunner is configured but not used. A queue worker (`queue:listen`) must be run separately for jobs; no supervisor config is committed.
- **CI/CD:** ⚠️ none — no `.github/workflows`, `.gitlab-ci.yml`, or other pipeline files in either repo.
- **Known prod surface (from frontend code):** API host `https://api.joincroose.com` (path prefix `/croose/...` appears in hardcoded URLs), with storage served under `/croose/public/storage` and `/croose/storage`. Image domain `api.joincroose.com` is whitelisted in `next.config.ts`.

---

## 10. Key environment variables

### Backend — declared in `.env.example`
| Group | Variables |
|---|---|
| App | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `APP_MAINTENANCE_DRIVER`, `PHP_CLI_SERVER_WORKERS`, `BCRYPT_ROUNDS` |
| Logging | `LOG_CHANNEL`, `LOG_STACK`, `LOG_DEPRECATIONS_CHANNEL`, `LOG_LEVEL` |
| Database | `DB_CONNECTION` (=sqlite; host/port/database/username/password commented) |
| Session | `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN` |
| Queue/Cache/Broadcast/FS | `QUEUE_CONNECTION`, `CACHE_STORE`, `BROADCAST_CONNECTION`, `FILESYSTEM_DISK` |
| Redis/Memcached | `MEMCACHED_HOST`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` |
| Mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `MAIL_SCHEME` |
| AWS | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT` |
| Vite | `VITE_APP_NAME` |

### ⚠️ Backend — required by code but MISSING from `.env.example` (must be added for integrations to work)
| Group | Variables |
|---|---|
| Stripe | `STRIPE_KEY`, `STRIPE_SECRET`, `ORDIIO_STRIPE_SECRET_KEY`, `ORDIIO_STRIPE_WEBHOOK_SECRET` |
| Paystack | `PAYSTACK_SECRET_KEY` |
| WhatsApp | `WHAPI_URL`, `WHAPI_MASTER_TOKEN` |
| AI | `GEMINI_API_KEY` |
| Google Calendar | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |
| Ordiio/music | `SOURCEAUDIO_API_KEY`, `AIRTABLE_TOKEN` |
| Broadcast (optional) | `REVERB_APP_KEY/SECRET/ID/HOST/PORT/SCHEME`, `PUSHER_*`, `ABLY_KEY` |
| Mail (optional) | `POSTMARK_TOKEN`, `RESEND_KEY` |

### Frontend
| Group | Variables |
|---|---|
| API | `NEXT_PUBLIC_API_BASE_URL` (only env var read; set in `.env.local` to `http://127.0.0.1:8000` locally). ⚠️ No `.env.example`; several flows hardcode prod URLs and bypass this var. |

---

## 11. Real-time / event flows

- **Broadcasting events defined:** `app/Events/UserOnlineStatus` and `app/Events/UserTyping` implement `ShouldBroadcast`. Corresponding listeners `MarkUserOnline` / `MarkUserOffline` update a cache flag. ⚠️ `MarkUserOnline::handle()` references `Cache`/`UserOnlineStatus` without `use` imports (would fatal if invoked).
- **Channels:** `routes/channels.php` defines one private channel `App.Models.User.{id}`, authorized when `$user->id` matches.
- **Driver reality:** `BROADCAST_CONNECTION` defaults to `log` (`.env.example:36`). Reverb (`config/reverb.php`, default port 6001) and Pusher/Ably are configured but **not actively connected**, and **no frontend client subscribes** (no laravel-echo, pusher-js, socket.io, or EventSource in `croose_frontend`).
- **Effective real-time model:** **polling over REST**. The frontend simulates presence/typing/live-chat by polling endpoints: `GET /user-status/{id}`, `GET /user-typing/{id}`, `GET /space_chat_list`, `GET /total_chats`, `GET /Message/{phone}`.
- **WhatsApp inbound:** handled through WHAPI + the phone-keyed public endpoints rather than a socket; `Conversation` rows persist message/response history with `session_id` and `context_data`.
- **The `UpdateOnlineStatus` middleware** that would broadcast presence on each request is **not wired** into the live middleware stack (only in the ignored legacy `Kernel.php`).

> Summary: the infrastructure for WebSocket broadcasting is scaffolded but inert. No pub/sub or message broker is in use; async work is the database queue (§7).

---

## 12. Server access

Documenting strictly what is present in the repositories. **No SSH keys, deployment user, server credentials, firewall rules, or access-control configuration are committed to either repo** — none were found and none are invented here.

**What the code reveals about infrastructure:**
- **Production API hostname:** `api.joincroose.com` — referenced in hardcoded frontend URLs (`app/Apis/publicapi.tsx` in `RunAgentInfo`, `PayApi`, `getQr`; `components/upgradetopro.tsx`; `components/setting1.tsx`) and whitelisted in `croose_frontend/next.config.ts` (`images.domains`). Paths use a `/croose/...` prefix, with storage under `/croose/public/storage` and `/croose/storage`.
- **Raw server IP:** `68.183.108.227` — hardcoded over HTTPS in `croose_frontend/app/(private)/dashboard/product/page.tsx:147-148` for bulk-upload template downloads (`https://68.183.108.227/croose/public/storage/...`). This IP is in DigitalOcean's range (informational; not asserted as authoritative). It is the only literal server IP in the codebase.
- **Web server:** Apache, per the root `.htaccess` (mod_rewrite). Deployment is shared-hosting/cPanel-style with the repo root as docroot (see §9).
- **Local dev API:** `http://127.0.0.1:8000` (frontend `.env.local`), i.e. `php artisan serve`.

**Not present in the repo (so cannot be documented from code):**
- SSH host/port/config, `~/.ssh` material, `deploy` user, or any login credentials.
- Firewall / security-group rules, reverse-proxy (nginx) config, TLS/cert config.
- Server provisioning, secrets management, or environment-promotion process.

> ⚠️ Security note: the hardcoded production hostname and raw IP live in client-side source shipped to browsers. Any access control (SSH, firewall, WAF) is managed outside these repositories and must be obtained from the hosting/infra owner — it is not derivable from the code.
