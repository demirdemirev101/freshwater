<?php

namespace App\Http\Controllers;

use App\Services\CartSessionResolverService;
use App\Services\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(
        Request $request,
        CustomerAuthService $customerAuthService,
        CartSessionResolverService $cartSessionResolver
    ): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $payload = $customerAuthService->login($validated, $cartSessionResolver->resolveForAuth($request));

        if ($payload === null) {
            return response()->json(['message' => 'Невалиден имейл или парола.'], 401);
        }

        return response()->json($payload);
    }

    public function register(
        Request $request,
        CustomerAuthService $customerAuthService,
        CartSessionResolverService $cartSessionResolver
    ): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|required|max:50',
            'email' => 'email|required|max:255|unique:users,email',
            'phone' => 'string|required|max:10',
            'password' => 'string|required|min:8',
        ]);

        return response()->json(
            $customerAuthService->register($validated, $cartSessionResolver->resolveForAuth($request)),
            201
        );
    }

    public function me(Request $request, CustomerAuthService $customerAuthService): JsonResponse
    {
        return response()->json($customerAuthService->toUserPayload($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Успешно излязохте от профила си.']);
    }
}
