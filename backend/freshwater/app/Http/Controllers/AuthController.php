<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private function cartSessionFallbackCacheKey(Request $request): string
    {
        return 'cart-session-fallback:' . sha1(($request->ip() ?? 'unknown') . '|' . (string) $request->userAgent());
    }

    private function cartSessionUserCacheKey(Request $request, string $sessionId): string
    {
        return 'cart-session-user:' . sha1(($request->ip() ?? 'unknown') . '|' . (string) $request->userAgent() . '|' . $sessionId);
    }

    private function cartSessionDebugContext(Request $request): array
    {
        $fields = [
            'sessionId',
            'session_id',
            'cartSessionId',
            'cart_session_id',
        ];

        $body = [];
        $query = [];

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);
                $body[$field] = [
                    'type' => get_debug_type($value),
                    'value' => is_scalar($value) ? (string) $value : null,
                ];
            }

            if ($request->query->has($field)) {
                $value = $request->query($field);
                $query[$field] = [
                    'type' => get_debug_type($value),
                    'value' => is_scalar($value) ? (string) $value : null,
                ];
            }
        }

        return [
            'cart_session_body' => $body,
            'cart_session_query' => $query,
            'cart_session_headers' => [
                'X-Cart-Session-Id' => $request->header('X-Cart-Session-Id'),
                'X-Cart-Session' => $request->header('X-Cart-Session'),
            ],
            'cart_session_fallback' => Cache::store('file')->get($this->cartSessionFallbackCacheKey($request)),
        ];
    }

    private function cartSessionId(Request $request): ?string
    {
        $sessionId = $request->input('sessionId')
            ?? $request->input('session_id')
            ?? $request->input('cartSessionId')
            ?? $request->input('cart_session_id')
            ?? $request->query('sessionId')
            ?? $request->query('session_id')
            ?? $request->query('cartSessionId')
            ?? $request->query('cart_session_id')
            ?? $request->header('X-Cart-Session-Id')
            ?? $request->header('X-Cart-Session')
            ?? Cache::store('file')->get($this->cartSessionFallbackCacheKey($request))
            ?? null;

        if (is_scalar($sessionId)) {
            $sessionId = trim((string) $sessionId);

            return $sessionId !== '' ? $sessionId : null;
        }

        return null;
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'required_without:username', 'email'],
            'username' => ['sometimes', 'required_without:email', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = null;

        if (isset($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();
        } elseif (isset($validated['username']) && Schema::hasColumn('users', 'username')) {
            $user = User::where('username', $validated['username'])->first();
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $sessionId = $this->cartSessionId($request);

        Auth::setUser($user);

        if (! empty($sessionId)) {
            Log::info('Login with cart session id', array_merge([
                'user_id' => $user->id,
                'cart_session_id' => $sessionId,
            ], $this->cartSessionDebugContext($request)));

            (new CartService($sessionId))->mergeGuestCartToUser();
            Cache::store('file')->put(
                $this->cartSessionUserCacheKey($request, $sessionId),
                $user->id,
                now()->addMinutes(30)
            );
        } else {
            Log::info('Login without cart session id', array_merge([
                'user_id' => $user->id,
                'payload_keys' => array_keys($request->except(['password'])),
                'query_keys' => array_keys($request->query()),
                'has_cart_session_header' => $request->hasHeader('X-Cart-Session-Id')
                    || $request->hasHeader('X-Cart-Session'),
            ], $this->cartSessionDebugContext($request)));
        }

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $this->transformUser($user),
            'cart' => Auth::check() ? $this->cartPayload(new CartService(null)) : null,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => 'string|required|max:50',
            'email' => 'email|required|max:255',
            'phone' => 'string|required|max:10',
            'password' => 'string|required|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password'=> Hash::make($validated['password'])
        ]);

        $sessionId = $this->cartSessionId($request);

        Auth::setUser($user);

        if(!empty($sessionId)){
            Log::info('Register with cart session id', array_merge([
                'user_id' => $user->id,
                'cart_session_id' => $sessionId,
            ], $this->cartSessionDebugContext($request)));

            (new CartService($sessionId))->mergeGuestCartToUser();
            Cache::store('file')->put(
                $this->cartSessionUserCacheKey($request, $sessionId),
                $user->id,
                now()->addMinutes(30)
            );
        } else {
            Log::info('Register without cart session id', array_merge([
                'user_id' => $user->id,
                'payload_keys' => array_keys($request->except(['password'])),
                'query_keys' => array_keys($request->query()),
                'has_cart_session_header' => $request->hasHeader('X-Cart-Session-Id')
                    || $request->hasHeader('X-Cart-Session'),
            ], $this->cartSessionDebugContext($request)));
        }
        
        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $this->transformUser($user),
            'cart' => Auth::check() ? $this->cartPayload(new CartService(null)) : null,
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->transformUser($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'city' => $user->city ?? null,
            'address' => $user->address ?? null,
            'postcode' => $user->postcode ?? null,
        ];
    }

    private function cartPayload(CartService $cart): array
    {
        return [
            'session_id' => $cart->getSessionId(),
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ];
    }
}
