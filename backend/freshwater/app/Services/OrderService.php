<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\CheckoutException;
use App\Jobs\CalculateBankTransferShippingJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected SettingsService $settingsService,
        private PaymentService $paymentService,
        private CartService $cartService,
        private StockService $stockService
    ) {}

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

            if (($data['payment_method'] ?? null) === 'stripe' && ! Setting::current()->stripe_enabled) {
                throw new CheckoutException('Stripe плащанията са временно изключени.', 422);
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? '',
                'shipping_city' => $data['shipping_city'],
                'shipping_postcode' => $data['shipping_postcode'] ?? null,
                'shipping_method' => $shippingMethod,
                'econt_office_code' => $shippingMethod === 'address'
                    ? null
                    : ($data['econt_office_code'] ?? null),
                'holiday_delivery_day' => $data['holiday_delivery_day'] ?? null,
                'status' => 'pending',
                'subtotal' => 0,
                'shipping_price' => 0,
                'total' => 0,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $quantity = (int) $itemData['quantity'];

                $this->stockService->reserve($product, $quantity);

                $price = $product->sale_price ?? $product->price;
                $total = $price * $quantity;
                $subtotal += $total;

                $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'product_name' => $product->name,
                    'price' => $price,
                    'quantity' => $quantity,
                    'total' => $total,
                ]);
            }

            $order->subtotal = $subtotal;
            $this->settingsService->applyTotals($order);
            $order->save();

            $this->paymentService->handle($order);

            if ($order->payment_method !== 'stripe') {
                DB::afterCommit(fn () => OrderPlaced::dispatch(
                    $order->id,
                    $data['session_id'] ?? $data['sessionId'] ?? null,
                ));
            }

            if (in_array($order->payment_method, ['bank_transfer', 'cod'], true)) {
                DB::afterCommit(fn () => dispatch(new CalculateBankTransferShippingJob($order->id)));
            }

            return $order;
        });
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->updateQuietly([
                'status' => 'cancelled',
            ]);
        });
    }

    public function releaseReservedStock(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with('items.product')
                ->lockForUpdate()
                ->firstOrFail();

            $this->releaseReservedStockForLockedOrder($lockedOrder);
        });
    }

    public function releaseReservedStockForLockedOrder(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if ($item->product) {
                $this->stockService->release($item->product, (int) $item->quantity);
            }
        }
    }

    public function deleteOrderWithItems(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->with('items.product')
                ->lockForUpdate()
                ->firstOrFail();

            $this->releaseReservedStockForLockedOrder($order);

            $order->items()->delete();
            $order->delete();
        });
    }
}
