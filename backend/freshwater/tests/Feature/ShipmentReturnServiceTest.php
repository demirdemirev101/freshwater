<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipment\ShipmentReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShipmentReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_reverse_label_for_address_delivery(): void
    {
        config([
            'services.econt.enabled' => true,
            'services.econt.base_url' => 'https://demo.econt.com/ee/services',
            'services.econt.username' => 'demo-user',
            'services.econt.password' => 'demo-pass',
            'services.econt.verify_ssl' => true,
            'services.econt.sender.name' => 'Freshwater',
            'services.econt.sender.phone' => '+359888888888',
            'services.econt.sender.office_code' => 'FS001',
        ]);

        Http::fake([
            'https://demo.econt.com/ee/services/Shipments/LabelService.createLabel.json' => Http::response([
                'label' => [
                    'shipmentNumber' => 'RET-123456',
                    'pdfURL' => 'https://example.test/return-label.pdf',
                    'totalPrice' => 8.40,
                ],
            ], 200),
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
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'carrier' => 'econt',
            'carrier_shipment_id' => 'OUT-654321',
            'tracking_number' => 'OUT-654321',
            'weight' => 2.5,
            'pack_count' => 1,
            'delivery_type' => 'address',
            'status' => 'delivered',
        ]);

        $created = app(ShipmentReturnService::class)->createReturnLabel($order);

        $this->assertSame('RET-123456', $created->return_carrier_shipment_id);
        $this->assertSame('RET-123456', $created->return_tracking_number);
        $this->assertSame('https://example.test/return-label.pdf', $created->return_label_url);
        $this->assertSame('confirmed', $created->return_status);
        $this->assertNotNull($created->return_sent_to_carrier_at);

        Http::assertSent(function (Request $request) use ($shipment) {
            $payload = $request->data();

            return $request->url() === 'https://demo.econt.com/ee/services/Shipments/LabelService.createLabel.json'
                && data_get($payload, 'label.previousShipmentNumber') === $shipment->carrier_shipment_id
                && data_get($payload, 'label.senderAddress.city.name') === 'Sofia'
                && data_get($payload, 'label.receiverOfficeCode') === 'FS001'
                && data_get($payload, 'label.paymentReceiverMethod') === 'cash';
        });
    }
}
