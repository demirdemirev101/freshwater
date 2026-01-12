<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(protected OrderPricingService $orderPricingService) {}

    public function setCustomerData(Order $order, array $data = []): void
    {
        if($order->customer_email){
            return;
        }
        if($order->user_id && $order->relationLoaded('user') || $order->user){
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
        DB::transaction(function () use ($order) {
            $order->subtotal = $order->items()->sum('total');

            $this->orderPricingService->applyTotals($order);

            $order->saveQuietly();
        });

        $order->refresh();
    }

    public function create(array $attributes = []): Order
    {
        return DB::transaction(function () use ($attributes)
        {
            return Order::create([
                'user_id' => $attributes['user_id'] ?? null,
                'status' => $attributes['status'] ?? 'pending', 
            ]);
        });
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order){
            $order->status='cancelled';
            $order->saveQuietly();
        });
    }

    /**
     * INTERNAL / DEV ONLY
     */
    public function deleteOrderWithItems(Order $order): void
    {
          DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });
    }
}