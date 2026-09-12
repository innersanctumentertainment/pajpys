# PAJPYS — Post A Job / Post Your Services

**pajpys.com** is a Caribbean-first marketplace where people **post jobs** and **post services** — professional services, creative work, technical help, and more.

## What PAJPYS does

- **Post a Job** — Clients pay a **TTD $20 posting fee** per job (USD optional via admin), then fund the job budget through secure escrow
- **Post Your Services** — Providers list services for free (creative, technical, professional, and more)
- **Job providers** — Verification, job acceptance, agreements, and completion workflow
- **15% withdrawal fee** — Unified platform fee on all wallet withdrawals
- **WiPay payments** — TTD, USD, GBP with idempotent webhooks
- **Financial ledger** — Immutable double-entry accounting

## Requirements

- PHP 8.3+, Composer, Node.js 20+, SQLite (production) or MySQL (optional)

## Local Setup (Windows — same pattern as Tactile / QuickInvoice)

```powershell
cd C:\Users\KyleGospel\Projects\pajpys
.\tools\install-local.ps1
.\tools\serve.ps1
```

Open **http://127.0.0.1:8095**

First-time migration from Cursor Cloud? Run `webs\setup-local-machine.ps1` — it clones into `Projects\pajpys` and copies handoff docs to `Desktop\webs\pajpys`.

Handoff prompt for new Cursor chats: `Desktop\webs\pajpys\MASTER_PROMPT.md`

### Linux / macOS

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve --port=8095
```

**Admin:** `admin@pajpys.com` / `password`

## Key routes

| Route | Purpose |
|-------|---------|
| `/` | Landing page |
| `/services` | Browse posted services |
| `/client/jobs` | Post and manage jobs |
| `/provider/services` | Post and manage services |
| `/va/jobs/discover` | Provider job marketplace |
| `/admin/dashboard` | Admin command center |

## Testing

```bash
php artisan test
```

## Deployment (pajpys.agapetech.org)

PAJPYS deploys to **https://pajpys.agapetech.org/** using the same GitHub Actions + Hostinger TUS upload workflow as [Tactile](https://github.com/innersanctumentertainment/tactile), [CompoCompre](https://github.com/innersanctumentertainment/compo-compre), and [QuickInvoice](https://github.com/innersanctumentertainment/quickinvoice).

### GitHub repository

`https://github.com/innersanctumentertainment/pajpys`

### Required GitHub secrets

| Secret | Description |
|--------|-------------|
| `HOSTINGER_API_TOKEN` | Same token used by your other Hostinger deploy workflows |
| `APP_ENV_B64` | Base64-encoded production `.env` (see `.env.production.example`) |
| `DEPLOY_SECRET` | Random string for deploy/migrate HTTP endpoints (must match `DEPLOY_SECRET` in `.env`) |

Legacy `ENV_B64` is still read by the deploy script if `APP_ENV_B64` is not set.

Generate `APP_ENV_B64`:

```bash
base64 -w0 .env.production > /tmp/env.b64   # Linux
# paste contents into GitHub secret APP_ENV_B64
```

### Hostinger setup

1. Push to `main` or run **Deploy to Hostinger** workflow manually
2. The deploy script creates subdomain `pajpys.agapetech.org` → `public_html/pajpys/public` if missing
3. Production uses **SQLite** at `database/database.sqlite` (skipped on deploy so data persists)

## Cron (production)

On Hostinger, add a cron job every minute:

```
* * * * * cd ~/domains/agapetech.org/public_html/pajpys && php artisan schedule:run >> /dev/null 2>&1
```
