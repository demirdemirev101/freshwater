<?php

namespace App\Services;

use App\Models\Order;

class WeightCalculatorService
{
    /**
     * Calculate the total weight of an order based on its items and their associated products.
     *  The method loads the order's items along with their associated products to access the weight information.
     *  It then sums the total weight by multiplying each product's weight by the quantity of that item in the order.
     */
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
