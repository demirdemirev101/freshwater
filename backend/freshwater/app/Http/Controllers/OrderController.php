<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $order = $orderService->create($request->validated());

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
        ]);
    }
}
