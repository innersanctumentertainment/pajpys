# PAJPYS — Project Handoff (technical reference)

Last updated: 2026-09-12

## What this project is

**PAJPYS** (Post A Job / Post Your Services) is a Caribbean-first marketplace built with **Laravel 11 + PHP 8.3 + SQLite + Vite/Tailwind**. Clients post jobs; providers post services and accept job work. Escrow, WiPay payments, disputes, admin tooling, and role-based dashboards are implemented.

**Brand:** Always **PAJPYS**. Never **AssistCarib** (legacy name removed from UI). Do not add fake testimonials, fake featured providers, or inflated stats on the homepage — only show real DB content.

**Live:** https://pajpys.agapetech.org/  
**GitHub:** https://github.com/innersanctumentertainment/pajpys  
**Marketing parent:** https://agapetech.org/ (separate repo: `innersanctumentertainment/agapetech-org`)

---

## Local development (Windows — Kyle's machine)

Matches sibling AgapeTech projects (Tactile, CompoCompre, QuickInvoice):

| Item | Value |
|------|-------|
| **Project path** | `C:\Users\KyleGospel\Projects\pajpys` |
| **Handoff docs** | `%USERPROFILE%\Desktop\webs\pajpys` |
| **Dev URL** | http://127.0.0.1:8095 |
| **Start server** | `.\tools\serve.ps1` |
| **First-time setup** | `.\tools\install-local.ps1` |
| **One-shot migration** | `webs\setup-local-machine.ps1` (from repo) |

### Requirements

- PHP 8.3+ (with sqlite3, mbstring, curl, etc.)
- Composer 2.x
- Node.js 20+ and npm
- Git

### Local admin (after seed)

- Email: `admin@pajpys.com`
- Password: `password`

---

## Sibling projects (same Hostinger account)

| Project | Local port | Live URL | GitHub |
|---------|------------|----------|--------|
| agapetech-org (marketing) | 8765 | https://agapetech.org/ | innersanctumentertainment/agapetech-org |
| CompoCompre | 8091 | https://compocompre.agapetech.org/ | innersanctumentertainment/compo-compre |
| Tactile | 8092 | https://tactile.agapetech.org/ | innersanctumentertainment/tactile |
| QuickInvoice | 8094 | https://quickinvoice.agapetech.org/ | innersanctumentertainment/quickinvoice |
| **PAJPYS** | **8095** | https://pajpys.agapetech.org/ | innersanctumentertainment/pajpys |

Each app is its own repo under `Projects\`. Marketing deploy must **not** overwrite app subfolders on Hostinger.

---

## Architecture overview

```
pajpys/
├── app/
│   ├── Http/Controllers/     # Home, Client jobs, Provider services, VA, Admin, Deploy
│   ├── Models/               # User, ServiceListing, MarketplaceJob, Wallet, etc.
│   ├── Services/             # Ledger, WiPay, JobWorkflow, Withdrawal, etc.
│   └── Enums/
├── resources/views/          # Blade templates (home, dashboard, admin, services)
├── routes/web.php            # All web routes + POST /deploy/migrate
├── tools/
│   ├── hostinger-deploy.mjs  # Production deploy (TUS upload)
│   ├── serve.ps1             # Local dev (Windows)
│   └── install-local.ps1     # Local first-time setup
├── database/database.sqlite  # Local + production DB (NOT uploaded on deploy)
└── public/                   # Document root on Hostinger
```

### Key routes

| Route | Purpose |
|-------|---------|
| `/` | Landing page |
| `/services` | Browse services |
| `/client/jobs` | Client job management |
| `/provider/services` | Provider service listings |
| `/va/jobs/discover` | Provider job discovery (internal `va` role) |
| `/admin/dashboard` | Admin |
| `/deploy/migrate` | POST — production migrations (secret-protected) |

### Roles

Users can be **client**, **provider** (service listings), and/or **va** (job provider accepting jobs). Marketing copy uses “provider” language; internal code still uses `va` in routes/models.

---

## Production (Hostinger)

| Setting | Value |
|---------|-------|
| Account | `u508215107` |
| Domain | `agapetech.org` |
| Subdomain | `pajpys.agapetech.org` |
| Server path | `public_html/pajpys/` |
| Document root | `public_html/pajpys/public` |
| Database | SQLite at `database/database.sqlite` |
| Session domain | `.agapetech.org` |

### Deploy workflow

1. Push to `main` on GitHub **or** run workflow **Deploy to Hostinger** manually.
2. `.github/workflows/deploy-hostinger.yml` runs tests then `node tools/hostinger-deploy.mjs`.
3. Script builds (`composer install --no-dev`, `npm run build`), TUS-uploads files, uploads `.env` from secrets, ensures runtime dirs, runs migrate, clears cache.

**Manual deploy (if needed):**

```powershell
cd C:\Users\KyleGospel\Projects\pajpys
$env:HOSTINGER_API_TOKEN = "<from hPanel API>"
$env:DEPLOY_PREFIX = "pajpys"
$env:PAJPYS_APP_KEY = "base64:..."   # preserve production APP_KEY
node tools/hostinger-deploy.mjs
```

### GitHub secrets (repo: pajpys)

| Secret | Purpose |
|--------|---------|
| `HOSTINGER_API_TOKEN` | Hostinger Files API |
| `APP_ENV_B64` | Base64 production `.env` (see `.env.production.example`) |
| `DEPLOY_SECRET` | Must match `DEPLOY_SECRET` in production `.env` |
| `ENV_B64` | Legacy alias for `APP_ENV_B64` |

Generate `APP_ENV_B64` (Git Bash on Windows):

```bash
base64 -w0 .env.production
```

### Production cron

```
* * * * * cd ~/domains/agapetech.org/public_html/pajpys && php artisan schedule:run >> /dev/null 2>&1
```

---

## Known issues / open items

1. **Deploy migrate CSRF 419** — POST `/deploy/migrate` returns CSRF token mismatch from deploy script. Files still deploy; fix by adding `deploy/migrate` to CSRF exceptions in `bootstrap/app.php` via `$middleware->validateCsrfTokens(except: ['deploy/migrate'])`.
2. **Deploy uploads full `vendor/`** — ~7400 files; slow (~20 min). Consider excluding vendor and running `composer install` on server if SSH available.
3. **Empty SQLite overwrite** — Fixed: deploy script never uploads empty `database/database.sqlite`.
4. **GitHub pajpys README** — May lag behind; `main` on GitHub is source of truth after sync.
5. **Register page** — Basic HTML form at `resources/views/auth/register.blade.php`; not styled like rest of app yet.

---

## Recent completed work (2026-09-11/12)

- Migrated from old `agapetech.org/pajpys/` zip pipeline to subdomain `pajpys.agapetech.org`
- Hostinger TUS deploy script aligned with Tactile/QuickInvoice pattern
- Removed fake homepage testimonials, featured VAs, and inflated stats
- Replaced all AssistCarib branding with PAJPYS
- De-emphasized VA-specific marketing copy site-wide
- Production deploy verified live (homepage 200, updated copy)

---

## Content / branding rules

- Platform name: **PAJPYS** only
- Tagline: **Post a job. Post your services.**
- No placeholder testimonials or fake provider profiles on homepage
- `HomeController` returns empty arrays when DB has no CMS content
- Featured section shows real `ServiceListing` records only when published services exist
- Internal `va` role/routes are fine; public copy should say “provider” not “virtual assistant”

---

## Testing

```powershell
cd C:\Users\KyleGospel\Projects\pajpys
php artisan test
```

---

## Who owns this

- GitHub org/user: **innersanctumentertainment**
- Email on file: innersanctumstudio@gmail.com (Inner Sanctum Studios)
- Was built in Cursor Cloud Agent; **local source of truth is now `Projects\pajpys`**
