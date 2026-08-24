# Browser tests (Laravel Dusk)

The PHPUnit suite covers the onboarding endpoints but never executes the
interview page's JavaScript. The interview in
`resources/views/backend/pages/onboarding.blade.php` is a fetch loop: it posts an
answer, receives the next question, and rewrites the DOM. A page whose script
fails to load passes every PHPUnit test while leaving users unable to answer a
single question — that exact defect (`@section('script')` against a layout that
yields `scripts`) occurred during development and was invisible to 135 green
tests.

These Dusk tests exist to close that gap. They are the only tests in the repo
that prove the interview actually works in a browser.

## One-time setup

```bash
composer install                       # laravel/dusk is a dev dependency
cp .env.dusk.example .env.dusk.local
php artisan key:generate --env=dusk.local
touch database/dusk.sqlite             # then set DB_DATABASE to its absolute path
php artisan dusk:chrome-driver --detect
```

`.env.dusk.local` is gitignored. It is commonly copied from `.env`, which carries
real credentials, so it must never be committed. `.env.dusk.example` lists the
only keys Dusk actually needs.

## Running

```bash
php artisan serve                      # in one terminal
php artisan dusk                       # in another
```

Run a single test with `php artisan dusk --filter test_name`.

## The fake AI provider

Dusk runs the application in a **separate process** from the test, so the
container binding the PHPUnit tests use —
`$this->instance(AiProviderService::class, $mock)` — cannot reach it.

Instead, `AI_PROVIDER=fake` selects a branch in
`App\Services\AI\AiProviderService::generate()` that returns canned responses in
the same normalised shape the real providers return, so `extractText()` and
`parseJson()` work unchanged. It returns a fixed follow-up question when
`$jsonMode` is false, and a fixed profile at rank 50 when it is true.

That branch is guarded to `local`, `testing`, and `dusk` environments, so a
config typo in production cannot serve canned answers to real users.

## What is covered

| Test | What it proves |
| --- | --- |
| `test_an_uncalibrated_customer_is_routed_to_a_working_interview` | The gate redirects to a page that actually renders its controls |
| `test_the_interview_advances_through_to_the_confirmation_card` | The JS loaded, the fetch fired, and the DOM updated — the dead-page check |
| `test_confirming_completes_calibration` | The real button writes the calibration to the database |
| `test_a_calibrated_user_is_not_returned_to_the_interview` | Re-calibration is gated, and `?recalibrate=1` still works |
| `test_a_failed_answer_request_surfaces_an_error_rather_than_undefined` | A non-OK response shows an error instead of rendering `undefined` |

## Notes for anyone extending these

- **Dusk reuses one browser session across a test class.** Onboarding stores
  progress in the session, so a previous test's state leaks into the next unless
  cookies are cleared before each `loginAs()`. The existing tests do this.
- `setUp()` uses `migrate:fresh` rather than the `DatabaseMigrations` trait.
- Prefer `waitForText` / `waitUntilMissing` over fixed sleeps.

## Not wired into CI

CI would need Chrome and a served application in the workflow. That is a separate
decision; `.github/workflows/ci.yml` currently runs PHPStan, `composer audit`,
the security suite, and PHPUnit only.
