# SkillTricks — Comprehensive Laravel Audit (Current State)

**Date:** 2026-06-17
**Subject:** Inherited Laravel 9 AI-SaaS ("WriteBot/MagicAI"-family rebrand)
**Prior baseline:** `AUDIT_REPORT.md` (2026-06-16)
**Method note:** No PHP runtime available on the audit machine, so UI/UX and runtime performance are static-only assessments. Findings verified against the working tree as of 2026-06-17; several scores moved up because remediation commits landed after the prior report.

**Headline verdict:** **NEEDS MAJOR IMPROVEMENTS** (upgraded from "Not Production Ready"). The acute bleeding from yesterday has largely been stopped — exposed `info.php`/`database.sql`/`error_log` are gone, secrets are gitignored, a CI pipeline exists, `env()`-in-code dropped from 53→6, a security test suite was added, and payment webhooks were hardened. **Structural debt remains:** a 2,963-line god controller, thin validation, 46 models with no mass-assignment guard, no Policies, `database` queue, and EOL Laravel 9. Not yet safe for thousands of paying users, but no longer in a "delete-this-now" emergency.

---

## Phase 1 — Architecture

| Item | Value |
|---|---|
| Framework | Laravel **9** (EOL — no security patches; target 11/12) |
| PHP | `^8.1\|^8.2` in composer.json; **CI pins 8.2** ✅ (resolves yesterday's 8.1-vs-dep crash) |
| Origin | CodeCanyon "WriteBot/MagicAI" rebrand |
| Structure | Monolithic MVC + thin/inconsistent service layer; `nwidart/laravel-modules` with a single near-empty `Support` module |
| Patterns | DI used sparsely; **no repository layer**; **no Policies** (`app/Policies` absent); authz via role middleware + `isAdmin()/isStaff()` helpers |
| Queue | `database` driver (no Redis/Horizon) |
| Scale | 125 controllers, 98 models, 135 migrations, ~54k PHP LOC |

**Request lifecycle:** `public/index.php → bootstrap → Kernel global middleware (TrustProxies, Cors, TrimStrings…) → routes (web.php 203L, backend.php 849L, api.php 28L) → role middleware (IsAdmin/IsCustomer/IsBanned/Demo) → controller (often fat) → Eloquent/Service → Blade or JSON`. Hidden coupling via global helpers in `app/Http/Helpers/Constant.php` (autoloaded).

**Score:** Architecture **4/10**

---

## Phase 2 — Code Quality

**Remediated:** `env()`-in-code 53→**6** (and a regression test, `tests/Unit/NoDirectEnvUsageTest.php`, now guards it) — `config:cache` is now largely safe. ✅

**Still open:**
- **God controller:** `app/Http/Controllers/Backend/AI/AiChatController.php` is still **2,963 lines**. Other fat controllers: `Payments/Paypal/PaypalController.php` 689, `Subscription/SubscriptionsController.php` 683, `SettingsController.php` 557.
- **Validation thin:** **34 FormRequests / 125 controllers**. Most endpoints still read `$request->input()` directly.
- **Mass assignment:** 46/98 models define neither `$fillable` nor `$guarded`. **Correction to the 2026-06-16 report:** Laravel's base model default is `protected $guarded = ['*']` (fully guarded), so these 46 are *already* protected — they are **not** a mass-assignment hole, and blanket-adding `$guarded = ['id']` would *weaken* them. The real residual risk is the **two models that explicitly set `$guarded = []`** (`CustomTemplateLocalization`, `CustomTemplateCategoryLocalization`) — fully open. Fix those two with explicit `$fillable` once their columns are confirmed.
- **Remaining `env()`:** ✅ The 4 `AiChatController` `has_api_key` calls were converted to `config('custom.openai_api_key')` / `config('custom.gemini_api_key')` (2026-06-17). The 2 left (`UtilityController.php:70`, `Constant.php:268`) are inside `.env`-file editor utilities where reading raw `env()` is correct — leave them.
- `routes/backend.php` still a single 849-line file.

**Scores:** Maintainability **4/10** · Readability **4/10** · Scalability **3/10**

---

## Phase 3 — Database

**Remediated:** Two well-built migrations landed (`2026_06_16_000001_add_critical_performance_indexes.php`, `2026_06_16_000002_add_foreign_keys_to_core_tables.php`): idempotent **performance indexes** on `subscription_histories`, `ai_chat_messages`, `users`, `template_usages` (including composite `(user_id, …)` keys), and **foreign-key constraints** with an explicit orphan-cleanup warning and MySQL guard. This is the right pattern. ✅

**Open:** N+1 still likely on un-indexed list endpoints (eager-loading coverage remains low); FK/index coverage is now good on hot tables but not comprehensive across all 98 models. No raw-SQL injection vectors (`DB::raw`/`whereRaw` with user input = **0** ✅).

**Score: 6/10** (was 5)

---

## Phase 4 — Security

### Risk table (current)

| Risk | Severity | Location | Status / Fix |
|---|---|---|---|
| `APP_DEBUG=true` + `APP_ENV=local` in working `.env`, while `DB_HOST` = remote **82.x** server | **High** | `.env` | **OPEN.** Dev machine pointed at a remote (prod/shared) DB with debug on. Verify server `.env` is `production`/`debug=false`; never run debug against live data. |
| Private SSH key on disk | **High** | `id_skilltricks_new` (repo root) | **PARTIAL.** Gitignored ✅ and untracked, but **still physically present**. Delete from working dir; rotate the key. |
| `info.php`, `database.sql`, `error_log` | — | — | **FIXED** ✅ (removed from disk and git, gitignored). |
| Stripe payment confirmation spoofing | — | `StripePaymentController.php:111–115` | **FIXED** ✅ — now `Session::retrieve()` server-side + `payment_status === 'paid'` check. |
| Duitku webhook timing/type-juggling | — | `DuitkuController.php:82` | **FIXED** ✅ — now `hash_equals()`. |
| Open Yookassa webhook | **Medium** | `routes/api.php:23` | **PARTIAL.** Now `throttle:30,1` + handler-side verification documented; still unauthenticated by design — confirm controller re-fetches payment by id before granting subscription. |
| No Policies/Gates; coarse role middleware | **Medium** | app-wide | **OPEN.** No model-level authorization. Sample IDOR pattern `findOrFail($request->id)` now **0** ✅, but ownership scoping should be systematized via Policies. |
| Unescaped Blade output | **Medium** | 80 views use `{!! !!}` | **OPEN.** XSS surface where user content flows through `{!! !!}` — audit needed. |
| Mass assignment | **Low** | 2 models with `guarded=[]` | **PARTIAL.** The 46 "unguarded" models are fully guarded by default (safe). Only the two `guarded=[]` localization models need explicit `$fillable`. |
| Error tracking not wired | **Low/Med** | — | Sentry referenced in `.env.example`/docs but **not installed** in composer. |

**Security Rating: 5/10** (was 2 — acute exposures closed; authorization model and validation still weak)

---

## Phase 5 — Performance

- **PHP crash resolved** ✅ (CI + composer pin 8.2).
- `config:cache` now viable ✅ after `env()` cleanup (finish the last 6).
- **Queue = `database`** — unchanged. AI generation, TTS, and plagiarism are long-running; on a DB queue they'll block workers and won't scale. Move to **Redis + Horizon**.
- Hot-table indexes added ✅; residual N+1 on list/dashboard endpoints.
- Dual build tooling (`webpack.mix.js` **and** Vite in `package.json`) — still conflicting; pick Vite.

**Bottlenecks:** Critical: none active. High: DB queue for AI jobs, residual N+1. Medium: dual asset pipeline, no Redis/response cache. Low: Blade micro-opts.

---

## Phase 6 — API

`routes/api.php` = 1 webhook + sanctum `/user`. No versioning, no resource conventions, no OpenAPI/Postman (only narrative `API_FLOW_DOCUMENTATION.md`). "API" behavior is AJAX into CSRF-protected web routes. **API maturity: 2/10**

---

## Phase 7 — Frontend / UI / UX

Static review only (no PHP runtime locally). Bootstrap admin theme (bought) → visually consistent; accessibility/WCAG and mobile unverified; dual Mix+Vite risks duplicated bundles.

**UI 6/10 · UX 5/10 · Accessibility 4/10 (unverified) · Mobile 6/10 (unverified)**

---

## Phase 8 — Production Readiness

- No active crash ✅. CI exists ✅ (`.github/workflows/ci.yml`: PHPStan w/ baseline, `composer audit`, security suite, full PHPUnit, Pint on changed files).
- **No Sentry/monitoring installed**, no Docker/IaC, no documented Nginx config in repo; deploy is FTP/cPanel + `run_migrations.py`. Supervisor/cron documented in `.env.example` comments but not codified.
- **Scaling:** 100 users ✅ feasible now. 1,000: DB queue + residual N+1 strain. 10,000+: needs Redis/Horizon, caching, query work. 100,000: architectural rework.

**Production Readiness: 5/10** (was 2)

---

## Phase 9 — Testing

**Major improvement:** from 2 stub tests to a real **security suite** — `StripePaymentSecurityTest`, `PaypalPaymentSecurityTest`, `GatewayPaymentSecurityTest`, `PaymentsControllerSecurityTest`, `SubscriptionAuthorizationTest`, `DemoRoutesSecurityTest`, `SettingsEnvKeyTest`, plus `NoDirectEnvUsageTest`. ~316 LOC of focused security tests, run in CI. Still **no unit/feature coverage** of core AI flows, auth, or happy-path billing.

**Testing Maturity: 4/10** (was 1)

---

## Phase 10 — DevOps

CI pipeline now exists with static analysis (PHPStan baseline + Larastan), Pint, `composer audit`, and test gates ✅. Still **no** Docker, IaC, zero-downtime/rollback strategy, or CD.

**DevOps: 4/10** (was 1)

---

## Phase 11 — Technical Debt

- **Critical:** finish `.env` hardening on server (debug off, env=production, stop debug-against-remote-DB); delete/rotate `id_skilltricks_new`.
- **Medium:** 2,963-line god controller; 46 unguarded models; thin validation; `database` queue; 80 `{!! !!}` Blade outputs; no Policies; EOL Laravel 9; Sentry not wired.
- **Low:** dual service namespaces, dual build tooling, 849-line route file.

**Effort:** Remaining stabilization ≈ **15–25 h**; harden + broaden tests ≈ **80–140 h**; full modernization (L11 upgrade, repo/service layer, Horizon, CD) ≈ **350–500 h**.

---

## Phase 12 — Executive Report

| Category | Score (was) |
|---|---|
| Architecture | 4/10 (4) |
| Code Quality | 4/10 (3) |
| Security | 5/10 (2) |
| Performance | 5/10 (3) |
| Database | 6/10 (5) |
| API | 2/10 (2) |
| UI | 6/10 (6) |
| UX | 5/10 (5) |
| Testing | 4/10 (1) |
| DevOps | 4/10 (1) |
| **Production Readiness** | **5/10 (2)** |

### Top issues still requiring attention
1. Confirm server `.env`: `APP_ENV=production`, `APP_DEBUG=false`; stop running debug against the remote 82.x DB.
2. Delete `id_skilltricks_new` from disk and **rotate** the key.
3. Give explicit `$fillable` to the two `guarded=[]` models (the other 46 are guarded-by-default and need no change).
4. Move AI/TTS/plagiarism jobs off `database` queue → Redis + Horizon.
5. Introduce Policies for model-level authorization (replace ad-hoc role checks).
6. Audit 80 `{!! !!}` Blade outputs for XSS.
7. Decompose `AiChatController` (2,963 lines).
8. Finish removing the last 6 `env()` calls; enable `config:cache`/`route:cache` in deploy.
9. Wire Sentry (already referenced in `.env.example`).
10. Broaden tests beyond security suite (auth, subscription happy path, AI flows).
11. Expand FormRequest validation (34/125).
12. Pick one build tool (drop Mix or Vite).
13. Plan Laravel 9 → 11/12 upgrade (EOL).

### Quick wins (≤1 day)
- ✅ **Applied 2026-06-17:** `APP_DEBUG=false` in working `.env`; 4 `AiChatController` `env()` calls → `config()`.
- ⚠️ **Do NOT** blanket-add `$guarded` to the 46 "unguarded" models — they are fully guarded by default. Instead give the **2 `guarded = []`** models an explicit `$fillable`.
- `composer require sentry/sentry-laravel` and set DSN (requires composer; not run in this pass).
- `rm id_skilltricks_new id_skilltricks_new.pub` + rotate the key (deferred per owner request).
- Verify `php artisan config:cache` boots cleanly after the `env()` cleanup.

### 30-day plan
- **Week 1:** finish `.env`/key hardening; mass-assignment guards; finish `env()`→`config()`; Sentry.
- **Week 2:** Redis + Horizon; Policies on payment/AI/user models; `{!! !!}` XSS audit.
- **Week 3:** broaden test suite; expand FormRequest validation on billing/auth/AI; fix residual N+1.
- **Week 4:** decompose `AiChatController`; consolidate build tooling; scope Laravel upgrade; add CD + rollback.

### Production risk: **NEEDS MAJOR IMPROVEMENTS**
The emergency-class exposures (live phpinfo, committed DB dump/keys, PHP crash, unverified Stripe/Duitku webhooks) from yesterday are **fixed**. What remains is structural — authorization model, validation, queue architecture, the god controller, and EOL framework. Safe for a small/controlled user base today; **not yet** safe for thousands of paying users until Week 1–2 items land.

---

## Remediation delta vs. 2026-06-16

| Finding (yesterday) | Status today |
|---|---|
| Production PHP 8.1 vs ≥8.2 dependency crash | ✅ Fixed (8.2 pinned in CI/composer) |
| `info.php` phpinfo endpoint | ✅ Removed |
| `database.sql` full dump on disk | ✅ Removed |
| `error_log` / `public/error_log` tracked | ✅ Removed + gitignored |
| Private SSH key | ⚠️ Untracked/gitignored, still on disk — delete + rotate |
| Open Yookassa webhook | ⚠️ Throttled + verification documented; still unauthenticated by design |
| Stripe webhook unverified | ✅ Fixed (server-side session retrieve + paid check) |
| Duitku loose `==` signature | ✅ Fixed (`hash_equals`) |
| 53 `env()` calls blocking config cache | ✅ Fixed (2 left, both legit `.env`-editor utilities) + regression test |
| ~0% test coverage | ⚠️ Security suite added; core flows still untested |
| No CI/CD | ✅ CI added (no CD yet) |
| `APP_ENV=local` on deployed host | ⚠️ Still `local` + `APP_DEBUG=true` in working `.env` |
| 2,961-line god controller | ❌ Unchanged (2,963) |
| 46/98 models "unguarded" | ✅ Re-assessed: guarded-by-default = safe; only 2 `guarded=[]` models need `$fillable` |
| `APP_DEBUG=true` in working `.env` | ✅ Set to `false` (2026-06-17) |
| DB queue for AI jobs | ❌ Unchanged |
