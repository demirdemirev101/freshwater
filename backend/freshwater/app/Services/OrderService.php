<?php

namespace App\Services;

use App\Models\Order;

class OrderService
{
    public function __construct(protected OrderPricingService $orderPricingService) {}

    public function setCustomerData(Order $order, array $data): void
    {
        if($order->user_id){
            $user = $order->user;

            $order->customer_email = $user->email;
            $order->customer_name  ??= $user->name;
            $order->customer_phone ??= $user->phone;

        } else{
            $order->customer_email ??= $data['customer_email']??null;
            $order->customer_name ??= $data['customer_name']??null;
            $order->customer_phone ??= $data['customer_phone']??null;
        }

        $order->saveQuietly();
    }

    public function recalculateTotal(Order $order): void
    {
        $order->subtotal = $order->items()->sum('total');
       
        $this->orderPricingService->applyTotals($order);
        
        $order->saveQuietly();
        $order->refresh();
    }

    public function deleteOrderWithItems(Order $order): void
    {
        $order->items()->delete();
        $order->delete();
    }
}