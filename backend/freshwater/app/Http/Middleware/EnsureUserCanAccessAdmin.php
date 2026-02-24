<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserCanAccessAdmin
{
    /**
     * Handle an incoming request, ensuring the user has the appropriate role to access admin routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        //there is no logged in user, let the auth middleware handle it
        if (! $user) {
            return $next($request);
        }

        // the user is logged in but does not have the required role, abort with 403
        if (! $user->hasAnyRole(['admin', 'superadmin'])) {
            abort(403);
        }

        return $next($request);
    }
}
