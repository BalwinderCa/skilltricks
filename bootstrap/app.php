<?php

use App\Http\Middleware\AffiliateMiddleWare;
use App\Http\Middleware\AffiliateReferral;
use App\Http\Middleware\CurrencyMiddleware;
use App\Http\Middleware\DemoMiddleware;
use App\Http\Middleware\EnsureDemoMode;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsBanned;
use App\Http\Middleware\IsCustomer;
use App\Http\Middleware\IsFrontendEnable;
use App\Http\Middleware\IsInMaintenance;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(__DIR__.'/../routes/backend.php');
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global and web-specific middleware
        $middleware->web(append: [
            LanguageMiddleware::class,
            CurrencyMiddleware::class,
            AffiliateReferral::class,
            IsInMaintenance::class,
        ]);

        // Payment webhooks authenticate by provider signature, not by session CSRF, so
        // they must be exempt. app/Http/Middleware/VerifyCsrfToken.php still lists these
        // in $except, but Laravel 12 never registers that class, so the list had no
        // effect and both endpoints answered 419 to every real callback.
        $middleware->validateCsrfTokens(except: [
            'webhooks/paypal',
            'webhooks/stripe',
        ]);

        // Route middleware aliases
        $middleware->alias([
            'admin' => IsAdmin::class,
            'customer' => IsCustomer::class,
            'isBanned' => IsBanned::class,
            'affiliate' => AffiliateMiddleWare::class,
            'demo' => DemoMiddleware::class,
            'ensureDemoMode' => EnsureDemoMode::class,
            'frontendAllow' => IsFrontendEnable::class,
            // Spatie Permissions v6
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
