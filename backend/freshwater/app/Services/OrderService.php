<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Jobs\CalculateBankTransferShippingJob;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
            protected OrderPricingService $orderPricingService,
            private PaymentService $paymentService,
            private CartService $cartService
        ) 
        {}

    public function recalculateTotal(Order $order): void
    {
        $order->subtotal = $order->items()->sum('total');
        $this->orderPricingService->applyTotals($order);
        $order->saveQuietly();
        $order->refresh();
    }

    public function create(array $data = []): Order
    {
        return DB::transaction(function () use ($data) {

            $order = Order::create([
                'user_id'           => Auth::id(),
                'customer_name'     => $data['customer_name'],
                'customer_email'    => $data['customer_email'],
                'customer_phone'    => $data['customer_phone'] ?? null,

                'shipping_address'  => $data['shipping_address'],
                'shipping_city'     => $data['shipping_city'],
                'shipping_postcode' => $data['shipping_postcode'] ?? null,
                'holiday_delivery_day' => $data['holiday_delivery_day'] ?? null,

                'status'            => 'pending',

                'subtotal'          => 0,
                'shipping_price'    => 0,
                'total'             => 0,

                'payment_method'    => $data['payment_method'],
                'payment_status'    => 'pending',

                'notes'             => $data['notes'] ?? null,
            ]);

            $cartItems = $this->cartService->items();

            if ($cartItems->isEmpty()) {
                throw new \Exception('Количката е празна');
            }

            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name,
                    'price'        => $item->price,
                    'quantity'     => $item->quantity,
                    'total'        => $item->total,
                ]);
            }

            $this->recalculateTotal($order);

            // ⚠️ PaymentService вътре в транзакцията (OK за сега)
            $this->paymentService->handle($order);

            // ✅ ЕДИН event, ясно и чисто
            OrderPlaced::dispatch($order->id);

            if ($order->payment_method === 'bank_transfer') {
                dispatch(new CalculateBankTransferShippingJob($order->id));
            }

            return $order;
        });
    }


    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order){
            $order->updateQuietly([
                'status' => 'cancelled',
            ]);
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
