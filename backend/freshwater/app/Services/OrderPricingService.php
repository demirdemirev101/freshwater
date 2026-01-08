<?php

namespace App\Services;
use App\Models\Order;
use App\Models\Setting;

class OrderPricingService
{
    /**
     * Apply shipping rules to the order.
     * Call this ONLY when creating or updating the order
     * or when ADMIN changes explicitly recalculates
     */
    public function applyShipping(Order $order): void
    {
        $settings = Setting::current();

        $shippingPrice = 0;

        if(! $settings->delivery_enabled){
            $order->shipping_price = 0;
            return;
        }

        $shippingPrice = (float) $settings->delivery_price;

        if($settings->free_delivery_over!==null &&
           $order->subtotal >=$settings->free_delivery_over)
        {
            $shippingPrice = 0;
        }

        $order->shipping_price = $shippingPrice;
    }

    /**
     * Apply full pricing logic.
     */
    public function applyTotals(Order $order): void
    {
        // Recalculate shipping first
        $this->applyShipping($order);

        // Calculate total
        $order->total = $order->subtotal + ($order->shipping_price ?? 0);
    }
}