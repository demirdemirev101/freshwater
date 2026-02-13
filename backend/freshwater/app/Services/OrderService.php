<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Jobs\CalculateBankTransferShippingJob;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The OrderService class is responsible for managing the lifecycle of orders in the application. 
 *  It provides methods to create new orders, recalculate order totals, and cancel existing orders.
 *  The service integrates with other services such as SettingsService for calculating shipping costs and totals,
 *  PaymentService for handling payment processing, and CartService for managing the shopping cart.
 *  The create method encapsulates the entire order creation process within a database transaction to ensure data integrity, including creating the order,
 *  adding items from the cart, calculating totals, and handling payment. Additionally,
 *  it dispatches an event when an order is placed and schedules a job for calculating shipping costs for certain payment methods.
 */
class OrderService
{
    public function __construct(
            protected SettingsService $settingsService,
            private PaymentService $paymentService,
            private CartService $cartService
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
    /**
     * Create a new order based on the provided data. This method performs the following steps:
     *  1. It starts a database transaction to ensure atomicity of the entire order creation process.
     *  2. It creates a new order record in the database with the provided customer and shipping information, as well as the selected payment method.
     *  3. It retrieves the items from the user's cart and creates corresponding order items associated with the newly created order.
     *  4. It recalculates the order totals, including the subtotal and shipping price, using the SettingsService.
     *  5. It handles the payment processing for the order using the PaymentService.
     *  6. It dispatches an OrderPlaced event to signal that a new order has been created, allowing other parts of the application to react accordingly.
     *  7. If the payment method is either 'bank_transfer' or 'cod', it schedules a job to calculate the shipping costs asynchronously.
     *  8. Finally, it returns the created order instance.
     */

    public function create(array $data = []): Order
    {
        return DB::transaction(function () use ($data) {

            $order = Order::create([
                // If the user is authenticated, associate the order with the user's ID. Otherwise, the order will be created without a user association.
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

            foreach ($cartItems as $cartItem) {
                $order->items()->create([
                    'product_id'   => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'price'        => $cartItem->price,
                    'quantity'     => $cartItem->quantity,
                    'total'        => $cartItem->total,
                ]);
            }

            $this->recalculateTotal($order);

            // ⚠️ PaymentService вътре в транзакцията (OK за сега)
            $this->paymentService->handle($order);

            // ✅ ЕДИН event, ясно и чисто
            OrderPlaced::dispatch($order->id);

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
