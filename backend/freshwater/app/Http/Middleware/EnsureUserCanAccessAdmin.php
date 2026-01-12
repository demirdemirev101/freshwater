<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserCanAccessAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // още няма логнат user → оставяме Filament да покаже login
        if (! $user) {
            return $next($request);
        }

        // логнат е, но няма право
        if (! $user->hasAnyRole(['admin', 'superadmin'])) {
            abort(403);
        }

        return $next($request);
    }
}
