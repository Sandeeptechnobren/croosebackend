# resources/views/ — Blade templates

## Purpose
Server-rendered Blade pages, used **only** for payment redirect/landing flows and transactional emails — not for the main app UI (that is the separate Next.js `croose_frontend`). Returned by `routes/web.php` and `app/Mail/*` mailables.

## Key files
- `welcome.blade.php` — default Laravel landing page served at `GET /`.
- `payment/` — generic payment success/cancel pages (`payment.success`, `payment.cancel`).
- `payments/` — payment option pages for orders/appointments/instances/subscriptions (`/payment/{type}/{uuid}`).
- `payments_ordiio/` — Ordiio-specific success/cancel pages (`ordiio_success`, `ordiio_cancel`). ⚠️ Some `web.php` routes to these reference a missing `OrdiioPaymentsController`.
- `billing/` — billing-related views.
- `emails/` — email bodies rendered by `app/Mail/SendOtpMail` and `ResetPasswordMail`.

## Data flow
`routes/web.php` → controller/closure → `return view('payments.xxx', [...])` → Blade renders HTML. Mailables (`app/Mail/*`) render `emails/*` into the message body.

## Dependencies
- **Depends on:** `routes/web.php`, `app/Mail/*`, Vite-built assets in `resources/css` + `resources/js` (`vite.config.js`).
- **Depended on by:** Stripe/Paystack redirect URLs (post-checkout landing) and OTP/reset emails.

## Conventions
- Standard Blade (`@extends`, `@section`, `{{ }}`). Assets via `@vite([...])`.
- This is a thin presentation layer; business logic stays in controllers/services.

## Common commands
```
npm run dev      # vite dev server for Blade assets (from repo root)
npm run build    # production asset build
```
