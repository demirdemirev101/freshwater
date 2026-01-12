<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        CartService $cartService,
        OrderService $orderService,
        StockService $stockService,
    )
    {
        $request->validate([
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
            'customer_phone' => 'nullable|string',
        ]);

        if ($cartService->items()->isEmpty()) {
            throw new CheckoutException('Количката е празна', 422);
        }

        return DB::transaction(function() use($request, $cartService, $orderService, $stockService){
            $order = $orderService->create([
                'user_id' => Auth::id(),
            ]);

            foreach($cartService->items() as $item){

                $stockService->reserve($item->prouct, $item->quantity);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                ]);
            }

            $orderService->setCustomerData($order, $request->all());
            $orderService->recalculateTotal($order);

            $cartService->clear();

            return response()->json([
                'success' => true,
                'order_id' =>$order->id,
            ]);
        });
    }
}
