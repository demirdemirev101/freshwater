<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use App\Services\Shipment\ShipmentCreationService;
use App\Services\Shipment\ShipmentMeasurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentMeasurementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_shipment_as_cargo_when_dimensions_exceed_package_limit(): void
    {
        $product = Product::create([
            'name' => 'Large Aquarium',
            'price' => 300,
            'stock' => false,
            'weight' => 12,
            'height' => 20,
            'width' => 35,
            'length' => 70,
        ]);

        $order = Order::create([
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'shipping_method' => 'address',
            'status' => 'ready_for_shipment',
            'subtotal' => 300,
            'shipping_price' => 0,
            'total' => 300,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 300,
            'quantity' => 1,
            'total' => 300,
        ]);

        $measurements = app(ShipmentMeasurementService::class)->forOrder($order->fresh('items.product'));

        $this->assertSame('cargo', $measurements['shipment_type']);
        $this->assertSame(12.0, $measurements['weight']);
        $this->assertSame(20.0, $measurements['height']);
        $this->assertSame(35.0, $measurements['width']);
        $this->assertSame(70.0, $measurements['length']);

        app(ShipmentCreationService::class)->createForOrder($order->id);

        $shipment = $order->fresh('shipment')->shipment;

        $this->assertSame('cargo', $shipment->shipment_type);
        $this->assertSame('70.00', $shipment->length);
    }

    public function test_it_defaults_to_pack_when_dimensions_are_missing_and_weight_is_below_limit(): void
    {
        $product = Product::create([
            'name' => 'Filter Media',
            'price' => 25,
            'stock' => false,
            'weight' => 2.5,
        ]);

        $order = Order::create([
            'customer_name' => 'Maria Petrova',
            'customer_email' => 'maria@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Bulgaria 15',
            'shipping_city' => 'Plovdiv',
            'shipping_postcode' => '4000',
            'shipping_method' => 'address',
            'status' => 'ready_for_shipment',
            'subtotal' => 50,
            'shipping_price' => 0,
            'total' => 50,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 25,
            'quantity' => 2,
            'total' => 50,
        ]);

        $measurements = app(ShipmentMeasurementService::class)->forOrder($order->fresh('items.product'));

        $this->assertSame('pack', $measurements['shipment_type']);
        $this->assertSame(5.0, $measurements['weight']);
        $this->assertNull($measurements['height']);
        $this->assertNull($measurements['width']);
        $this->assertNull($measurements['length']);
    }

    public function test_econt_payload_uses_cargo_for_cargo_shipments(): void
    {
        config([
            'services.econt.sender.name' => 'Freshwater',
            'services.econt.sender.phone' => '+359888888888',
            'services.econt.sender.office_code' => 'FS001',
        ]);

        $order = Order::create([
            'customer_name' => 'Georgi Ivanov',
            'customer_email' => 'georgi@example.com',
            'customer_phone' => '0888123456',
            'shipping_address' => 'Bulgaria 15',
            'shipping_city' => 'Plovdiv',
            'shipping_postcode' => '4000',
            'shipping_method' => 'address',
            'status' => 'ready_for_shipment',
            'subtotal' => 120,
            'shipping_price' => 0,
            'total' => 120,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        $shipment = new Shipment([
            'carrier' => 'econt',
            'weight' => 35,
            'height' => 30,
            'width' => 40,
            'length' => 70,
            'pack_count' => 1,
            'shipment_type' => 'cargo',
            'delivery_type' => 'address',
            'declared_value' => 120,
            'cash_on_delivery' => 120,
        ]);
        $shipment->setRelation('order', $order);

        $payload = app(EcontPayloadMapper::class)->map($shipment);

        $this->assertSame('CARGO', $payload['shipmentType']);
    }
}
