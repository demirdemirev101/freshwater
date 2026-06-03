<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use Tests\TestCase;

class ReturnShipmentPayloadTest extends TestCase
{
    public function test_return_shipment_payload_uses_customer_as_sender_and_store_as_receiver(): void
    {
        config([
            'services.econt.sender.name' => 'Eksait',
            'services.econt.sender.phone' => '+359888111222',
            'services.econt.sender.office_code' => '1000',
        ]);

        $order = new Order([
            'customer_name' => 'Ivan Ivanov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1000',
            'shipping_address' => 'Test street 1',
            'subtotal' => 120,
        ]);

        $shipment = new Shipment([
            'direction' => 'return',
            'delivery_type' => 'office',
            'office_code' => '5500',
            'weight' => 1.250,
            'pack_count' => 1,
            'declared_value' => 120,
            'cash_on_delivery' => 0,
        ]);
        $shipment->setRelation('order', $order);

        $payload = app(EcontPayloadMapper::class)->map($shipment);

        $this->assertNull(data_get($payload, 'previousShipmentNumber'));
        $this->assertSame('Ivan Ivanov', data_get($payload, 'senderClient.name'));
        $this->assertSame('+359888123456', data_get($payload, 'senderClient.phones.0'));
        $this->assertSame('5500', data_get($payload, 'senderOfficeCode'));
        $this->assertSame('Eksait', data_get($payload, 'receiverClient.name'));
        $this->assertSame('1000', data_get($payload, 'receiverOfficeCode'));
        $this->assertSame('cash', data_get($payload, 'paymentReceiverMethod'));
        $this->assertSame(120.0, data_get($payload, 'services.declaredValueAmount'));
        $this->assertSame('ivan@example.com', data_get($payload, 'services.smsNotification.toEmail'));
    }
}
