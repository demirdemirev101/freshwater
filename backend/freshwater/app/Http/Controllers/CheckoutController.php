<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        OrderService $orderService
    ) {
        $validated = $request->validate([
            'customer_name'     => 'required|string',
            'customer_email'    => 'required|email',
            'customer_phone'    => 'nullable|string',
            'shipping_address'  => 'required|string',
            'shipping_city'     => 'required|string',
            'shipping_postcode' => 'nullable|string',
            'holiday_delivery_day' => 'nullable|date_format:Y-m-d',
            'payment_method'    => 'required|string',
            'notes'             => 'nullable|string',
        ]);
        if (!isset($validated['holiday_delivery_day'])) {
            $validated['holiday_delivery_day'] = $request->input('holidayDeliveryDay');
        }

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
