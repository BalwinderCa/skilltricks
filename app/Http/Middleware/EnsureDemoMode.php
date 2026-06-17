<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDemoMode
{
    /**
     * Allow demo-only routes only in local environment with demo mode enabled.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! app()->environment('local') || config('custom.demo_mode') !== 'On') {
            abort(404);
        }

        return $next($request);
    }
}
