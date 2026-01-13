<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
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

    public function create(array $data = []): Order
    {
        return DB::transaction(function () use ($data)
        {
            $order = Order::create([
                'user_id'           => Auth::id(),
                'customer_name'     => $data['customer_name'],
                'customer_email'    => $data['customer_email'],
                'customer_phone'    => $data['customer_phone'] ?? null,

                'shipping_address'  => $data['shipping_address'],
                'shipping_city'     => $data['shipping_city'],
                'shipping_postcode' => $data['shipping_postcode'] ?? null,

                'status'            => 'pending',

                // 🔥 ВАЖНО
                'subtotal'          => $data['subtotal'],
                'shipping_price'    => $data['shipping_price'] ?? 0,
                'total'             => $data['total'],

                'payment_method'    => $data['payment_method'],
                'payment_status'    => 'unpaid',

                'notes'             => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {

                $product = Product::findOrFail($item['product_id']);
                
                $order->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'price'        => $item['price'],
                    'quantity'     => $item['quantity'],
                    'total'  => $item['price'] * $item['quantity'],
                ]);
            }

            DB::afterCommit(function () use ($order) {
                event(new OrderCreated($order));
            });

            return $order;
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