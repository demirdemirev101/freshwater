<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartSessionResolverService
{
    public function rememberFromRequest(Request $request): ?string
    {
        return $this->resolve($request, false);
    }

    public function resolveForAuth(Request $request): ?string
    {
        return $this->resolve($request, true);
    }

    private function resolve(Request $request, bool $fallbackToRemembered): ?string
    {
        $sessionId = $request->input('session_id')
            ?? $request->input('sessionId')
            ?? $request->query('session_id')
            ?? $request->query('sessionId')
            ?? $request->header('X-Cart-Session-Id');

        if (is_scalar($sessionId)) {
            $sessionId = trim((string) $sessionId);

            if ($sessionId !== '') {
                Session::put('cart_session_id', $sessionId);

                return $sessionId;
            }
        }

        if (! $fallbackToRemembered) {
            return null;
        }

        $rememberedSessionId = Session::get('cart_session_id');

        return is_string($rememberedSessionId) && trim($rememberedSessionId) !== ''
            ? trim($rememberedSessionId)
            : null;
    }
}
