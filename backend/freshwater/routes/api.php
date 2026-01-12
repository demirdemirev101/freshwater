<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\OrderController;

Route::get('/products', [ProductApiController::class, 'index']);

Route::post('/checkout', [OrderController::class, 'store']);
