<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\ShipmentCreated;
use App\Mail\OrderReturnRequestedMail;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipment\ShipmentReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShipmentReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_separate_return_shipment_and_dispatches_it_through_the_normal_flow(): void
    {
        Event::fake([ShipmentCreated::class]);
        Mail::fake();

        $order = Order::create([
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'shipping_method' => 'address',
            'status' => OrderStatus::COMPLETED->value,
            'subtotal' => 120,
            'shipping_price' => 0,
            'total' => 120,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        $outboundShipment = Shipment::create([
            'order_id' => $order->id,
            'carrier' => 'econt',
            'direction' => 'outbound',
            'carrier_shipment_id' => 'OUT-654321',
            'tracking_number' => 'OUT-654321',
            'weight' => 2.5,
            'pack_count' => 1,
            'delivery_type' => 'address',
            'status' => 'delivered',
        ]);

        $created = app(ShipmentReturnService::class)->createReturnLabel($order);

        $this->assertNotSame($outboundShipment->id, $created->id);
        $this->assertSame('return', $created->direction);
        $this->assertSame('created', $created->status);
        $this->assertSame('address', $created->delivery_type);
        $this->assertSame('0.00', (string) $created->cash_on_delivery);
        $this->assertSame($outboundShipment->pack_count, $created->pack_count);

        $order->refresh();
        $this->assertSame(OrderStatus::RETURN_REQUESTED->value, $order->status);
        $this->assertDatabaseHas('shipments', [
            'id' => $created->id,
            'order_id' => $order->id,
            'direction' => 'return',
        ]);

        Event::assertDispatched(
            ShipmentCreated::class,
            fn (ShipmentCreated $event): bool => $event->orderId === $order->id
                && $event->shipmentId === $created->id,
        );

        Mail::assertSent(OrderReturnRequestedMail::class);
    }
}
