# Croose Code Patterns & Style Guide

> The de-facto conventions used in `croosebackend` (Laravel 12 API) and `croose_frontend` (Next.js 15 dashboard), each illustrated with **one real example pulled from the codebase**. This describes how the code is written *today* — including some inconsistencies worth knowing about. Companion to [ARCHITECTURE.md](ARCHITECTURE.md) and [CODEBASE_AUDIT.md](CODEBASE_AUDIT.md).
>
> Where the established pattern has a known pitfall, it is flagged with ⚠️ and a recommendation. New code should follow the dominant pattern unless a ⚠️ says otherwise.

---

## Table of contents
1. [API route / endpoint structure](#1-api-route--endpoint-structure)
2. [Database queries (ORM)](#2-database-queries-orm)
3. [Error handling](#3-error-handling)
4. [Auth & middleware on routes](#4-auth--middleware-on-routes)
5. [Environment variables & config](#5-environment-variables--config)
6. [Module / feature folder organization](#6-module--feature-folder-organization)
7. [Tests](#7-tests)
8. [Background jobs / queue tasks](#8-background-jobs--queue-tasks)
9. [Frontend component structure](#9-frontend-component-structure)
10. [API response formats](#10-api-response-formats)
11. [Naming conventions](#11-naming-conventions)
12. [Import / export patterns](#12-import--export-patterns)

---

## 1. API route / endpoint structure

Routes are registered in `routes/api.php` as flat `Route::<verb>('/path', [Controller::class, 'method'])` declarations. All controllers are imported with `use` at the top of the file; there is **no** `apiResource` / RESTful resource grouping — every endpoint is spelled out explicitly, and the controller method name is free-form (not tied to REST verbs).

**Real example — `routes/api.php:50-56`:**

```php
use App\Http\Controllers\ServicesController;

Route::middleware('auth:sanctum')->group(function ()
{
    Route::post('/services/show', [ServicesController::class, 'showById']);
    Route::put('/services/{id}', [ServicesController::class, 'update']);
    Route::post('/services', [ServicesController::class, 'store']);
    Route::get('/services', [ServicesController::class, 'get_services']);
    Route::post('/services/bulkupload', [ServicesController::class, 'addbulkservices']);
    Route::delete('/services/{id}', [ServicesController::class, 'destroy']);
    Route::get('/getServicesBySpace', [ServicesController::class, 'getServicesBySpace']);
});
```

**Conventions to follow:**
- One controller per resource (`ServicesController`, `ProductsController`, …). Group its routes together with a `// comment` header.
- The `{id}` route parameter is passed positionally to the controller method (`update(Request $request, $id)`); model-route binding is **not** used.
- The default `api/` prefix is applied automatically (so `/services` is reachable at `POST api/services`). Do not add another `/api` prefix.

⚠️ Inconsistencies already in the file: route method names mix styles (`get_services`, `showById`, `addbulkservices`), some URIs are malformed (`/broadcast/show{id}` is missing a slash), and several routes are declared more than once — the **last** definition silently wins. Prefer one clear definition and a consistent verb-noun method name.

---

## 2. Database queries (ORM)

Eloquent is the only data layer — **no raw SQL**, no query builder against tables directly except through models. The dominant read pattern is a tenant-scoped `where(...)->first()` / `->get()`; writes go through `Model::create([...])`. Mass assignment relies on each model's `$fillable`.

**Real example — `app/Http/Controllers/ServicesController.php:33-58`:**

```php
// Scope every lookup to the authenticated client (multi-tenant guard)
$space = Space::where('id', $validated['space_id'])
                ->where('client_id', $client_id)
                ->first();

$service = Service::create([
    ...$validated,
    'client_id' => $client_id,
    'image'     => $imagePath,
    'currency'  => $currency,
]);
```

**Model side — `app/Models/Service.php`:**

```php
class Service extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'space_id', 'name', /* … */ 'is_featured'];

    protected $casts = [
        'available_days' => 'array',   // JSON columns cast to array
        'ai_tags'        => 'array',
        'is_active'      => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($service) {           // auto-fill uuid on insert
            if (empty($service->uuid)) {
                $service->uuid = (string) Str::uuid();
            }
        });
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }
}
```

**Conventions to follow:**
- **Always scope queries by `client_id`** (the tenant) for owned resources — never trust an incoming id alone.
- Declare `$fillable` and `$casts` on every model; cast JSON columns to `array` and flags to `boolean`.
- UUIDs are generated in a `booted()` `creating` hook, not in the controller.
- Relationships are explicit methods (`belongsTo` / `hasMany`) with the FK named.
- Wrap multi-step writes (file upload + insert) in a `DB::transaction` (see §3).

---

## 3. Error handling

The pattern is a **per-method `try/catch (\Exception $e)`** that returns a JSON error with an HTTP status code, paired with `DB::beginTransaction()` / `commit()` / `rollBack()` for writes. Validation is done first with `$request->validate([...])` (which throws a 422 automatically before the try block). There is **no** global exception handler customization and the dead `Traits/ApiResponse` helper is **not** used — every controller builds the envelope inline.

**Real example — `app/Http/Controllers/ServicesController.php:43-76`:**

```php
DB::beginTransaction();
try {
    $imagePath = null;
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        if ($image->isValid()) {
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('services', $imageName, 'public');
        }
    }

    $service = Service::create([ ...$validated, 'client_id' => $client_id ]);

    DB::commit();
    return response()->json([
        'success' => true,
        'message' => 'Service Created Successfully!',
        'service' => $service,
    ], 200);
} catch (\Exception $e) {
    DB::rollBack();
    if ($imagePath && Storage::disk('public')->exists($imagePath)) {
        Storage::disk('public')->delete($imagePath);   // clean up orphaned upload
    }
    return response()->json([
        'success' => false,
        'message' => 'Service Creation failed!',
        'error'   => $e->getMessage(),
    ], 500);
}
```

**Conventions to follow:**
- Validate up front with `$request->validate([...])`; let the framework return 422.
- Guard ownership/existence with explicit `if (!$model) return response()->json([...], 403|404)` checks.
- Wrap writes in a transaction; on failure `rollBack()` **and** undo side effects (e.g. delete a just-stored file).
- Return `$e->getMessage()` under an `error` key on 500s. ⚠️ This leaks internal messages to clients — acceptable in this codebase today, but consider gating it behind `config('app.debug')` for production-sensitive endpoints.
- Background code logs instead of returning (see §8): `\Log::error(...)`, `\Log::info(...)`.

---

## 4. Auth & middleware on routes

Authentication is **Laravel Sanctum bearer tokens**, applied **per-block** with `Route::middleware('auth:sanctum')->group(...)` — not globally. The authenticated principal is the **`Client`** model (the default `api` guard's provider), retrieved with `$request->user()` or `Auth::user()`. Admin routes add a `prefix` + a named middleware alias.

**Authenticated block — `routes/api.php:44-49`:**

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/account_profile', [ClientsController::class, 'account_profile']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // … all owner-facing endpoints live inside this group
});
```

**Admin block — `routes/admin.php:6-24`:**

```php
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);
});
```

**Reading the authenticated user inside a controller** (`ServicesController.php:32` and `:82`):

```php
$client_id = $request->user()->id;   // preferred
// or
$client = Auth::user();
if (!$client) return response()->json(['message' => 'Unauthenticated'], 401);
```

**Conventions to follow:**
- Put any endpoint that acts on owned data **inside** the `auth:sanctum` group.
- Use `$request->user()->id` to get the tenant id, then scope all queries by it.
- Middleware aliases are registered in `bootstrap/app.php` (e.g. `'admin' => AdminMiddleware`).

⚠️ Known gaps (do **not** copy): the `admin` middleware (`AdminMiddleware::handle`) is currently a **no-op**, so admin routes are protected only by `auth:sanctum` — any logged-in client reaches them. A large block of payment / phone-keyed / DelayDog / complaint routes sits **after** the closing `}` of the auth group in `api.php` and is therefore **public**. New admin logic must add a real role check; new owner-facing routes must go *inside* the auth group.

---

## 5. Environment variables & config

Two patterns coexist:
1. **`config/services.php` → `config('services.…')`** — the idiomatic Laravel way; keys read `env()` *once* at config time.
2. **Direct `env('KEY')` calls scattered in controllers/jobs/services** — common in this codebase but discouraged by Laravel (breaks `php artisan config:cache`).

**Config definition — `config/services.php:37-41`:**

```php
'stripe' => [
    'key'    => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'ordiio_secret_key' => env('ORDIIO_STRIPE_SECRET_KEY'),
],
```

**Direct `env()` read — `app/Jobs/VerifyPaystackPayment.php:26`:**

```php
$response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
    ->get("https://api.paystack.co/transaction/verify/{$this->reference}");
```

**Conventions to follow:**
- **Prefer** adding a key to `config/services.php` and reading it via `config('services.x.y')`. Only the config file should call `env()`.
- Provide a fallback for non-secret values: `env('AWS_DEFAULT_REGION', 'us-east-1')`.
- Every new third-party key **must** be added to `.env.example` (many current keys are missing — see CODEBASE_AUDIT §9).

⚠️ Do not reference config keys that don't exist: `services.stripe.webhook_secret` and `services.whapi.token` are read in code but **absent** from `config/services.php`, so they silently resolve to `null`. Add the key to the config file before referencing it.

**Frontend** reads exactly one variable, always via `process.env`:

```ts
// app/Apis/publicapi.tsx:1034
export const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL;
```
Client-exposed vars **must** be prefixed `NEXT_PUBLIC_`. ⚠️ Several functions hardcode `https://api.joincroose.com` instead of `BASE_URL` — always use `BASE_URL`.

---

## 6. Module / feature folder organization

The backend uses Laravel's **standard type-based layout** (group by *kind*, not by feature). A feature is spread across sibling directories that share the resource name:

```
app/
├── Http/Controllers/ServicesController.php   ← request handling + validation
├── Models/Service.php                        ← Eloquent model ($fillable, $casts, relations)
├── Http/Requests/  (Form Requests)           ← optional extracted validation (rarely used)
├── Http/Resources/ (API Resources)           ← optional output transformer (rarely used)
└── Services/       (business/integration)    ← e.g. MessageService, BroadcastService
routes/api.php                                ← wires the controller methods to URIs
database/migrations/2025_06_30_102301_services.php
```

Cross-cutting integration logic lives in `app/Services/` (e.g. `MessageService` for WHAPI, `StripeWebhookHandler`, `GoogleCalendarService`), and these are instantiated/injected by controllers. Sub-products get a **namespace subfolder**: `app/Http/Controllers/Admin/`, `app/Http/Controllers/API/`, `app/Services/Ordiio/`.

**Conventions to follow:**
- New resource = a `XxxController` + an `Xxx` model + a migration + route lines, named consistently.
- Put reusable third-party/business logic in `app/Services/`, not in the controller.
- Group a sub-product's classes under a sub-namespace folder (mirror `Admin/`, `API/`, `Ordiio/`).

⚠️ Form Requests and API Resources exist but are barely used (only `BroadcastResource`, 3 request classes) — the rest validate inline. `app1/`, `app2/`, `app.zip` are dead backup copies — never add to them.

---

## 7. Tests

Framework is **PHPUnit** (config in `phpunit.xml`), two suites: `tests/Unit` and `tests/Feature`. The test environment uses an in-memory SQLite DB, array cache/session, sync queue. Run with `php artisan test`. ⚠️ Only the two scaffold tests below currently exist — there is effectively no coverage; treat these as the *template* for new tests.

**Unit test (no framework boot) — `tests/Unit/ExampleTest.php`:**

```php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;   // plain PHPUnit base, no Laravel

class ExampleTest extends TestCase
{
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

**Feature / integration test (boots the app, makes HTTP calls) — `tests/Feature/ExampleTest.php`:**

```php
namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;  // enable to reset DB per test
use Tests\TestCase;               // Laravel base TestCase

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
```

**Conventions to follow:**
- **Unit tests** extend `PHPUnit\Framework\TestCase` (pure, fast, no DB/app).
- **Feature tests** extend `Tests\TestCase`, use `$this->get/post(...)` and `$response->assert*`.
- Method names are `snake_case` starting with `test_…` (the `void` return type is declared).
- For tests touching the DB, uncomment `use RefreshDatabase;` so the in-memory schema is migrated per test.
- **Frontend** has no test runner configured (only a `lint` script, with no ESLint config present).

---

## 8. Background jobs / queue tasks

Jobs implement `ShouldQueue` and use the standard Laravel job traits. The **`database`** queue driver is used (no Redis/Horizon). Constructor captures serializable inputs (an id/reference, never a full model is required); `handle()` does the work and **logs** outcomes rather than returning a response. Run the worker with `php artisan queue:listen`.

**Real example — `app/Jobs/VerifyPaystackPayment.php`:**

```php
namespace App\Jobs;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

class VerifyPaystackPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reference;

    public function __construct($reference)      // pass the lightweight key, not the model
    {
        $this->reference = $reference;
    }

    public function handle()
    {
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get("https://api.paystack.co/transaction/verify/{$this->reference}");

        if ($response->successful()) {
            $data  = $response->json()['data'];
            $order = Order::where('payment_reference', $this->reference)->first();
            if (!$order) {
                \Log::error("Order not found for reference: {$this->reference}");
                return;
            }
            $order->payment_status = $data['status'] === 'success' ? 'paid' : 'failed';
            $order->save();
        } else {
            \Log::error("Verification failed for reference: {$this->reference}");
        }
    }
}
```

**Conventions to follow:**
- `implements ShouldQueue` + the four standard traits.
- Constructor takes a **reference/id**; re-fetch the model inside `handle()`.
- Use the `Http` facade for outbound calls; log `info`/`warning`/`error` for each branch; `return;` early on missing data.
- Dispatch with `VerifyPaystackPayment::dispatch($reference);`.

⚠️ `SendWhatsAppTextJob` is an empty stub — not a template. Scheduled work (`RunBroadcastCron`) is **not** auto-registered (the only schedule lives in the ignored legacy `Http/Kernel.php`); register cron schedules in `routes/console.php` or via an external cron.

---

## 9. Frontend component structure

Components are **typed functional components** with an explicit `Props` interface, written as `React.FC<Props>`, styled with **Tailwind utility classes** inline, and exported as **`default`**. Shared components live in `app/(private)/components/` and `app/(public)/component/`. Pages live in `page.tsx` files inside the App Router tree and are marked `"use client"` when they use state/hooks.

**Real example — `app/(private)/components/ConfirmationModal.tsx`:**

```tsx
import React from "react";

interface ConfirmationModalProps {
    isOpen: boolean;
    title: string;
    message: string;
    onConfirm: () => void;
    onCancel: () => void;
}

const ConfirmationModal: React.FC<ConfirmationModalProps> = ({
    isOpen, title, message, onConfirm, onCancel,
}) => {
    if (!isOpen) return null;          // early-return guard for conditional render

    return (
        <div className="fixed inset-0 flex items-center justify-center z-[60]" onClick={onCancel}>
            <div className="bg-white rounded-2xl shadow-xl w-[400px] p-6"
                 onClick={(e) => e.stopPropagation()}>
                <h3 className="text-lg font-semibold text-[#0F172A] mb-3">{title}</h3>
                <button onClick={onConfirm}
                        className="px-6 py-2.5 bg-[#685BC7] text-white rounded-xl">
                    Yes
                </button>
            </div>
        </div>
    );
};

export default ConfirmationModal;
```

**Conventions to follow:**
- Define an `interface XxxProps`, type the component `React.FC<XxxProps>`, destructure props in the signature.
- Guard conditional rendering with an early `if (!isOpen) return null;`.
- Style with Tailwind classes inline; brand purple is `#685BC7`. Colors are frequently hardcoded hex via `text-[#…]`.
- `export default` one component per file; file name matches the component (PascalCase or lowercase, see §11).
- Add `"use client";` at the top of any component/page that uses hooks, state, or `localStorage`.

---

## 10. API response formats

There is **no single envelope** — two shapes coexist. The most common is `{ success, message, ... }`; a parallel `{ status, message, data }` shape also appears across older controllers. The data payload key is **ad-hoc** (named after the resource, e.g. `service`, not a generic `data`). HTTP status codes carry the real success/failure signal.

**Success (200) — `ServicesController.php:60-65`:**

```php
return response()->json([
    'success'   => true,
    'message'   => 'Service Created Successfully!',
    'service'   => $service,                                   // resource-named key
    'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
], 200);
```

**Error — same controller, different statuses:**

```php
return response()->json(['message' => 'Unauthenticated'], 401);                       // :84
return response()->json(['message' => 'Service not found or unauthorized'], 403);     // :88
return response()->json([                                                              // :71-75
    'success' => false,
    'message' => 'Service Creation failed!',
    'error'   => $e->getMessage(),
], 500);
```

**Conventions to follow (and recommended canonical shape for new code):**
- Always set the correct **HTTP status code** (200/401/403/404/422/500) — clients rely on it.
- Always include a human-readable `message`.
- For new endpoints, prefer the `{ success: bool, message: string, data: … }` shape and put the payload under a consistent key. (Existing code uses resource-named keys and mixes `success`/`status` — match the surrounding controller when editing it, but standardize new modules.)
- Put the exception text under `error` on 500s only.

**Frontend** unwraps with `response.data` and surfaces `error.response.data.message`:

```ts
// app/Apis/publicapi.tsx:1068-1072
} catch (error: any) {
    throw new Error(error?.response?.data?.message || error.message || 'Something went wrong');
}
```

---

## 11. Naming conventions

Conventions are **inconsistent across the codebase**; the table below states the *dominant / recommended* convention and notes the deviations you will encounter.

| Thing | Convention (use this) | Real examples | ⚠️ Deviations in repo |
|---|---|---|---|
| **PHP class / controller file** | `PascalCase`, suffix `Controller` | `ServicesController.php`, `AdminController.php` | `paymentController.php`, `Stripe.php`, `Ordiio_settings_controller.php` |
| **Eloquent model** | `PascalCase` singular | `Service`, `Client`, `Space` | snake_case models exist: `space_iq`, `subscription_items`, `Ordiio_transaction`, `Licensed_track` |
| **Controller method** | `camelCase`, verb-led | `showById`, `getProductBySpace` | `snake_case` mixed in: `get_services`, `account_profile`, `addbulkservices` |
| **DB table** | `snake_case` plural | `services`, `products`, `client_customer` | `space_iqs`, capitalized `Ordiio_transactions` |
| **DB column** | `snake_case`; FKs `<entity>_id`; UUID col `uuid` | `client_id`, `space_id`, `payment_status`, `uuid` | — |
| **API path** | lowercase, usually `/snake_case` or single word | `/account_profile`, `/getServicesBySpace`, `/services/bulkupload` | mixes snake_case, camelCase, and kebab (`/reset-password` **and** `/reset_password` both exist) |
| **Route param** | `{snake_case}` or `{id}` | `/services/{id}`, `/orders/{client_phone}/{customer_phone}` | — |
| **TS component** | `PascalCase` for shared UI | `ConfirmationModal.tsx`, `StatusBadge.tsx` | many lowercase: `croosehq.tsx`, `spaceiq.tsx`, `setting1.tsx` |
| **TS API function** | `camelCase` verb-led | `createSpace`, `getUserStatus`, `findAccountByEmail` | some PascalCase: `InstanceActivationStatus`, `RunAgentInfo`, `PayApi` |
| **TS interface** | `PascalCase`, suffix `Props` for component props | `ConfirmationModalProps`, `AxiosOptions` | — |
| **Next.js page file** | always `page.tsx` (App Router) | `dashboard/home/page.tsx` | — |

**Rule of thumb for new code:** PHP classes/models `PascalCase`; methods `camelCase`; tables/columns `snake_case`; React components `PascalCase`; TS functions/vars `camelCase`. Pick **one** casing for a new API path family and stay consistent.

---

## 12. Import / export patterns

### Backend (PHP)
- **Namespaces are PSR-4**: `App\` → `app/` (declared in `composer.json`). Every file declares its `namespace`, every dependency is a top-of-file `use`.
- Facades imported by FQN `use` (`use Illuminate\Support\Facades\DB;`) **or** referenced with a leading backslash inline (`\Log::error(...)`, `\Str::uuid()`) — both appear. Prefer the explicit `use` import.
- Controllers/models are pulled into route files with `use` (see §1).

```php
// app/Http/Controllers/ServicesController.php (top)
namespace App\Http\Controllers;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
```

### Frontend (TypeScript / React)
- **Path alias `@/*` → project root** (`tsconfig.json: "paths": { "@/*": ["./*"] }`). Import shared modules via the alias, not long relative `../../..` chains.
- **API functions are named exports**; import only what you use:

```ts
// app/(public)/login/page.tsx
import { loginApi, verifyToken } from '@/app/Apis/publicapi';

// app/(private)/customisespace/page.tsx
import { createSpace } from '@/app/Apis/publicapi';
```

- **React components are default exports** (`export default ConfirmationModal;`), imported as `import ConfirmationModal from '@/app/(private)/components/ConfirmationModal';`.
- Third-party libs use their documented import style (`import React from "react"`, `import axios, { AxiosRequestConfig } from 'axios'`).

**Conventions to follow:**
- New shared helpers/components → import through the `@/` alias.
- API-client functions → **named** export from `app/Apis/publicapi.tsx`.
- UI components → **default** export, one per file.

⚠️ `app/Apis/publicapi.tsx` re-declares `import axios …` and `export const BASE_URL` partway down the file (line ~1032) after ~960 lines of commented-out code; new code should still import `BASE_URL`/`axiosRequest` as named exports from this module, but the file is overdue for cleanup (see CODEBASE_AUDIT §11).
