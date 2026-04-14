<?php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/add/{product}', [CartController::class, 'store']);
    Route::patch('/update/{product}', [CartController::class, 'update']);
    Route::delete('/delete/{product}', [CartController::class, 'remove']);
    Route::delete('/', [CartController::class, 'clear']);
});

Route::post('/checkout', [CheckoutController::class, 'store']);

Route::get('/products', [ProductApiController::class, 'index']);
