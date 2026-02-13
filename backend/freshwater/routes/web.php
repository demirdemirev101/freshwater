<?php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'add']);
    Route::post('/update', [CartController::class, 'update']);
    Route::post('/remove', [CartController::class, 'remove']);
    Route::post('/clear', [CartController::class, 'clear']);
});

Route::post('/checkout', [CheckoutController::class, 'store']);

Route::get('/products', [ProductApiController::class, 'index']);

