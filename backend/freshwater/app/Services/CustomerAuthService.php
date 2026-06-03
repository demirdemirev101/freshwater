<?php

namespace App\Services;

use App\Models\User;
use App\Support\SensitiveValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CustomerAuthService
{
    public function login(array $credentials, ?string $cartSessionId): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        return $this->authenticate($user, $cartSessionId, 'Login');
    }

    public function register(array $data, ?string $cartSessionId): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        return $this->authenticate($user, $cartSessionId, 'Register');
    }

    public function toUserPayload(User $user): array
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

    private function authenticate(User $user, ?string $cartSessionId, string $context): array
    {
        Auth::setUser($user);

        if (! empty($cartSessionId)) {
            Log::info("{$context} with cart session id", [
                'user_id' => $user->id,
                'cart_session_id_fingerprint' => SensitiveValue::fingerprint($cartSessionId),
            ]);

            (new CartService($cartSessionId))->mergeGuestCartToUser();
        } else {
            Log::info("{$context} without cart session id", [
                'user_id' => $user->id,
            ]);
        }

        return [
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $this->toUserPayload($user),
            'cart' => Auth::check() ? $this->cartPayload(new CartService(null)) : null,
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
