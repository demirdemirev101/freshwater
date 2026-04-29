<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;

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
