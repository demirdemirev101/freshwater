<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private function cartSessionId(Request $request): ?string
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

        $rememberedSessionId = Session::get('cart_session_id');

        return is_string($rememberedSessionId) && trim($rememberedSessionId) !== ''
            ? trim($rememberedSessionId)
            : null;
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = null;

        if (isset($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $sessionId = $this->cartSessionId($request);

        Auth::setUser($user);

        if (! empty($sessionId)) {
            Log::info('Login with cart session id', [
                'user_id' => $user->id,
                'cart_session_id' => $sessionId,
            ]);

            (new CartService($sessionId))->mergeGuestCartToUser();
        } else {
            Log::info('Login without cart session id', [
                'user_id' => $user->id,
            ]);
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
            'email' => 'email|required|max:255|unique:users,email',
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
            Log::info('Register with cart session id', [
                'user_id' => $user->id,
                'cart_session_id' => $sessionId,
            ]);

            (new CartService($sessionId))->mergeGuestCartToUser();
        } else {
            Log::info('Register without cart session id', [
                'user_id' => $user->id,
            ]);
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
