# Access & Credentials Guide

Where to find everything needed to run, deploy, and operate Croose. This file
records **what** each resource is and **where** it lives — never the secret
values themselves. Anything marked `[FILL IN]` must be completed by the tech
lead. **Never paste real tokens, passwords, or private keys into this file.**

## Source Repositories
| Repo | URL |
|------|-----|
| Backend (Laravel 12 API) | https://github.com/Sandeeptechnobren/croosebackend.git |
| Frontend (Next.js 15 dashboard) | https://github.com/Sandeeptechnobren/croose_frontend.git |

> 🔒 If a GitHub Personal Access Token was ever shared in plaintext, **revoke it
> immediately** in GitHub → Settings → Developer settings → Tokens and issue a
> new one. Tokens must never be committed or stored in this repo.

## Local Development Database
| Item | Value |
|------|-------|
| Engine (default) | SQLite |
| File | `database/database.sqlite` |
| Run migrations | `php artisan migrate` (PHP 8.3 binary — see CLAUDE.md) |
| Backup before migrating | copy `database/database.sqlite` first — no exceptions |

## Third-Party Services
For each: who owns the account / where the dashboard is / where the key lives.
| Service | Used for | Account owner / dashboard |
|---------|----------|---------------------------|
| Stripe | Card payments (SDK) | [FILL IN] |
| Paystack | Payments (raw HTTP, webhook-verified) | [FILL IN] |
| WHAPI (`gate.whapi.cloud`) | WhatsApp gateway | [FILL IN] |
| Google Gemini (`gemini-2.5-flash`) | AI chatbot replies | [FILL IN] |
| Google Calendar | Appointment sync | [FILL IN] |
| SourceAudio | Ordiio music licensing | [FILL IN] |
| Airtable | Ordiio data | [FILL IN] |

All service keys are read from `.env` (see `.env.example` for the key names).

## Servers
| Environment | Host / IP | User | Notes |
|-------------|-----------|------|-------|
| Production | [FILL IN] | [FILL IN] | Frontend references `api.joincroose.com` and IP `68.183.108.227` — **observed in source, unverified.** Confirm before use. |
| Staging | [FILL IN] | [FILL IN] | |

SSH connection details and key setup: see [docs/SSH_CONFIG.md](SSH_CONFIG.md).
Deployment history: see [docs/DEPLOY_LOG.md](DEPLOY_LOG.md).

## Deployment Tooling
| Item | Value |
|------|-------|
| CI/CD pipeline | None — manual deploy (shared-hosting / cPanel layout, repo root = webroot) |
| Deploy method | [FILL IN — git pull / rsync / cPanel git, etc.] |

## Contacts
| Role | Name | Contact |
|------|------|---------|
| Tech lead | [FILL IN] | [FILL IN] |
| DevOps / hosting | [FILL IN] | [FILL IN] |
| On-call | [FILL IN] | [FILL IN] |
