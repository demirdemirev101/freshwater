<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderCancellationService;
use App\Services\StripeRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_paid_stripe_order_refunds_and_restores_stock(): void
    {
        Mail::fake();

        $this->app->instance(StripeRefundService::class, new class extends StripeRefundService
        {
            public function refund(Order $order, float $amount, ?\App\Enums\OrderStatus $fullyRefundedOrderStatus = null): object
            {
                $this->applyRefund($order, 're_test_cancel', $amount, $fullyRefundedOrderStatus);

                return (object) ['id' => 're_test_cancel'];
            }
        });

        $product = Product::create([
            'name' => 'Compact MF',
            'price' => 99,
            'stock' => true,
            'quantity' => 3,
        ]);

        $order = Order::create([
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'shipping_method' => 'address',
            'status' => 'pending_review',
            'subtotal' => 99,
            'shipping_price' => 0,
            'total' => 99,
            'payment_method' => 'stripe',
            'payment_status' => 'paid',
            'stripe_payment_intent_id' => 'pi_cancel_123',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 99,
            'quantity' => 1,
            'total' => 99,
        ]);

        app(OrderCancellationService::class)->cancel($order);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('99.00', (string) $order->refunded_amount);
        $this->assertSame('re_test_cancel', $order->stripe_refund_id);
        $this->assertSame(4, (int) $product->fresh()->quantity);
    }
}
