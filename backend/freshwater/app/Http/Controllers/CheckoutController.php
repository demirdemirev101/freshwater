<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Process the checkout by validating the request data and creating an order using the OrderService.
      * @param \Illuminate\Http\Request $request
      * @param \App\Services\OrderService $orderService
      * @return \Illuminate\Http\JsonResponse
      * @throws \App\Exceptions\CheckoutException
     */
    public function store(Request $request, OrderService $orderService) 
    {
        $validated = $request->validate([
            'customer_name'     => 'required|string',
            'customer_email'    => 'required|email',
            'customer_phone'    => 'nullable|string',
            'shipping_address'  => 'required|string',
            'shipping_city'     => 'required|string',
            'shipping_postcode' => 'nullable|string',
            'payment_method'    => 'required|string',
            'notes'             => 'nullable|string',
        ]);

        try {
            $order = $orderService->create($validated);

            return response()->json([
                'success'  => true,
                'order_id' => $order->id,
            ]);

        } catch (CheckoutException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }
}
