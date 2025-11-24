<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsBanned
{
    public function handle($request, Closure $next)
    {
        if (isLoggedIn() && isCustomer() && auth()->user()->is_banned) {

            $redirect_to = "home";

            auth()->logout();

            flash(localize("You have been banned"))->error();

            return redirect()->route($redirect_to);
        }

        return $next($request);
    }
}
