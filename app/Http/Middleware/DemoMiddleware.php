<?php

namespace App\Http\Middleware;

use Closure;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;

class DemoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (env('DEMO_MODE') == "On") { 
            if($request->ajax()){
                return response()->json([
                    'status'    => 200,
                    'success'   => false,
                    'demo'      => true,
                    'message'   => localize('This is turned off in demo')
                ]);
            }else{
                flash(localize('This is turned off in demo mode'))->warning();
                return back();
            }
        } else {
            return $next($request);
        }
    }
}
