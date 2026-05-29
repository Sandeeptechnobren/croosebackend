# Croose Codebase Audit

> Read-only investigation of the Croose backend and frontend repositories. No application code was modified to produce this document.

| | |
|---|---|
| **Backend** | `croosebackend` — Laravel 12 / PHP 8.2+ REST API |
| **Frontend** | `croose_frontend` — Next.js 15 (App Router) / React 19 / TypeScript |
| **Audit date** | 2026-05-29 |
| **Product** | Multi-tenant WhatsApp commerce/CRM ("Spaces"), plus two sub-products: **Ordiio** (music licensing) and **DelayDog** (rail delay claims) |

---

## Table of contents
1. [Top-level directory structure](#1-top-level-directory-structure)
2. [Tech stack](#2-tech-stack)
3. [Request data flow](#3-request-data-flow)
4. [Database models & relationships](#4-database-models--relationships)
5. [API routes / endpoints](#5-api-routes--endpoints)
6. [Shared utilities & patterns](#6-shared-utilities--patterns)
7. [Test setup](#7-test-setup)
8. [Build / deploy & CI/CD](#8-build--deploy--cicd)
9. [Environment variables & config](#9-environment-variables--config)
10. [Third-party integrations](#10-third-party-integrations)
11. [Dead code, unused deps & cleanup](#11-dead-code-unused-deps--cleanup)
12. [Critical issues summary](#12-critical-issues-summary)

---

## 1. Top-level directory structure

### Backend (`croosebackend`)

| Path | Description |
|---|---|
| `app/` | Application code (controllers, models, services, jobs, events). See breakdown below. |
| `bootstrap/` | `app.php` (Laravel 11/12-style app + routing + middleware config), `providers.php` (registers only `AppServiceProvider`), `cache/`. |
| `config/` | 15 config files: app, auth, broadcasting, cache, cors, database, filesystems, logging, mail, octane, queue, reverb, sanctum, services, session. |
| `database/` | `migrations/` (~55 files), `seeders/` (3), `factories/` (only `UserFactory`). |
| `public/` | Standard webroot: `index.php`, `favicon.ico`, `logo.jpg`, `robots.txt`. |
| `resources/` | `css/`, `js/`, `views/` (subfolders: `billing`, `emails`, `payment`, `payments`, `payments_ordiio`). |
| `routes/` | `api.php` (~228 lines), `admin.php`, `web.php`, `channels.php`, `console.php`, plus a stray `routes/Ordiio/Ordiio_api`. |
| `storage/`, `tests/`, `vendor/` | Standard Laravel runtime / test / dependency dirs. |
| `composer.json` / `composer.lock` | PHP dependency manifest + lockfile. |
| `package.json`, `vite.config.js` | Front-end asset build (Vite + Tailwind v4) for Blade views. |
| `artisan` | Laravel CLI entrypoint. |
| `index.php` (root) | **Non-standard** front controller at repo root (edited to load `./vendor/autoload.php` + `./bootstrap/app.php`) — shared-hosting/cPanel deploy layout where the repo root is the webroot. |
| `.htaccess` (root) | Apache rewrite to `index.php`; forwards `Authorization` / `X-XSRF-Token` headers. |
| `phpunit.xml` | PHPUnit test config. |
| ⚠️ `app1/`, `app2/`, `app.zip` | **Dead backup snapshots of `app/`** (see §11). Live under the deploy webroot — security flag. |

**`app/` breakdown:**
- `Http/Controllers/` — 35+ controllers; subfolders `Admin/`, `API/` (AuthController, FeedBackController, SubscriptionsController), `OrdiioApiController/`. Non-PSR names exist: `Stripe.php`, `paymentController.php`, `Ordiio_settings_controller.php`.
- `Http/Middleware/` — `AdminMiddleware`, `ApiCacheMiddleware`, `UpdateOnlineStatus`.
- `Http/Requests/` — `BroadcastStoreRequest`, `BroadcastUpdateRequest`, `YouTubeAllowlistRequest`.
- `Http/Resources/` — only `BroadcastResource`.
- `Models/` — ~50 Eloquent models (mixed PascalCase + snake_case filenames).
- `Providers/` — `AppServiceProvider` (no-op), `RouteServiceProvider`.
- `Services/` — Broadcast, GoogleCalendar, Message, SonicSearch, SourceAudio, StripeWebhookHandler, User, YouTubeAllowlist, + `Ordiio/` subdir.
- `Jobs/` — `SendWhatsAppTextJob` (empty stub), `VerifyPaystackPayment`.
- `Events/` — `UserOnlineStatus`, `UserTyping`. `Listeners/` — `MarkUserOnline`, `MarkUserOffline`.
- `Mail/` — `ResetPasswordMail`, `SendOtpMail`.
- `Console/Commands/` — `RunBroadcastCron`; legacy `Console/Kernel.php` (unused).
- `DTOs/`, `Helpers/`, `Traits/` (`ApiResponse` — unused).

### Frontend (`croose_frontend`)

| Path | Description |
|---|---|
| `app/` | All application code (Next.js App Router, no `src/`). |
| `app/(public)/` | Unauthenticated route group: `login`, `signup`, `emailverification`, `forgotcard`, plus shared `component/` (navbar, selectbox, client-side `publiroute.tsx` guard). |
| `app/(private)/` | Authenticated route group: `dashboard/` (home, overview, space, appointment, customers, product, orders, messaging, subscription, payments, liveagent*, etc.), `customerspace/`, `customisespace/`, `spacebusiness/`, shared `components/` (~40), `Iqcontext.tsx`. |
| `app/Apis/publicapi.tsx` | **The single API client module** — all axios calls live here. |
| `app/context/`, `app/content/` | `SettingContext`, `Content.tsx` (mounts global setting modals). |
| `app/layout.tsx` / `page.tsx` / `globals.css` | Root layout (providers + react-toastify), home (renders `Signupform`), Tailwind v4 entry. |
| `public/` | ~75 image/SVG assets. |
| `next.config.ts`, `tsconfig.json`, `postcss.config.mjs`, `.env.local` | Config files. No `.env.example`, no ESLint config, no `tailwind.config` (Tailwind v4 CSS-first). |
| ⚠️ `how`, `how --stat 6da408b`, `Croose V1 MVP - June/` | **Stray git-output artifacts + a stray asset folder** (see §11). |

---

## 2. Tech stack

### Backend
- **Framework:** Laravel `^12.0`; **PHP** `^8.2`.
- **Auth:** Laravel Sanctum `^4.0` (bearer tokens). Default guard `api` (driver `sanctum`), default provider `clients` → `App\Models\Client`; second provider `ordiio_users` → `App\Models\OrdiioUser`. SPA stateful auth (`EnsureFrontendRequestsAreStateful`) is **commented out** in `bootstrap/app.php:24` → token-only auth.
- **ORM:** Eloquent.
- **Database:** default **SQLite** (`config/database.php`, `.env.example:23`); mysql/mariadb/pgsql/sqlsrv connections also defined.
- **Queue:** `database` (failed jobs `database-uuids`, batching enabled). **Cache:** `database`. **Session:** `database`. **Mail:** default `log`.
- **Broadcasting:** Reverb `^1.0` configured (also Pusher/Ably), but `BROADCAST_CONNECTION` defaults to `log` — not actively wired.
- **App server:** Octane `^2.12` + Spiral RoadRunner `^2025.1` configured (`config/octane.php` → `roadrunner`), but no app code uses Octane.
- **Notable libs:** Cashier `16.0.1` + stripe-php `^17.4`, Twilio SDK `^8.6`, Postmark `^7.0`, google/apiclient `^2.18`, Guzzle `^7.9`, phpoffice/phpspreadsheet `^4.4`, blade-flags / laravel-country-flags, Tinker.

### Frontend
- **Framework:** Next.js `^15.3.8` (App Router, **Turbopack** dev), React `^19.2.3`, TypeScript `^5.8.3`.
- **HTTP:** axios `^1.10.0` (sole client).
- **Forms:** Formik.
- **Styling — multiple systems (flag):** Tailwind CSS v4 (primary) + MUI v7 (used) + styled-components (declared, unused) + emotion (MUI transitive peer).
- **UI/icons:** @headlessui, @heroicons, lucide-react, react-icons, @iconify/react (used); @material-tailwind/react (declared, unused); react-select (used in one page).
- **Toasts — duplicate libs (flag):** both `react-toastify` (global) and `react-hot-toast` are installed and used.
- **Declared-but-unused (flag):** `react-router-dom` (unusual in Next.js), `react-chatbot-kit`, `styled-components`, `dotenv`.

---

## 3. Request data flow

### Backend

**Entry chain:** `public/index.php` → `bootstrap/app.php` (`$app->handleRequest(...)`) → routing/middleware config (`bootstrap/app.php:8-29`).

**Route registration (`bootstrap/app.php:9-19`):**
- `web:` → `routes/web.php`
- `api:` → **both** `routes/api.php` **and** `routes/admin.php` (no extra `/api` prefix set; default `api/` prefix applies)
- `commands:` → `console.php`; `channels:` → `channels.php`; health check `/up`.

**Middleware actually in effect (`bootstrap/app.php:20-26`)** — only the **api group** is customized:
- `HandleCors`
- alias `'admin' => AdminMiddleware`
- `EnsureFrontendRequestsAreStateful` is **commented out**

Everything else (global middleware, `auth:sanctum`, `SubstituteBindings`, throttling) is the framework default — **not** declared here.

**Typical API request path:** `index.php` → `bootstrap/app.php` → CORS → `auth:sanctum` (per-block) → route match → Controller method → Eloquent/DB → `response()->json([...])`. Controllers build JSON inline; `Http/Resources/` exists but is barely used.

**Custom middleware (`app/Http/Middleware/`):**
- ⚠️ **`AdminMiddleware`** — registered as `admin` alias but its `handle()` is a **no-op** (`return $next($request)`). Provides **no admin authorization**; admin routes rely only on `auth:sanctum`.
- `ApiCacheMiddleware` — caches GET 200/201 for 10 min. **Not active** (only in unused `Kernel.php`).
- `UpdateOnlineStatus` — broadcasts presence. **Not active** (only in unused `Kernel.php`).

⚠️ **Dead `app/Http/Kernel.php`** — legacy Laravel-10-style kernel describing a different middleware stack + a `broadcast:run` schedule. In Laravel 11/12 this file is **ignored**, so those middlewares and that schedule never run.

⚠️ **Admin routes registered twice:** once via the `api` array in `bootstrap/app.php`, and again in `RouteServiceProvider.php:10-12` under `['api','auth:sanctum','admin']` with prefix `admin`.

### Frontend
Pages are client components calling functions in `app/Apis/publicapi.tsx` (axios + `Bearer` token from `localStorage`). Auth guards are **client-side only**: `(public)/component/publiroute.tsx` redirects to dashboard if a token exists; `(private)/dashboard/layout.tsx` calls `verifyToken()` and redirects to `/login` on failure. Base URL = `process.env.NEXT_PUBLIC_API_BASE_URL`.

---

## 4. Database models & relationships

All models in `app/Models/`. **Correctness issues are flagged first because several will fatal on autoload.**

### ⚠️ Data-integrity / correctness issues
- **Unresolved Git merge-conflict markers** (`<<<<<<< HEAD … >>>>>>> b872fe7 (Live code)`) left in **7 files** → PHP parse/fatal errors when autoloaded:
  - `Services/SourceAudioService.php`, `Services/YouTubeAllowlistService.php`, `Services/Ordiio/SourceAudioApiService.php`
  - `Models/OrdiioUser.php`, `Models/OrdiioLicensePurchase.php`, `Models/ordiio_license_purchases.php`, `Models/OrdioCheckoutSession.php`
- **`Models/Licensed_track.php:9-14`** — missing semicolon after `$fillable` → **syntax error**.
- **Appointment model ↔ migration mismatch:** model fillable uses `appointment_date`/`start_time`/`end_time`, but migration creates `scheduled_at`/`start_time`/`end_time` (`appointment_date` doesn't exist).
- **Space model ↔ migration mismatch:** model fillable includes `country` (and `uuid`) not in the base spaces migration (`uuid` added later; `country` appears unmigrated).
- **Duplicate models for the same tables** (Ordiio refactor in flight): `OrdiioLicenseCategory` vs `ordiio_license_categories`; `OrdiioLicensePurchase` vs `ordiio_license_purchases`; `OrdiioTransaction` vs `Ordiio_transaction`. Some relations point to **nonexistent classes** (`OrdiioLicense`, `Track`).
- **Duplicate migrations:** `2025_08_23_*` and `2025_11_15_*` both create `ordiio_license_categories` and `ordiio_license_purchases` → will collide on `migrate`.

### Core CRM models
| Model | Table (if non-default) | Relationships |
|---|---|---|
| `User` | users | auth model for Sanctum/admin (no relations defined) |
| `Client` (Authenticatable, HasApiTokens, SoftDeletes) | clients | hasMany Appointment; belongsToMany Customer (pivot `client_customer`) |
| `Space` (SoftDeletes) | spaces | belongsTo Client |
| `Product` | products | belongsTo Space |
| `Service` | services | belongsTo Space |
| `Customer` | customers | hasMany Appointment; hasMany Order; belongsToMany Client |
| `Appointment` | appointments | belongsTo Client/Customer/Service |
| `Order` (SoftDeletes) | orders | belongsTo Client/Customer/Product/Space |
| `Transaction` (SoftDeletes) | transactions | belongsTo Client/Customer |
| `ClientCustomer` | client_customer | belongsTo Customer (pivot model) |
| `Categories` | categories | hasMany Product/Service; belongsTo Client |
| `BusinessCategory` (SoftDeletes) | business_categories | — |
| `Country` (SoftDeletes) | countries | — |
| `Conversation` | conversations | belongsTo Client/Space/Customer |

### Space-IQ / WhatsApp (Whapi)
`space_iq` (space_iqs), `SpaceIqDocs` (spaces_iq_docs), `Space_whapichannel_details` (belongsTo Space/Client), `SpaceWhapiPaymentDetail` (belongsTo Client/Space), `WhitelistChannel` (belongsTo User).

### Broadcast / messaging
`BroadcastHeader` (belongsTo TargetMessage), `TargetMessage` (`Customers()` returns a dynamic segment query, not a real relation), `FeedBackManagement`.

### Subscriptions
`Subscription` (hasMany subscription_items; belongsTo Space/Service; hasMany customer_subscriptions), `subscription_items` (morphTo `item`), `customer_subscriptions` (belongsTo Subscription/Customer/Service; hasMany subscription_transaction), `subscription_transaction`.

### DelayDog (rail-delay sub-product)
`DelayDogUserDetail` (hasMany journeys), `DelayDogJourney` (belongsTo user), `DelayDogClaims` (belongsTo journey/user), `DelayDogTickets` (belongsTo journey).

### Ordiio (music-licensing sub-product — partially broken/duplicated)
`OrdiioUser` (Cashier Billable, conflict markers), `OrdiioSubscription`, `OrdiioTransaction`/`Ordiio_transaction`, `OrdiioLicenseCategory`/`ordiio_license_categories`, `OrdiioLicensePurchase`/`ordiio_license_purchases`, `Ordiio_cart`, `Ordiio_favourites`, `Ordiio_playlists`, `ordiio_playlist_tracks`, `OrdioCheckoutSession`, `Licensed_track`. **None have live HTTP routes** (see §5).

### Relationship map (core)
```
Client 1─* Space 1─* Product
                  1─* Service
                  1─* Order / Appointment / Conversation
Client *─* Customer (pivot client_customer; +space_id)
Customer 1─* Appointment, 1─* Order
Appointment *─1 Service/Customer/Client
Order *─1 Product/Customer/Client/Space
Transaction *─1 Client/Customer
Space 1─* Subscription 1─* subscription_items (morphTo product/service)
Subscription 1─* customer_subscriptions 1─* subscription_transactions
TargetMessage 1─* BroadcastHeader
DelayDogUserDetail 1─* DelayDogJourney 1─* {DelayDogClaims, DelayDogTickets}
```

### Seeders / factories
- `DatabaseSeeder` → `DemoDataSeeder` (inserts 10 fake orders referencing client/customer/product IDs 1–10 it does **not** create → FK failure on an empty DB). `OrdiioLicenseCategorySeeder` exists but is not called.
- Only `UserFactory` exists.

---

## 5. API routes / endpoints

All API routes use the default `api/` prefix (e.g. `POST api/register`). `routes/api.php` has **no global group**; `auth:sanctum` is applied per-block.

### Auth (`AuthController`, public — `api.php:33-43`)
`POST /register`, `POST /login`, `POST /reset-password`, `GET /clients`, `POST /send_otp`, `POST /reset_password`, `POST /ordiio/forgot-password`, `POST /ordiio/reset-password`, `POST /verify-reset-password`, `POST /find_account/{email}`.

### Authenticated block (`auth:sanctum` — `api.php:44-147`)
- **Account:** `POST /account_profile`, `POST /account/profile/update`, `POST /update_password`, `POST /logout`.
- **Services:** `POST /services/show`, `PUT /services/{id}`, `POST /services`, `GET /services`, `POST /services/bulkupload`, `DELETE /services/{id}`, `GET /getServicesBySpace`.
- **Products:** `GET /products`, `PUT /products/{id}`, `DELETE /products/{id}`, `POST /products`, `POST /products/bulkupload`, `GET /getProductBySpace`.
- **Customers:** `POST /customer/register`, `GET /getCustomer`, `GET /getCustomerByPhone`, `GET /customer_statistics`.
- **Appointments:** `GET /appointments`, `GET /appointment_statistics`, `POST /appointments_status_update`, `DELETE /appointments/{id}`.
- **Orders:** `GET /order_statistics`, `GET /orders`, `POST /orders_status_update`, `POST /createmanualorder`.
- **Spaces:** `POST /create_space`, `GET /space`, `POST /update_space`, `POST /check-user-space`, `POST /checkspaceIQincresed`, `POST /space_iq`, `GET /get_space_list`, `GET /get_space_prompt`, `POST /update_space_prompt`, `GET /space_chat_stats`, `GET /space_chat_list`, `POST /space_activation_charge`.
- **Categories:** `GET /get_categories`, `POST /get_template/{type}`.
- **Conversations:** `GET /get_conversations`, `GET /total_chats`.
- **Whapi automation:** `POST /whapi/instance`, `POST /whapi/instancenew`, `GET /whapi/instance/qr`, `GET /whapi/instance_activation_status`.
- **Payment:** `POST /payment_details`.
- **Subscriptions:** `POST /create_subscription`, `GET /subscription_list`, `POST /check_name`, `GET /subscribers_list`, `GET /subscriber_statistics`; prefix `subscriptions`: `GET /subscriptions`, `PUT /subscriptions/{id}`, `POST /subscriptions/{id}/archive`, `POST /subscriptions/{id}/unarchive`, `DELETE /subscriptions/{id}`.
- **Broadcast/messaging:** `GET /broadcast/list`, `GET /broadcast/show{id}` *(malformed URI — missing slash)*, `POST /broadcast/add`, `PUT /broadcast/update/{id}`, `DELETE /broadcast/delete/{id}`, `GET /user-status/{id}`, `POST /typing-start`, `POST /typing-stop`, `GET /target/messages/{id}`, `GET /target/list`, `POST /sendBroadcastMessage`, `GET /Message/{phone}`, `POST /sendWhatsapp`.
- **Chat:** `POST /chat`.

### ⚠️ Public routes OUTSIDE the auth group (`api.php:149-228`)
These sit after the auth group's closing brace, so they are **unauthenticated** — likely unintentional for the write endpoints:
- **Phone-keyed storefront/booking (WhatsApp-bot facing):** `GET|POST /orders/{client_phone}/{customer_phone}`, `GET|POST /appointments/{space_phone}/{customer_phone}`, `GET /products/{phoneNumber}`, `GET /services/{phoneNumber}`, `GET /manual-token-check`, `GET /countries`, `GET /available-slots/{space_phone}`, `POST /store_transaction/...`, `GET /get_transaction/...`, `PUT /products/{id}` *(duplicate, here public)*, `POST /categories`, `GET|POST /business_categories`, `GET /business_categories/{id}`.
- **Payments (Stripe & Paystack):** `GET /stripe/order/{uuid}`, `GET /paystack/order/{uuid}`, `GET /stripe/appointment/{uuid}`, `GET /paystack/appointment/{uuid}`, `GET /stripe/instance/{uuid}` *(declared twice — second wins)*, `GET /paystack/instance/{uuid}`, `GET /paystack/subscription/{uuid}`, `GET /paystack/whapi/{space_uuid}`, `GET /paystack/callback`, `POST /paystack/webhook`, `POST /paystack/webhook_test`, `POST /payment/status`, `POST /stripe/webhook` *(declared **3×** — last `SubscriptionsController@handle` wins)*.
- **Customer subscription checkout:** `POST /subscribe/{uuid}/{phone}`, `GET /stripe-checkout/{uuid}/{phone}`, `GET /stripe/success`.
- **DelayDog:** `POST /delaydogusers/{user_phone}`, `POST /delaydogjourney/{user_phone}`, `POST /delaydogclaims/{user_phone}/{journey_uuid}`, `POST /delaydogtickets`.
- **Complaints/Feedback (prefix `complain`):** `GET /complain/list`, `POST /complain/add`, `GET /complain/show/{id}`, `POST /complain/update/{id}`, `DELETE /complain/delete/{id}`.

### Admin routes (`routes/admin.php`, prefix `admin`, `auth:sanctum`)
All `Admin\AdminController`: `GET /admin/dashboard`, `GET /admin/users`, `POST /admin/user/status/{id}`, `GET /admin/orders`, `POST /admin/orders/status`, `GET /admin/products`, `DELETE /admin/products/{id}`, `GET /admin/services`, `DELETE /admin/services/{id}`, `GET /admin/subscriptions`, `POST /admin/subscription/archive/{id}`.
⚠️ The `admin` middleware is a **no-op**, so any authenticated Sanctum user can call these. Routes are also registered twice (see §3).

### Web routes (`routes/web.php`) — payment landing pages
`GET /` (welcome), `GET /payment-success`, `GET /payment-cancel`, ordiio success/cancel pages, `GET /payment/{order|appointment|instance|subscription}/{uuid}`, `POST /ordiio/create-checkout`, ordiio license success/cancel, `GET /subscribe/{uuid}/{phone}`.
⚠️ web.php imports `OrdiioPaymentsController` and `OrdiioLicenseController` which **do not exist** in `app/Http/Controllers/` → these routes throw on resolution.

### Console & channels
- `console.php`: only `inspire`.
- `channels.php`: private channel `App.Models.User.{id}` (authorized by id match).

### ⚠️ Cross-cutting route issues
- Many payment / storefront-write / DelayDog / complaint endpoints are **public** — review intent.
- **Duplicate route definitions silently override** earlier ones (`/stripe/webhook` ×3, `/stripe/instance/{uuid}` ×2, `PUT /products/{id}` public vs authed).
- The **entire Ordiio music-licensing module has no registered routes** (`routes/Ordiio/Ordiio_api` is empty/unloaded) — unreachable via HTTP.

### Frontend → backend endpoints consumed
`app/Apis/publicapi.tsx` calls the modules above (auth, spaces, products, services, appointments, customers, orders, payments, subscriptions, broadcast/messaging, chat/whapi). ⚠️ **Hardcoded URLs bypass the env var** (see §5 of frontend findings, repeated in §11):
- `https://api.joincroose.com/...` in `RunAgentInfo`, `PayApi`, `getQr`, `upgradetopro.tsx`, `setting1.tsx`.
- **Raw IP** `https://68.183.108.227/croose/public/storage/...` in `dashboard/product/page.tsx:147-148` (bulk-upload templates).
- `next.config.ts` whitelists `api.joincroose.com` for images.

---

## 6. Shared utilities & patterns

### Backend
- **`Traits/ApiResponse.php`** — `success()`/`fail()` JSON envelope helpers. ⚠️ **Confirmed dead** — never imported anywhere.
- **Base `Controller.php`** — empty abstract stub; no shared response/auth helpers. Controllers build JSON ad hoc.
- **Service layer (`app/Services`)** — the main reuse point: `MessageService` (WHAPI send/list), `BroadcastService` (CRUD + audit stamps), `GoogleCalendarService`, `StripeWebhookHandler`, `SourceAudioService` / `SonicSearchService` / `Ordiio\*` (SourceAudio API), `YouTubeAllowlistService`. ⚠️ `UserService` is an **empty stub**.
- **Helpers (`app/Helpers`)** — `TrackHelper`, `TargetCustomers` (plain static classes; **not** a composer-autoloaded `helpers.php`).
- **DTOs** — `CuratedDTO`, `TrackDTO` (static `fromArray()` mappers).
- **Form Requests** — only 3, used by `BroadcastController` + `Ordiio_settings_controller`; the other ~30 controllers validate inline.
- **API Resources** — only `BroadcastResource`.
- **Jobs** — `VerifyPaystackPayment` (functional); ⚠️ `SendWhatsAppTextJob` empty.
- **Events/Listeners** — presence events; ⚠️ `MarkUserOnline::handle()` references `Cache`/`UserOnlineStatus` **without `use` imports** → would fatal if invoked.
- **Conventions:** dominant response shape `response()->json(['status'=>…, 'message'=>…, 'data'=>…])` built **inline** (inconsistent spacing signals copy-paste); repeated `try/catch(\Exception)` in nearly every controller; inconsistent file/class naming (`paymentController`, `Stripe`, snake_case models).

### Frontend
- **Single API module** (`app/Apis/publicapi.tsx`) wraps all axios calls; bearer token from `localStorage`; standard try/catch returning `err.response.data.message`.
- **Contexts:** `IqProvider` (space-IQ flag), `SettingContext` (modal state).
- **Component libraries** under `(private)/components/` and `(public)/component/`; client-side route guards.

---

## 7. Test setup

- **Framework:** PHPUnit `^11.5.3` (no Pest, despite `pestphp/pest-plugin` being allow-listed).
- **Run:** `php artisan test` (composer `test` script clears config first).
- **Suites (`phpunit.xml`):** `Unit` → `tests/Unit`, `Feature` → `tests/Feature`; coverage source `app/`. Test env: sqlite `:memory:`, array cache/session, sync queue, array mail, bcrypt rounds 4.
- ⚠️ **Only 2 stub tests** (`Feature/ExampleTest`, `Unit/ExampleTest`) → effectively **no real coverage**. `tests/routes/` also holds stray copies of route files.
- **Frontend:** no test framework configured (only a `lint` script, but no ESLint config file present).

---

## 8. Build / deploy & CI/CD

### Backend
- **Composer scripts:** `dev` runs `artisan serve` + `queue:listen` + `pail` + `npm run dev` concurrently; `test` clears config + runs tests; `post-create-project-cmd` touches `database/database.sqlite` + migrates.
- **Asset build:** `npm run build` (`vite build`) / `npm run dev` (`vite`) for Blade views.
- ⚠️ **No CI/CD** — no `.github/workflows`, no `.gitlab-ci.yml`.
- ⚠️ **No Docker/Sail files** (Sail is a dev dependency only).
- **Deploy layout:** shared-hosting/cPanel style — root `index.php` + `.htaccess` make the **repo root the webroot**, which exposes `app/`, `config/`, `.env`, and the `app1/app2/app.zip` backups unless protected. **Security flag.**

### Frontend
- **Scripts:** `dev` (`next dev --turbopack`), `build` (`next build`), `start`, `lint`.
- No CI/CD config present.

---

## 9. Environment variables & config

### Backend — present in `.env.example`
App (`APP_*`, `BCRYPT_ROUNDS`, `PHP_CLI_SERVER_WORKERS`), Logging (`LOG_*`), DB (`DB_CONNECTION=sqlite`), Session, Queue/Broadcast/Cache/Filesystem (`QUEUE_CONNECTION=database`, `BROADCAST_CONNECTION=log`, `CACHE_STORE=database`, `FILESYSTEM_DISK=local`), Redis/Memcached, Mail (`MAIL_MAILER=log`), AWS, `VITE_APP_NAME`.

### ⚠️ Backend — hidden required env vars (referenced in code but MISSING from `.env.example`)
Third-party keys read via `env()`:
- `SOURCEAUDIO_API_KEY` (~15 files), `AIRTABLE_TOKEN`
- `STRIPE_KEY`, `STRIPE_SECRET`, `ORDIIO_STRIPE_SECRET_KEY`, `ORDIIO_STRIPE_WEBHOOK_SECRET`
- `PAYSTACK_SECRET_KEY`
- `GEMINI_API_KEY`
- `WHAPI_URL`, `WHAPI_MASTER_TOKEN`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`

⚠️ Referenced via `config()` but the config keys **don't exist** → always resolve to null (likely bugs):
- `services.stripe.webhook_secret` (used in `SubscriptionsController`, `TransactionController`) — no `webhook_secret` under `stripe` in `config/services.php`.
- `services.whapi.token` (used in `MessageService`) — no `whapi` block in `config/services.php`.

Config-level vars with sensible defaults (not in env.example): `REVERB_*`, `PUSHER_*`, `ABLY_KEY`, `OCTANE_SERVER`, `POSTMARK_TOKEN`, `RESEND_KEY`, `SLACK_BOT_USER_OAUTH_TOKEN`, `SANCTUM_STATEFUL_DOMAINS`.

### Frontend
- Only `NEXT_PUBLIC_API_BASE_URL` is read. `.env.local` present (`http://127.0.0.1:8000`). ⚠️ **No `.env.example`.**
- Config: `next.config.ts` (image domains + webp/avif), `tsconfig.json` (strict, `@/*` alias), `postcss.config.mjs`.

---

## 10. Third-party integrations

| Integration | Package(s) | Used? | Keys |
|---|---|---|---|
| **Stripe (direct SDK)** | `stripe/stripe-php` | ✅ Yes — `StripeWebhookHandler`, `Stripe.php`, `TransactionController`, `OrdiioController`, `API\SubscriptionsController` | `STRIPE_*`, `ORDIIO_STRIPE_*` |
| **Stripe Cashier** | `laravel/cashier` (pinned `16.0.1`) | ❌ No `Billable`/Cashier usage — **unused candidate** | — |
| **Paystack** | raw Guzzle/HTTP (no package) | ✅ Yes — `VerifyPaystackPayment`, `PayStackController` | `PAYSTACK_SECRET_KEY` |
| **Google Gemini AI** | direct HTTP | ✅ Yes — `ChatController` (`gemini-2.5-flash`) | `GEMINI_API_KEY` |
| **Google Calendar** | `google/apiclient` | ✅ Yes — `GoogleCalendarService` | `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI` |
| **WHAPI (WhatsApp)** | direct HTTP (`gate.whapi.cloud`) | ✅ Yes — `MessageService`, `WhapiController`, `RunBroadcastCron` | `WHAPI_URL`, `WHAPI_MASTER_TOKEN` (+ per-space token in DB) |
| **SourceAudio / SonicSearch** | direct HTTP | ✅ Yes — SourceAudio services/controllers, `TrackHelper` | `SOURCEAUDIO_API_KEY` |
| **Airtable** | direct HTTP | ✅ Yes — `SourceAudioApiController` | `AIRTABLE_TOKEN` |
| **Twilio** | `twilio/sdk` | ❌ Only an unused `use` import in `API\AuthController` — **unused candidate** | — |
| **Postmark** | `wildbit/postmark-php` | ❌ Only an unused `use` import; package is **deprecated** (superseded by ActiveCampaign fork) — **unused candidate** | — |
| **PhpSpreadsheet** | `phpoffice/phpspreadsheet` | ✅ Yes — product/service bulk import | — |
| **Country flags** | `outhebox/blade-flags`, `stidges/laravel-country-flags` | ❌ No app usage — **unused candidates** | — |
| **Reverb (broadcast)** | `laravel/reverb` | ⚠️ Config + `ShouldBroadcast` events exist, but driver defaults to `log` — **not wired** | — |
| **AWS/S3** | Flysystem (Laravel default) | ❌ No app usage (`FILESYSTEM_DISK=local`) | — |
| **Octane / RoadRunner / Swoole** | `laravel/octane`, `spiral/roadrunner`, `swoole/ide-helper` | ❌ Config published, no app usage — **unused candidates** | — |

**Frontend client-side:** Paystack (via backend redirect URLs/QR). No Stripe.js, no analytics, no websocket/Reverb/Pusher/Echo client — real-time behavior is REST polling (`user-status`, `user-typing`, chat lists). `react-chatbot-kit` installed but unused.

---

## 11. Dead code, unused deps & cleanup

### Backend — confirmed dead
- **`app1/` (77 files), `app2/` (102 files), `app.zip` (~110 KB):** stale backup snapshots of `app/`. `app/` is a strict superset; they share the `App\` namespace but composer maps `App\` → `app/` only, so they're never loaded. **Safe to delete** (and they sit under the deploy webroot — security risk).
- **7 merge-conflict files** (listed in §4) — **highest priority**; will fatal on autoload.
- **Broken `web.php` routes:** `OrdiioPaymentsController` (3 routes) and `OrdiioLicenseController` (2 routes) — classes **do not exist** → fail at dispatch.
- **Empty stubs:** `Services/UserService.php`, `Jobs/SendWhatsAppTextJob.php`, `Traits/ApiResponse.php` (unused), `Providers/AppServiceProvider.php` (no-op), legacy `Http/Kernel.php` (ignored by L11/12).
- **Substantial commented-out blocks:** `MessageService`, `TrackHelper`, `SonicSearchService`, `YouTubeAllowlistService`, plus the dead halves of conflict files.

### Backend — orphaned controllers (CANDIDATES, verify before removing)
`Stripe.php`, `CustomerSubscriptionsController.php`, `SubscriptionItemsController.php`, `SubscriptionsTransactionController.php` — no route references found. The entire Ordiio controller set has no live routes.

### Backend — unused dependency candidates (verify before removing)
`laravel/octane`, `spiral/roadrunner`, `laravel/reverb`, `swoole/ide-helper`, `laravel/cashier` (and exact pin `16.0.1`), `twilio/sdk`, `wildbit/postmark-php` (deprecated), `outhebox/blade-flags`, `stidges/laravel-country-flags`.

**Genuinely used (keep):** `laravel/framework`, `laravel/sanctum`, `google/apiclient`, `guzzlehttp/guzzle`, `phpoffice/phpspreadsheet`, `stripe/stripe-php`.

### Backend — risky version pins / deprecations
- `phpoffice/phpspreadsheet ^4.4` → transitively pulls `maennchen/zipstream-php` which **requires PHP 8.3**, while composer declares `php: ^8.2`. **This already blocks `composer install` on a PHP 8.2 host** (the reason PHP 8.3 was installed locally for this setup).
- `laravel/cashier: 16.0.1` exact pin (no `^`) blocks patch updates — and is unused.
- `wildbit/postmark-php ^7.0` — abandoned namespace.

### Frontend — confirmed dead / issues
- **`app/Apis/publicapi.tsx`:** ~960 lines of **commented-out duplicate** code (lines ~1–965); live code starts ~line 968. Also a broken live function `OrderStatistics` (`url: \`c\`` typo). Heavy `console.log` (tokens logged).
- **Unused declared deps:** `react-router-dom` (unusual in Next.js), `@material-tailwind/react`, `react-chatbot-kit`, `styled-components`, `dotenv`.
- **Duplicate libs:** two toast libraries (`react-toastify` + `react-hot-toast`); multiple styling systems (Tailwind + MUI + styled-components/emotion).
- **Stray root artifacts:** `how` (captured `git log` output), `how --stat 6da408b` (captured `git show --stat` output), `Croose V1 MVP - June/` (single stray `Icon.png`). All safe to delete.
- **Hardcoded prod URLs / raw IP** bypassing `NEXT_PUBLIC_API_BASE_URL` (see §5).
- **Two different reset endpoints** (`/api/reset_password` vs `/api/reset-password`) — one likely stale.
- **Security:** token in `localStorage`, logged to console; auth guards client-side only.
- `app/page.tsx` imports `Image`/`Login` but renders neither; `README.md` is the default template.

---

## 12. Critical issues summary

Ordered by severity — these are the items most likely to break the app or expose risk:

1. **🔴 7 unresolved Git merge-conflict files + 1 syntax error in `app/Models` & `app/Services`** — any request loading these classes fatals. Resolve before running anything beyond a trivial route.
2. **🔴 Broken `web.php` routes** referencing non-existent `OrdiioPaymentsController` / `OrdiioLicenseController`.
3. **🔴 Auth/authorization gaps:** `AdminMiddleware` is a no-op (any authenticated user is "admin"); many payment/storefront-write endpoints are unintentionally public.
4. **🟠 Duplicate route definitions** silently override each other (`/stripe/webhook` ×3, `/stripe/instance` ×2, `PUT /products/{id}`).
5. **🟠 `config()` references with no backing config key** (`services.stripe.webhook_secret`, `services.whapi.token`) resolve to null → webhook signature / WhatsApp token bugs.
6. **🟠 Model ↔ migration column mismatches** (Appointment, Space) and **duplicate Ordiio migrations** that collide on `migrate`.
7. **🟠 PHP version mismatch:** lockfile needs PHP 8.3 (zipstream) but composer declares `^8.2`.
8. **🟡 Deploy layout exposes source** (repo root = webroot; `app1/app2/app.zip` backups, `.env`, `config/` reachable unless `.htaccess`-protected).
9. **🟡 No CI/CD and effectively no tests** (2 stub tests; no frontend tests/lint config).
10. **🟡 `.env.example` missing ~15 required third-party keys**; frontend has no `.env.example`.
11. **🟢 Significant dead weight:** `app1/app2/app.zip`, ~960 commented lines in `publicapi.tsx`, 5+ unused composer packages, 5 unused npm packages, stray root artifacts.

> **Note:** Dead-code and unused-dependency entries marked "candidate" need verification (service-container resolution, dynamic dispatch, Blade usage) before deletion. Confirmed items are labelled as such.
