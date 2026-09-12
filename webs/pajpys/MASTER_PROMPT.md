# Master prompt — paste this into a new Cursor chat to take over PAJPYS

Copy everything below the line into a new Agent chat when you want to continue work on PAJPYS locally.

---

You are taking over **PAJPYS** (Post A Job / Post Your Services), an existing Laravel marketplace project. Read this entire prompt before making changes.

## Project identity

- **Name:** PAJPYS — Caribbean-first marketplace for jobs and professional services
- **Never use:** AssistCarib (legacy brand, fully removed)
- **Live URL:** https://pajpys.agapetech.org/
- **GitHub:** https://github.com/innersanctumentertainment/pajpys
- **Local path (Windows):** `C:\Users\KyleGospel\Projects\pajpys`
- **Handoff docs:** `%USERPROFILE%\Desktop\webs\pajpys\PROJECT_HANDOFF.md` (read this for full detail)

## Stack

- Laravel 11, PHP 8.3, SQLite (prod + local), Vite, Tailwind, shadcn-style Blade components
- Payments: WiPay (sandbox in default .env)
- Auth: email verification, Google OAuth hooks, 2FA support, Spatie permissions
- Deploy: GitHub Actions + `tools/hostinger-deploy.mjs` (Hostinger TUS API)

## Local workflow (match sibling AgapeTech projects)

```powershell
cd C:\Users\KyleGospel\Projects\pajpys
.\tools\install-local.ps1   # first time only
.\tools\serve.ps1           # daily dev → http://127.0.0.1:8095
```

Sibling ports: CompoCompre 8091, Tactile 8092, QuickInvoice 8094, PAJPYS **8095**, marketing 8765.

Local admin after seed: `admin@pajpys.com` / `password`

## What the app does

- **Clients** post jobs (TTD $20 posting fee default), fund escrow, manage candidates
- **Providers** post service listings for free
- **Job providers** (`va` role internally) discover jobs, accept work, withdraw earnings (15% platform fee)
- **Admin** dashboard for users, verifications, payouts, disputes, settings
- **Public** landing page, services browse, auth flows

## Key files

| Area | Path |
|------|------|
| Homepage + marketing | `resources/views/home.blade.php`, `app/Http/Controllers/HomeController.php` |
| Routes | `routes/web.php`, `routes/auth.php` |
| Deploy script | `tools/hostinger-deploy.mjs` |
| Deploy workflow | `.github/workflows/deploy-hostinger.yml` |
| Production env template | `.env.production.example` |
| Windows dev scripts | `tools/serve.ps1`, `tools/install-local.ps1` |

## Production / Hostinger

- Account `u508215107`, domain `agapetech.org`
- Subdomain `pajpys` → document root `public_html/pajpys/public`
- SQLite at `database/database.sqlite` — **never upload empty DB on deploy** (script skips it)
- GitHub secrets needed: `HOSTINGER_API_TOKEN`, `APP_ENV_B64`, `DEPLOY_SECRET`
- Deploy: push to `main` or run **Deploy to Hostinger** workflow

## Branding & content rules (critical)

1. Platform is **PAJPYS** — no AssistCarib anywhere
2. Do **not** add fake testimonials, fake featured providers, or made-up stats
3. `HomeController` must return **empty arrays** when DB has no real CMS data — no fallbacks with fake names
4. Featured homepage section = real published `ServiceListing` records only, or hidden
5. Marketing copy: “provider” / “marketplace” language — do not over-highlight “virtual assistants” in headers/hero (VA is one service type among many)
6. Internal `va` routes/models/roles stay as-is

## Known open issues

1. **Deploy migrate 419 CSRF** — `POST /deploy/migrate` fails CSRF check from deploy script. Fix: add CSRF exception for `deploy/migrate` in `bootstrap/app.php`. Deploy still uploads files successfully.
2. Deploy is slow (~7400 files including vendor via TUS)
3. Register page is unstyled basic HTML

## Recent history (context)

- Built as Cursor Cloud Agent project, now migrated to local `Projects\pajpys`
- Fixed production 500s from missing SQLite / wrong subdomain root
- Completed branding cleanup and removed fake homepage content (deployed live 2026-09-12)
- Marketing site `agapetech-org` links to https://pajpys.agapetech.org/

## How to work

- Match existing Laravel/Blade conventions in the repo
- Minimize scope — focused diffs only
- Test locally with `php artisan test` before deploy
- Deploy via GitHub Actions or `node tools/hostinger-deploy.mjs` with Hostinger token
- Read `Desktop\webs\pajpys\PROJECT_HANDOFF.md` for architecture, secrets, and sibling project table

## Your first actions in this chat

1. Confirm the repo exists at `C:\Users\KyleGospel\Projects\pajpys` and `git pull origin main`
2. Read `PROJECT_HANDOFF.md` on the desktop if present
3. Ask what task I want done, or continue from my message below

---

**My task for this session:**

[Describe what you want done here]
