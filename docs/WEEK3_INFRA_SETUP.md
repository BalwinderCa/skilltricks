# Week-3 infra setup (server-side) — Queue/Horizon + Sentry

These steps require `composer require`, a running Redis, and server access. They
were **not** applied to the repo automatically because:

- `composer require` would rewrite `composer.json`/`composer.lock`, which is risky
  while the production host runs **PHP 8.1.33** but the lock already requires
  **PHP ≥ 8.2** (the active prod crash from Week-1). Fix the PHP version first.
- Horizon needs Redis running; the local box's `redis` PHP extension is currently
  ABI-mismatched (`Unable to initialize module`), so it can't be verified here.

Do these on the server (or a clean clone) after the PHP version is aligned.

---

## 1. Redis-backed queue + Horizon

Current state: `QUEUE_CONNECTION=database`. AI generation, TTS, plagiarism and
PDF jobs run on the DB queue — fine for low volume, but it polls the DB and does
not scale or give visibility. Move to Redis + Horizon.

```bash
# 1. Ensure PHP redis extension + a Redis server are installed and running.
composer require predis/predis           # or use phpredis (ext-redis)
composer require laravel/horizon
php artisan horizon:install
php artisan migrate                       # horizon has no migration, but ensures schema is current
```

`.env`:
```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis        # or phpredis if the extension is healthy
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
HORIZON_PREFIX=skilltricks_horizon:
```

`config/horizon.php` — set the queues the app actually dispatches to (check
`app/Jobs/*`); a sane starting point:
```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue'      => ['default'],
            'balance'    => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'tries'      => 3,
            'timeout'    => 120,   // raise for long AI calls; keep < Supervisor stopwaitsecs
        ],
    ],
],
```

Supervisor (`/etc/supervisor/conf.d/horizon.conf`):
```ini
[program:skilltricks-horizon]
process_name=%(program_name)s
command=php /home/<path>/artisan horizon
autostart=true
autorestart=true
user=<deploy-user>
redirect_stderr=true
stdout_logfile=/home/<path>/storage/logs/horizon.log
stopwaitsecs=3600
```
```bash
supervisorctl reread && supervisorctl update && supervisorctl start skilltricks-horizon
```

Protect the dashboard: `app/Providers/HorizonServiceProvider.php` `gate()` should
allow only admins (reuse `isAdmin()`), so `/horizon` isn't world-readable.

> Note: any code currently dispatching jobs synchronously (or `dispatchSync`)
> won't benefit until switched to `dispatch()` on the redis connection.

---

## 2. Error tracking — Sentry

No error tracking today (no Sentry/Bugsnag). Add Sentry for Laravel 9:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=<your-dsn>
```

`.env`:
```env
SENTRY_LARAVEL_DSN=<your-dsn>
SENTRY_TRACES_SAMPLE_RATE=0.1        # tune; 1.0 = trace everything (costly)
```

`app/Exceptions/Handler.php` `register()`:
```php
$this->reportable(function (\Throwable $e) {
    if (app()->bound('sentry')) {
        app('sentry')->captureException($e);
    }
});
```

Scrub secrets before sending (the app handles many payment/API keys):
in `config/sentry.php` ensure `send_default_pii => false` and add `before_send`
to strip Authorization headers / token fields.

---

## 3. After PHP is aligned — enable config cache

Once views/admin-settings decision is made (see Week-2), enable in deploy:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Remember to re-run `config:cache` after any `.env`/settings change.
