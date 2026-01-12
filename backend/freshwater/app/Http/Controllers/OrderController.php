<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request, OrderService $orderService)
    {
        $order = $orderService->create($request->all());

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
        ]);
    }
}
