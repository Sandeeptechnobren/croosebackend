# Project Overview

Croose is a multi-tenant WhatsApp commerce/CRM platform. A business owner ("Client") registers, creates "Spaces" (storefronts, each with its own WhatsApp channel, catalog, and an AI chatbot persona), and manages customers, orders, appointments, subscriptions, and broadcast messaging. End customers interact via WhatsApp (the WHAPI gateway) and phone-keyed public endpoints; Google Gemini powers AI replies. Two partial sub-products also live in the repo: **Ordiio** (music licensing) and **DelayDog** (rail-delay claims). This repo is the **Laravel 12 API** (`croosebackend`); the Next.js dashboard is a sibling repo at `../croose_frontend`.

# Tech Stack

- **Backend:** Laravel 12, PHP 8.3 (required transitively by `phpoffice/phpspreadsheet` → zipstream)
- **Auth:** Laravel Sanctum bearer tokens — default guard `api`, provider `clients` → `App\Models\Client`
- **ORM/DB:** Eloquent; SQLite by default (MySQL/Postgres also configured). Queue, cache, and session all use the `database` driver
- **Frontend (`../croose_frontend`):** Next.js 15 (App Router, Turbopack), React 19, TypeScript, axios, Formik, Tailwind v4 + MUI v7
- **Integrations:** Stripe, Paystack, WHAPI (WhatsApp), Google Gemini, Google Calendar, SourceAudio/Airtable (Ordiio)
- **Tests:** PHPUnit. **Assets:** Vite (Blade), Next build (frontend)

# Architecture

Apache → root `index.php` → `bootstrap/app.php` → `routes/api.php` (+`admin.php`) → Controllers → Eloquent → JSON. Sanctum auth is applied per route-block, not globally. See **docs/ARCHITECTURE.md** for details.

# Directory Structure

- `app/` — application code (Controllers, Models, Services, Jobs, Events, Middleware)
- `routes/` — `api.php`, `admin.php`, `web.php`, `channels.php`, `console.php`
- `config/` — 15 framework/service config files
- `database/` — migrations (~55), seeders, factories
- `resources/views/` — Blade payment/email templates (Vite assets in `resources/css|js`)
- `tests/` — PHPUnit `Unit/` + `Feature/`
- `public/` — Apache docroot assets
- `docs/` — ARCHITECTURE, PATTERNS, CODEBASE_AUDIT, ACCESS, DEPLOY_LOG, SSH_CONFIG
- `tasks/` — todo.md, lessons.md
- ⚠️ `app1/`, `app2/`, `app.zip` — dead backup copies; never edit

# Key Commands

Backend uses the PHP 8.3 binary: `C:\php83\php.exe -c "C:\php83\php.ini" artisan ...`
- Install: `composer install`
- Dev server: `php artisan serve` (http://127.0.0.1:8000)
- Migrate: `php artisan migrate` · Status: `php artisan migrate:status`
- Queue worker: `php artisan queue:listen --tries=1`
- Test: `php artisan test`
- Blade assets: `npm run dev` / `npm run build`

Frontend (`../croose_frontend`): `npm install` · `npm run dev` (http://localhost:3000) · `npm run build && npm run start` · `npm run lint`

Deploy: no CI/CD pipeline exists; shared-hosting/cPanel layout (repo root = webroot). See docs/ACCESS.md & docs/DEPLOY_LOG.md.

# Coding Conventions

- PHP: `PascalCase` classes/models, `camelCase` methods, `snake_case` tables/columns, FK `<entity>_id`, PSR-4 `use` imports
- React/TS: `PascalCase` components (default export), `camelCase` API functions (named export), path alias `@/*`
- Always scope multi-tenant queries by `client_id`. Validate first, then `try/catch` with a DB transaction for writes
- See docs/PATTERNS.md for the canonical shapes

# Patterns

See **docs/PATTERNS.md** for examples with real code.

# Testing

PHPUnit. Run `php artisan test` (or `--filter=Name`). Tests live in `tests/Unit/` (pure) and `tests/Feature/` (boots the app, in-memory SQLite). ⚠️ Only scaffold tests exist today. The frontend has no test runner configured.

# Task Management
- Before starting work, write plan to tasks/todo.md
- Track progress by marking items complete
- After ANY correction or mistake, update tasks/lessons.md with a rule that prevents it
- After completing work, add entry to CHANGELOG.md

# Git Workflow
- Always create a feature branch: feature/[your-name]/[short-description]
- Never commit directly to main
- Every PR must have a clear description of what changed and why
- Run tests before pushing
- Request review from at least one team member

# Important Rules
- NEVER modify code without an approved plan
- NEVER skip tests
- ALWAYS check docs/PATTERNS.md before creating new patterns
- ALWAYS update CHANGELOG.md with your changes
- Do not touch: .claude/skills/ (third-party), node_modules/, vendor/, .git/, dist/, build/, .env files; and project-specific: app1/, app2/, app.zip (dead backups)

# Frontend Design Rules (Impeccable)
- For ANY frontend/UI work, run `/impeccable audit` after `/review` and `/impeccable polish` before final commit
- Never use Inter, Arial, Roboto, or system fonts as primary typeface — pick distinctive fonts
- Never use pure gray — always tint neutrals toward the brand color
- Never nest cards inside cards
- Never use gray text on colored backgrounds — check contrast
- Never use purple gradients as default — commit to a project-specific color palette
- Never use bounce/elastic easing — it feels dated
- See .claude/skills/impeccable/ for full design reference

# Bulk Operation Safety
- NEVER run bulk find-and-replace (sed, grep -rl | xargs) without excluding: .claude/skills/, node_modules/, vendor/, .git/, dist/, build/, package-lock.json, composer.lock, yarn.lock, bun.lock
- Safe bulk rename pattern: grep -rl 'old-name' --exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=.git --exclude-dir=dist --exclude-dir=build --exclude-dir=.claude/skills . | xargs sed -i 's/old-name/new-name/g'
- ALWAYS show the list of files that will be affected BEFORE running any bulk operation
- ALWAYS ask for confirmation before executing bulk changes

# Security Rules (Enforced on Every Task)
- NEVER store auth tokens in localStorage — use httpOnly cookies only (⚠️ current frontend uses localStorage; flagged in docs/CODEBASE_AUDIT.md — fix forward)
- NEVER return stack traces, file paths, or SQL errors in API responses
- NEVER build SQL queries with string concatenation — always use parameterized queries (Eloquent bindings)
- NEVER commit .env files or hardcode secrets in source code
- NEVER serve user-uploaded files without MIME type and size validation
- ALWAYS filter by tenant id (here: `client_id`) in multi-tenant database queries
- ALWAYS verify webhook signatures (Stripe, Paystack, etc.)
- ALWAYS rate-limit API endpoints (especially auth, payment, and public routes)
- ALWAYS validate environment variables at app startup
- ALWAYS add server-side validation — client-side validation is not security
- ALWAYS use HTTPS — no mixed content allowed
- ALWAYS hash passwords with bcrypt/argon2 — never store plaintext

# Deployment Rules
- Server: Claude can deploy and test autonomously. For destructive DB ops (DROP, TRUNCATE, DELETE without WHERE), show command and wait for APPROVED.
- ALWAYS backup database before running migrations — no exceptions
- NEVER auto-fix without circuit breaker: max 3 auto-fix cycles, then STOP and report
- If circuit breaker fires, run /rollback — NEVER leave a broken deployment live
- /rollback checks database migration state before reverting code — code-only rollback with forward-migrated DB corrupts data
- Log every deployment to docs/DEPLOY_LOG.md
- Before ANY operation touching 5+ files, show the file list and wait for APPROVED

# Testing Rules
- Every new API endpoint MUST have at least one automated test
- Every new database query in multi-tenant projects MUST have a tenant isolation test
- Tests must verify behavior (what SHOULD happen), not just implementation
- Human defines WHAT to test, AI writes HOW to test — never let AI decide test scope alone
- Run /test before every push — no exceptions
- After human QA finds a bug, add a regression test so it's caught next time
- Test categories: tenant isolation, API contracts, business logic, integration, security
- For human QA test cases, run /generate-qa-sheet before release

# Subdirectory Docs
- Backend: `app/`, `app/Http/Controllers/`, `app/Http/Middleware/`, `app/Models/`, `app/Services/`, `app/Jobs/`, `app/Events/`, `app/Console/Commands/`, `routes/`, `database/`, `config/`, `tests/`, `resources/views/` — each has a CLAUDE.md
- Frontend (`../croose_frontend`): `app/`, `app/Apis/`, `app/(private)/`, `app/(public)/` — each has a CLAUDE.md

# Context Window Budget
- Root CLAUDE.md: under 150 lines (this file)
- Each subdirectory CLAUDE.md: under 80 lines
- tasks/lessons.md: prune entries older than 30 days to an archive file
- docs/explorations/: delete explorations older than 2 weeks (re-explore if needed)
- .claudeignore: keeps build artifacts, dependencies, and large data out of context
- When context feels heavy: run /compact to compress conversation history
- One task per session — start fresh, don't carry over context from previous tasks
