<?php

namespace Tests\Feature;

use App\Events\ShipmentCreated;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Services\OrderReturnRequestService;
use App\Services\StripeRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderReturnRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_request_for_paid_stripe_order_creates_return_shipment_and_refunds(): void
    {
        Event::fake([ShipmentCreated::class]);
        Mail::fake();

        $this->app->instance(StripeRefundService::class, new class extends StripeRefundService
        {
            public function refund(Order $order, float $amount, ?\App\Enums\OrderStatus $fullyRefundedOrderStatus = null): object
            {
                $this->applyRefund($order, 're_test_return', $amount, $fullyRefundedOrderStatus);

                return (object) ['id' => 're_test_return'];
            }
        });

        $product = Product::create([
            'name' => 'Compact UF',
            'price' => 120,
            'stock' => true,
            'quantity' => 5,
            'weight' => 1.2,
        ]);

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
            'stripe_payment_intent_id' => 'pi_return_123',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 120,
            'quantity' => 1,
            'total' => 120,
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'carrier' => 'econt',
            'direction' => 'outbound',
            'carrier_shipment_id' => 'OUT-123',
            'tracking_number' => 'OUT-123',
            'weight' => 1.2,
            'pack_count' => 1,
            'delivery_type' => 'address',
            'status' => 'delivered',
        ]);

        $shipment = app(OrderReturnRequestService::class)->requestReturn($order);

        $order->refresh();

        $this->assertSame('return', $shipment->direction);
        $this->assertSame('return_requested', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('120.00', (string) $order->refunded_amount);
        $this->assertSame('re_test_return', $order->stripe_refund_id);
    }
}
