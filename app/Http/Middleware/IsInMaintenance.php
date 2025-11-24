<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsInMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            if (Auth::user()->user_type == 'customer') {
                if (getSetting('enable_maintenance_mode') == 1) {
                    abort(503);
                }
            }
        }

        return $next($request);
    }
}
