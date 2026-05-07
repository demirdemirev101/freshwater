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
            protected SettingsService $settingsService,
            private PaymentService $paymentService,
            private CartService $cartService,
            private StockService $stockService
        ) 
        {}

    /**
     * Recalculate the total for a given order. This method performs the following steps:
     *  1. It calculates the subtotal by summing the total of all items associated with the order.
     *  2. It applies the shipping rules and recalculates the shipping price using the SettingsService.
     *  3. It saves the updated order totals to the database without triggering model events.
     *  4. It refreshes the order instance to ensure it has the latest data from the database.  
    */
    public function recalculateTotal(Order $order): void
    {
        $order->subtotal = $order->items()->sum('total');
        $this->settingsService->applyTotals($order);
        $order->saveQuietly();
        $order->refresh();
    }

    public function createFromItems(array $data = []): Order
    {
        return DB::transaction(function () use ($data) {
            $shippingMethod = $data['shipping_method'] ?? 'address';

            $order = Order::create([
                // If the user is authenticated, associate the order with the user's ID. Otherwise, the order will be created without a user association.
                'user_id'           => Auth::id(),
                'customer_name'     => $data['customer_name'],
                'customer_email'    => $data['customer_email'],
                'customer_phone'    => $data['customer_phone'] ?? null,

                'shipping_address'  => $data['shipping_address'] ?? '',
                'shipping_city'     => $data['shipping_city'],
                'shipping_postcode' => $data['shipping_postcode'] ?? null,
                'shipping_method'   => $shippingMethod,
                'econt_office_code' => $shippingMethod === 'address'
                    ? null
                    : ($data['econt_office_code'] ?? null),
                'holiday_delivery_day' => $data['holiday_delivery_day'] ?? null,

                'status'            => 'pending',

                'subtotal'          => 0,
                'shipping_price'    => 0,
                'total'             => 0,

                'payment_method'    => $data['payment_method'],
                'payment_status'    => 'pending',

                'notes'             => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            foreach ($data['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $this->stockService->reserve($product, (int)$itemData['quantity']);
                $productName = $product->name;
                $price = $product->sale_price ?? $product->price;
                $total = $price * $itemData['quantity'];
                
                $subtotal += $total;

                $order->items()->create([
                    'product_id'   => $itemData['product_id'],
                    'product_name' => $productName,
                    'price'        => $price,
                    'quantity'     => $itemData['quantity'],
                    'total'        => $total,
                ]);
            }

            $order->subtotal = $subtotal;
            $this->settingsService->applyTotals($order);
            $order->save();

            // ⚠️ PaymentService вътре в транзакцията (OK за сега)
            $this->paymentService->handle($order);

            // ✅ ЕДИН event, ясно и чисто
            OrderPlaced::dispatch($order->id, $data['session_id'] ?? $data['sessionId'] ?? null);

            if (in_array($order->payment_method, ['bank_transfer', 'cod'], true)) {
                dispatch(new CalculateBankTransferShippingJob($order->id));
            }

            return $order;
        });
    }

    /**
     * Cancel an existing order. This method performs the following steps:
         *  1. It starts a database transaction to ensure atomicity of the operation.
         *  2. It updates the order's status to 'cancelled' in the database without triggering model events.
         *  3. Finally, it completes the transaction, ensuring that the order cancellation is applied atomically to maintain data integrity.
         *  
         * Note: This method assumes that any necessary stock adjustments or other related operations are handled elsewhere,
         *  as it only updates the order's status.
     */
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
     * Delte an order along with its associated items. This method performs the following steps:
     *  1. It starts a database transaction to ensure atomicity of the operation.
     *  2. It deletes all items associated with the order from the database.
     *  3. It deletes the order itself from the database.
     *  4. Finally, it completes the transaction, ensuring that all deletions are applied atomically to maintain data integrity.
     */
    public function deleteOrderWithItems(Order $order): void
    {
          DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });
    }
}
