<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Events\OrderReadyForShipment;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_checkout_marks_order_as_paid_and_dispatches_events(): void
    {
        Event::fake([OrderPlaced::class, OrderReadyForShipment::class]);
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $order = Order::create([
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'shipping_method' => 'address',
            'status' => 'pending',
            'subtotal' => 99,
            'shipping_price' => 0,
            'total' => 99,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
        ]);

        $payload = json_encode([
            'id' => 'evt_checkout_completed',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                    'amount_total' => 9900,
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'cart_session_id' => 'cart_session_1',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        )->assertOk();

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('ready_for_shipment', $order->status);
        $this->assertSame('cs_test_123', $order->stripe_checkout_session_id);
        $this->assertSame('pi_test_123', $order->stripe_payment_intent_id);

        Event::assertDispatched(OrderPlaced::class);
        Event::assertDispatched(OrderReadyForShipment::class);
    }

    public function test_expired_checkout_removes_pending_order_and_restores_reserved_stock(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $product = Product::create([
            'name' => 'Compact UF',
            'price' => 120,
            'stock' => true,
            'quantity' => 4,
        ]);

        $order = Order::create([
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'shipping_method' => 'address',
            'status' => 'pending',
            'subtotal' => 120,
            'shipping_price' => 0,
            'total' => 120,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 120,
            'quantity' => 1,
            'total' => 120,
        ]);

        $payload = json_encode([
            'id' => 'evt_checkout_expired',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_expired',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        )->assertOk();

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);

        $this->assertSame(5, (int) $product->fresh()->quantity);
    }

    public function test_refund_updated_marks_order_as_partially_refunded(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $order = Order::create([
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'shipping_method' => 'address',
            'status' => 'completed',
            'subtotal' => 120,
            'shipping_price' => 0,
            'total' => 120,
            'payment_method' => 'stripe',
            'payment_status' => 'paid',
            'stripe_payment_intent_id' => 'pi_refund_123',
        ]);

        $payload = json_encode([
            'id' => 'evt_refund_updated',
            'type' => 'refund.updated',
            'data' => [
                'object' => [
                    'id' => 're_test_partial',
                    'status' => 'succeeded',
                    'payment_intent' => 'pi_refund_123',
                    'amount' => 5000,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        )->assertOk();

        $order->refresh();

        $this->assertSame('partially_refunded', $order->payment_status);
        $this->assertSame('50.00', (string) $order->refunded_amount);
        $this->assertSame('re_test_partial', $order->stripe_refund_id);
        $this->assertSame('completed', $order->status);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }
}
