<?php

namespace App\Services;

use App\Models\Order;

class WeightCalculatorService
{
    public function forOrder(Order $order): float
    {
        $order->loadMissing('items.product');

        $weight = $order->items->sum(function ($item) {
            $productWeight = (float) ($item->product->weight ?? 0);
            return $productWeight * $item->quantity;
        });

        return max($weight, 0.100);
    }
}
