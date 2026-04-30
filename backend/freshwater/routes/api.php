<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\AuthController;

Route::get('/products', [ProductApiController::class, 'index']);

// Cart routes
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/add/{product}', [CartController::class, 'store']);
Route::patch('/cart/update/{product}', [CartController::class, 'update']);
Route::delete('/cart/delete/{product}', [CartController::class, 'remove']);
Route::delete('/cart', [CartController::class, 'clear']);

Route::get('/checkout/econt-offices', [CheckoutController::class, 'econtOffices']);
Route::post('/checkout', [CheckoutController::class, 'store']);
Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping']);


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});