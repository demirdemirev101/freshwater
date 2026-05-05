<?php

use App\Exceptions\CheckoutException;
use App\Http\Middleware\AuthenticateOptionalSanctumToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'optional.sanctum' => AuthenticateOptionalSanctumToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            function (CheckoutException $e, $request): JsonResponse {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->status());
            }
        );
    })
    ->create();
