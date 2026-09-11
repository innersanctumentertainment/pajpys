# PAJPYS — Post A Job / Post Your Services

**pajpys.com** is a Caribbean-first marketplace where people **post jobs** and **post services** — including virtual assistant work, professional services, creative work, and more.

## What PAJPYS does

- **Post a Job** — Clients pay a **TTD $20 posting fee** per job (USD optional via admin), then fund the job budget through secure escrow
- **Post Your Services** — Providers list services for free (VA services, creative, technical, professional)
- **Virtual Assistants** — Full VA marketplace workflow: verification, job acceptance, agreements, completion
- **15% withdrawal fee** — Unified platform fee on all wallet withdrawals
- **WiPay payments** — TTD, USD, GBP with idempotent webhooks
- **Financial ledger** — Immutable double-entry accounting

## Requirements

- PHP 8.3+, Composer, Node.js 20+, MySQL 8+ (or SQLite for dev)

## Local Setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite  # if using SQLite
php artisan migrate --seed
npm run build
php artisan serve --port=43123
```

**Admin:** `admin@pajpys.com` / `password`

## Key routes

| Route | Purpose |
|-------|---------|
| `/` | Landing page |
| `/services` | Browse posted services |
| `/client/jobs` | Post and manage jobs |
| `/provider/services` | Post and manage services |
| `/va/jobs/discover` | VA job marketplace |
| `/admin/dashboard` | Admin command center |

## Testing

```bash
php artisan test
```

## Deployment (agapetech.org/pajpys)

PAJPYS deploys to **https://agapetech.org/pajpys/** using the same GitHub Actions + Hostinger Files API workflow as [agapetech-org](https://github.com/innersanctumentertainment/agapetech-org) and [bemysupporter](https://github.com/innersanctumentertainment/bemysupporter). The marketing site at `agapetech.org` root is untouched.

### GitHub repository

`https://github.com/innersanctumentertainment/pajpys`

### Required GitHub secrets

| Secret | Description |
|--------|-------------|
| `HOSTINGER_API_TOKEN` | Same token used by your other Hostinger deploy workflows |
| `ENV_B64` | Base64-encoded production `.env` (see `.env.production.example`) |
| `DEPLOY_SECRET` | Random string for deploy/migrate HTTP endpoints (must match `DEPLOY_SECRET` in `.env`) |

Generate `ENV_B64`:

```bash
base64 -w0 .env.production > /tmp/env.b64   # Linux
# paste contents into GitHub secret ENV_B64
```

### Hostinger setup

1. Create a MySQL database in hPanel (e.g. `u508215107_pajpys`)
2. Set PHP version to **8.3+** for `agapetech.org`
3. Push to `main` or run **Deploy to Hostinger** workflow manually

Deploy uploads to `public_html/pajpys/` (web) and `public_html/pajpys_app/` (Laravel core), runs migrations via `POST /pajpys/deploy/migrate`, and smoke-tests the live site.

## Cron (production)

On Hostinger, add a cron job every minute:

```
* * * * * cd ~/domains/agapetech.org/public_html/pajpys_app && php artisan schedule:run >> /dev/null 2>&1
```
