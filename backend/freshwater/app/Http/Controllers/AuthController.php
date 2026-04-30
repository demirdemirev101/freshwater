<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Database\Seeder;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'required_without:username', 'email'],
            'username' => ['sometimes', 'required_without:email', 'string'],
            'password' => ['required', 'string'],
            'sessionId' => ['sometimes', 'string'],
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

        if (! empty($validated['sessionId'])) {
            Auth::setUser($user);
            (new CartService($validated['sessionId']))->mergeGuestCartToUser();
        }

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $this->transformUser($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => 'string|required|max:50',
            'email' => 'email|required|max:255',
            'phone' => 'string|required|max:10',
            'password' => 'string|required|min:12',
            'sessionId' => 'sometimes|string'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password'=> Hash::make($validated['password'])
        ]);

        if(!empty($validated['sessionId'])){
            Auth::setUser($user);
            (new CartService($validated['sessionId']))->mergeGuestCartToUser();
        }
        
        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $this->transformUser($user),
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
}
