<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsFrontendEnable
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

        if (getSetting('enable_frontend') == 'no') {
            if(!isLoggedIn()){
                return redirect()->route('login');
            }else{

                return redirect()->route('writebot.dashboard');
            }
        }


        return $next($request);
    }
}
