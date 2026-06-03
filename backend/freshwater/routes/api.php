<?php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductApiController::class, 'index']);

Route::middleware('optional.sanctum')->group(function () {
    // Cart routes
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/add/{product}', [CartController::class, 'store']);
    Route::patch('/cart/update/{product}', [CartController::class, 'update']);
    Route::delete('/cart/delete/{product}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);

    Route::get('/checkout/econt-offices', [CheckoutController::class, 'econtOffices'])
        ->middleware('throttle:econt-lookup');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:checkout-api');
    Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping'])
        ->middleware('throttle:checkout-api');
});

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact-form');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:auth-api');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:auth-api');

Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
