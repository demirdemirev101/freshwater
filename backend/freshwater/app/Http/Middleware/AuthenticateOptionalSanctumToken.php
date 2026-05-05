<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOptionalSanctumToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() && $request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();

            if ($user) {
                Auth::setUser($user);
            }
        }

        return $next($request);
    }
}
