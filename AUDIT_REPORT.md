# SkillTricks (writebot-laravel) — Comprehensive Technical & Business Audit

**Date:** 2026-06-16
**Auditor role:** Senior Laravel Architect / DevOps / Security / Performance / QA / UX
**Subject:** Inherited Laravel 9 SaaS application (AI content/chat platform)
**Verdict (headline):** **NOT PRODUCTION READY** — production is currently crashing on a PHP version mismatch, secrets/keys and a DB dump are exposed on disk, there is effectively **no automated test coverage**, **no CI/CD**, and a 2,961-line god controller. This is a purchased/rebranded Envato codebase ("writebot-laravel") with heavy technical debt.

---

## Phase 1 — Project Understanding

### Stack
| Item | Value |
|---|---|
| Framework | Laravel **9.52.20** (EOL — Laravel 9 security support ended; current LTS is 11/12) |
| PHP requirement | `^7.4 \|\| 8.1` in composer.json; **production server runs 8.1.33** |
| Origin | Envato/CodeCanyon "WriteBot / MagicAI"-family rebrand (initial commit literally `writebot-laravel`) |
| Frontend build | **Mixed** — `webpack.mix.js` (Laravel Mix) present AND Vite configured in `package.json` (`vite ^8`). Conflicting toolchains. |
| Modules | `nwidart/laravel-modules` with a single `Support` module |
| Queue | `database` driver (no Redis/Horizon) |
| Cache/session | per `.env` (Redis available but `env()` misuse undermines config cache) |

### Architecture
- **Monolithic MVC** with a thin, inconsistent service layer (`app/Services` + `app/Http/Services` — two parallel service namespaces, no convention).
- **No repository pattern**, no domain layer. Business logic lives in controllers and a few fat services.
- **No Policies** (`app/Policies` does not exist). Authorization is done with role middleware (`IsAdmin`, `IsCustomer`, `IsBanned`) and helper functions (`isAdmin()`, `isStaff()`).
- Payment integrations: ~15 gateways (Stripe, PayPal, Paystack, Flutterwave, Midtrans, Mollie, Razorpay, Paytm, Mercadopago, Yookassa, Duitku, Iyzico...). Massive surface area.
- AI integrations: OpenAI, Anthropic, DeepSeek, Vertex, ElevenLabs, HeyGen, Azure, Stable Diffusion.

### Request lifecycle (text)
```
HTTP → public/index.php → bootstrap/app.php → Kernel (global middleware:
  TrustProxies, TrustHosts, Cors, PreventRequestsDuringMaintenance, TrimStrings, ...)
→ route (routes/web.php 11.5KB, routes/backend.php 72KB!, routes/api.php tiny)
→ route middleware group (web/auth + IsAdmin/IsCustomer/demo/...)
→ Controller (often fat; AiChatController = 2,961 lines)
→ inline business logic / Service / Eloquent
→ Blade view OR JSON
```
**Component relationships:** Controllers depend directly on Eloquent models and on Services inconsistently; helpers (`app/Http/Helpers`) provide global functions used everywhere (hidden coupling).

---

## Phase 2 — Code Quality Audit

### Controllers — **fat, inconsistent**
- **131 controllers**, 22,683 LOC. Top offenders:
  - `app/Http/Controllers/Backend/AI/AiChatController.php` — **2,961 lines** (god object).
  - `Subscription/SubscriptionsController.php` 673, `Payments/Paypal/PaypalController.php` 668, `faq/FaqsController.php` 537, `SettingsController.php` 530.
- Business logic embedded in controllers (AI calls, billing, file handling).
- **Validation is inconsistent**: only **32 Form Request** classes vs **131 controllers**; only **7 controllers** call `validate()` inline. Most endpoints rely on raw `$request->input()` with no validation.
- Response format not standardized (mix of redirects, `response()->json`, raw arrays).

### Models — **mass-assignment exposure**
- **98 models**. **46 of them define neither `$fillable` nor `$guarded`** → Laravel 9 default is fully guarded only if `$guarded=[]` is NOT set, but many use `Model::create($request->all())` patterns. Combined with missing validation this is a real mass-assignment risk.
- 2 models explicitly set `$guarded = []` (fully open mass assignment).
- Eager loading thin: only **18** `with()` usages across 131 controllers → strong indicator of **widespread N+1**.

### Services / Repositories / Traits
- Two service folders (`app/Services`, `app/Http/Services`) — no clear boundary. SOLID weakly followed.
- **No repository layer.**
- Traits present (`app/Traits`) — verify for hidden state.

### Anti-patterns confirmed
- **53 `env()` calls inside `app/`** — breaks `php artisan config:cache` (returns null in production once cached). Major.
- `routes/backend.php` is a **72KB single file** — unmaintainable routing.

**Scores:** Maintainability **3/10** · Readability **4/10** · Scalability **3/10**

---

## Phase 3 — Database Audit

- **133 migrations** — large schema; many localization side-tables (`*Localization`) suggesting normalized i18n (reasonable).
- A **1.8 MB `database.sql` dump is committed to the working tree** (now gitignored but present on disk → contains real user data + secrets).
- N+1 risk high (see Phase 2). Index review not auto-verifiable here, but with 98 models and 18 eager loads, expect missing FKs/indexes on hot paths (chat messages, projects, usage logs).
- `DemoController.php:83` runs `DB::select('SHOW TABLES')` — fine (no user input) but indicates demo-reset logic touching whole DB.

**Recommendations:** add FK constraints + composite indexes on `(user_id, created_at)` for chat/usage tables; audit slow query log; add eager loading to list endpoints. **Estimated 30–60% latency reduction** on dashboard/list pages once N+1 fixed.

**Score: 5/10**

---

## Phase 4 — Security Audit

### 🔴 CRITICAL — files exposed
| Risk | Severity | Location | Fix |
|---|---|---|---|
| **phpinfo() endpoint live & git-tracked** | **Critical** | `info.php` (tracked since initial commit) | Delete file; never deploy phpinfo. |
| **Private SSH key on disk** | **Critical** | `id_rsa_skilltricks` (+ `.pub`) in repo root | Remove from disk; **rotate the key** (assume compromised). Gitignored but physically present in deploy dir. |
| **Full DB dump on disk** | **Critical** | `database.sql` (1.8 MB) | Delete; rotate any secrets it contains; purge from any backups. |
| **Production error log committed** | **High** | `error_log`, `public/error_log`, `config/error_log` (git-tracked) | Remove from git history; `public/error_log` may be web-served — leaks stack traces/paths. |
| **`.env` with live API keys on disk** | **High** | `.env` (Stripe/PayPal/AWS/Paystack/Flutterwave/Yookassa secrets) | Gitignored ✅ but ensure server perms 600 and not under webroot. |

### Authentication / Authorization
- Laravel UI + Sanctum installed. Custom `LoginController` (504 lines) and `RegisterController` (432) — oversized, review for rate-limit/lockout.
- **No Policies/Gates** — authz via role middleware + `isAdmin()/isStaff()` helpers. Coarse-grained. **195** `@can/can()/Gate` hits are mostly blade permission flags, not model-level authorization.
- **IDOR risk:** ownership scoping is inconsistent. Good: `GenerateImagesController:297`, `GenerateSdImagesController:346` scope by `user_id`. Bad pattern: `GenerateT2SController:126/192` `TextToSpeech::findOrFail($request->id)` with no `user_id` check → user can act on another user's resource. Audit every `findOrFail($request->...)`.

### Payments / Webhooks
- **`routes/api.php`: `POST /youkassa/process` has NO auth and NO rate limiting** — open payment-processing endpoint.
- **Duitku webhook** (`DuitkuController.php:82`) verifies signature with loose `==` and non-constant-time compare → timing/type-juggling risk. Use `hash_equals()` and strict `===`.
- **Stripe webhook signature verification not found** in `Payments/Stripe` (`constructEvent`/`STRIPE_WEBHOOK_SECRET` absent in grep) → webhooks may be **unverified/spoofable**. Confirm and fix.

### Input security
- Validation coverage thin (32 FormRequests / 131 controllers). XSS risk in any blade using `{!! !!}` with user content (audit needed). Mass assignment as above.
- SQL injection: **low** — only 2 raw-query files, no user interpolation found. ✅

### Config
- **`APP_ENV="local"` while `APP_URL=https://staging.skilltricksinc.com`** — wrong environment in a deployed app (verbose errors, dev behavior). `APP_DEBUG="false"` ✅.

**Security Rating: 2/10**

---

## Phase 5 — Performance Audit

- **🔴 PRODUCTION IS CRASHING:** `error_log` shows repeated `PHP Fatal error: Composer detected issues... require PHP ">= 8.2.0". You are running 8.1.33` (Nov 2025). A dependency expects PHP 8.2 but the host runs 8.1.33 → fatal on boot. **#1 priority.**
- `env()` misuse (53×) means **`config:cache` is unsafe** → cannot use the single biggest Laravel perf win.
- Queue = **database** driver; no Horizon/Redis. AI generation, plagiarism, TTS jobs on a DB queue won't scale; long-running AI calls will block workers.
- N+1 across list/dashboard endpoints (Phase 2/3).
- Frontend: dual Mix+Vite — bundles likely unoptimized/duplicated.
- Scheduler runs only `subscription:expire` daily.

**Bottlenecks ranked:** Critical: PHP version crash; config cache disabled. High: N+1, DB queue for AI jobs. Medium: asset pipeline, missing response/Redis cache. Low: blade micro-opts.

---

## Phase 6 — API Audit

- API is **near non-existent**: `routes/api.php` has 2 routes (open Yookassa webhook + sanctum `/user`). No versioning, no resource conventions, no rate limiting beyond defaults, **no OpenAPI/Swagger/Postman** (separate `API_FLOW_DOCUMENTATION.md` exists but is narrative). Most "API-like" behavior is AJAX into web routes (CSRF-protected web group).

**API maturity: 2/10**

---

## Phase 7 — Frontend / UI / UX

> Static-only review (app not booted due to redis ext + PHP mismatch). Qualitative.
- Blade + Bootstrap admin template (typical CodeCanyon theme) — visually consistent because it's a bought theme.
- Dual build tooling risks broken/duplicated assets (recent commits literally fix the frontend build: `c626a7d Fix frontend build`).
- Accessibility/mobile not verified; bought themes are usually responsive but rarely WCAG-compliant.

**UI 6/10 · UX 5/10 · Accessibility 4/10 (unverified) · Mobile 6/10 (unverified)**

---

## Phase 8 — Production Readiness

- **Currently failing in production** (PHP 8.1.33 vs ≥8.2 dep). 
- No Docker, no IaC, no documented Nginx/Supervisor config in repo. `run_migrations.py` (a Python migration runner) suggests ad-hoc/cPanel-FTP deployment (`.ftpquota`, `cgi-bin`, `public_html` paths in logs).
- No error tracking (Sentry/Bugsnag absent), no monitoring, no backup strategy in repo.
- Storage link / queue workers / scheduler cron must be manually ensured.

**Scaling:** 100 users: marginal once crash fixed. 1,000: DB queue + N+1 will hurt. 10,000+: not feasible without Redis/Horizon, caching, query optimization, horizontal scaling. 100,000: architecture rework required.

**Production Readiness: 2/10**

---

## Phase 9 — Testing

- **tests/ contains only the two Laravel stubs** (`Unit/ExampleTest.php`, `Feature/ExampleTest.php`). **Effective coverage ≈ 0%.** No payment, auth, or AI-flow tests. For a billing SaaS this is unacceptable.

**Testing Maturity: 1/10**

---

## Phase 10 — DevOps

- **No `.github/`, no GitLab CI, no Jenkinsfile, no Dockerfile, no docker-compose, no Terraform/Ansible.** Deployment is manual (Python script + FTP/cPanel). No zero-downtime, no rollback strategy. No static analysis (no Pint/Larastan/PHPStan in require-dev).

**DevOps: 1/10**

---

## Phase 11 — Technical Debt

**Critical (do now):** PHP version crash; exposed key/dump/phpinfo/error_log; unverified Stripe webhook + open Yookassa endpoint; `env()`-in-code blocking config cache; zero tests.
**Medium:** 2,961-line god controller; 72KB route file; thin validation; N+1; DB queue; dual build tooling; EOL Laravel 9.
**Low:** dual service namespaces; trait hygiene; naming.

**Refactor effort estimate:** Stabilize (critical) ≈ **40–60 h**. Harden + test foundation ≈ **120–200 h**. Full modernization (Laravel upgrade, repo/service layer, Horizon, CI/CD, test suite) ≈ **400–600 h**.

---

## Phase 12 — Executive Report

### Scores
| Category | Score |
|---|---|
| Architecture | 4/10 |
| Code Quality | 3/10 |
| Security | 2/10 |
| Performance | 3/10 |
| Database | 5/10 |
| API | 2/10 |
| UI | 6/10 |
| UX | 5/10 |
| Testing | 1/10 |
| DevOps | 1/10 |
| Production Readiness | 2/10 |

### Top issues requiring immediate attention
1. Production crashing: PHP 8.1.33 vs dependency requiring ≥8.2 (`error_log`).
2. `info.php` (phpinfo) live and git-tracked.
3. Private SSH key `id_rsa_skilltricks` on disk → rotate.
4. `database.sql` full dump on disk → delete + rotate secrets.
5. `error_log`/`public/error_log` git-tracked & possibly web-served.
6. Open, unauthenticated payment webhook `POST /youkassa/process`.
7. Stripe webhook signature verification missing/unconfirmed.
8. Duitku webhook loose `==` signature compare (use `hash_equals`).
9. `APP_ENV=local` on a deployed (staging) host.
10. 53 `env()` calls in app code → config cache unusable.
11. ~0% automated test coverage on a billing platform.
12. No CI/CD, no Docker, manual FTP deploy, no rollback.
13. IDOR: unscoped `findOrFail($request->id)` (e.g. `GenerateT2SController`).
14. Mass assignment: 46/98 models without `$fillable/$guarded`.
15. Thin validation (32 FormRequests / 131 controllers).
16. N+1 across list/dashboard endpoints (only 18 eager loads).
17. 2,961-line `AiChatController` god object.
18. AI/TTS/plagiarism jobs on `database` queue (no Horizon/Redis).
19. EOL Laravel 9 + dual Mix/Vite build.
20. No error tracking / monitoring / backups.

### Quick wins (≤1 day)
- Delete `info.php`, `database.sql`, `error_log`, `public/error_log`, `config/error_log`; purge from git history; rotate SSH key + exposed API secrets.
- Set `APP_ENV=production` (or `staging`) on the server.
- Add auth + throttle to `/youkassa/process`; switch Duitku compare to `hash_equals`.
- Align PHP version (host to 8.2+, or pin deps to 8.1) to stop the crash.

### 30-day plan
- **Week 1 — Stop the bleeding:** fix PHP version crash; remove exposed files; rotate all secrets; fix env mismatch; secure payment webhooks.
- **Week 2 — Config & data integrity:** replace all in-app `env()` with `config()`; enable `config:cache`/`route:cache`; audit & fix IDOR + mass assignment on payment/AI/user models.
- **Week 3 — Reliability:** move queues to Redis + Horizon; add Sentry; add FormRequest validation to billing/auth/AI endpoints; fix top N+1.
- **Week 4 — Engineering hygiene:** GitHub Actions CI (Pint + Larastan + PHPUnit); write feature tests for auth, subscription, and one payment gateway happy/failure path; document deploy + add rollback.

### Production risk: **NOT PRODUCTION READY**
Active production crash + exposed private key/DB dump/phpinfo + unverified payment webhooks + zero tests. Do not treat as safe for paying users until at least the Week 1–2 items are complete.
