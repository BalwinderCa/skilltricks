<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds an invited member on the password form until they replace the temporary
 * password that was emailed to them.
 *
 * A redirect, not a hidden sidebar: the menu is cosmetic, and anyone who knows a
 * URL can type it. This is the part that actually blocks.
 */
class MustChangePassword
{
    /** Reachable while blocked — the form itself, and the way out. */
    private const ALLOWED = [
        'password.change',
        'password.change.store',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        // An XHR following a redirect to an HTML form learns nothing useful, so
        // answer those with a status the caller can act on.
        if ($request->expectsJson()) {
            abort(423, 'A new password must be set before continuing.');
        }

        return redirect()->route('password.change');
    }
}
