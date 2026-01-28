<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;
        
Route::get('/products', [ProductApiController::class, 'index']);
