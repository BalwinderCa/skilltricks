<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

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
            \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\CurrencyMiddleware::class,
            \App\Http\Middleware\AffiliateReferral::class,
            \App\Http\Middleware\IsInMaintenance::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'customer' => \App\Http\Middleware\IsCustomer::class,
            'isBanned' => \App\Http\Middleware\IsBanned::class,
            'affiliate' => \App\Http\Middleware\AffiliateMiddleWare::class,
            'demo' => \App\Http\Middleware\DemoMiddleware::class,
            'ensureDemoMode' => \App\Http\Middleware\EnsureDemoMode::class,
            'frontendAllow' => \App\Http\Middleware\IsFrontendEnable::class,
            // Spatie Permissions v6
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
