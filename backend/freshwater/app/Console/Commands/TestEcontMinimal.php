<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use App\Services\Econt\EcontService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestEcontMinimal extends Command
{
    protected $signature = 'test:econt-minimal 
                            {--calculate : Calculate only, no label}
                            {--address : Use senderAddress instead of office}
                            {--office= : Receiver office code (e.g. 1127)}
                            {--mode= : create or validate}';

    protected $description = 'Minimal Econt shipment test (no DB, no events)';

    public function handle(): int
    {
        $this->info('🚀 Starting MINIMAL Econt test');

        // 1️⃣ Fake order model (NO database)
        $order = new Order();
        $order->id = 999;
        $order->customer_name = 'Димитър Димитров';
        $order->customer_phone = '0876543210';
        $order->shipping_city = 'Русе';
        $order->shipping_postcode = '7000';
        $order->shipping_address = 'Муткурова';
        $order->shipping_address_num = '84';
        $order->shipping_address_details = 'бл. 5, вх. А, ет. 6';

        // 2️⃣ Shipment model
        $shipment = new Shipment();
        $shipment->id = 999;
        $shipment->weight = 1.2;
        $shipment->pack_count = 1;
        $shipment->delivery_type = 'address';
        $shipment->office_code = null;
        $shipment->cash_on_delivery = 100.00;
        $shipment->declared_value = 5.00;

        if ($this->option('office')) {
            $shipment->delivery_type = 'office';
            $shipment->office_code = (string) $this->option('office');
        }

        // 🔥 РЪЧНО връзваме relation-а
        $shipment->setRelation('order', $order);

        // 3️⃣ Map payload
        $this->info('🔧 Mapping payload...');
        $mapper = app(EcontPayloadMapper::class);

        try {
            $payload = $mapper->map($shipment);
        } catch (\Throwable $e) {
            $this->error('❌ Payload mapping failed');
            $this->line($e->getMessage());
            return Command::FAILURE;
        }

        // 4️⃣ Override sender (DIAGNOSTIC SWITCH)
        if ($this->option('address')) {
            $this->warn('⚠️ Using senderAddress instead of senderOfficeCode');

            unset($payload['senderOfficeCode']);

            $payload['senderAddress'] = [
                'city' => [
                    'country' => [
                        'code3' => 'BGR',
                    ],
                    'name' => 'Русе',
                    'postCode' => '7000',
                ],
                'street' => 'Алея Младост',
                'num' => '7',
            ];
        }

        $this->info('📦 Final payload:');
        dump($payload);

        // 5️⃣ Call Econt
        $econt = app(EcontService::class);

        try {
            if ($this->option('calculate')) {
                $this->info('📐 CALCULATE MODE');
                $price = $econt->calculatePrice($payload);
                $this->info('💰 Price: ' . ($price ?? 'NULL'));
            } else {
                $mode = $this->option('mode') ?: 'create';
                $this->info('📦 CREATE LABEL MODE: ' . $mode);
                $response = $econt->createLabel($payload, $mode);
                dump($response);
            }
        } catch (\Throwable $e) {
            $this->error('❌ Econt API ERROR');
            $this->line($e->getMessage());

            Log::error('Econt minimal test failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return Command::FAILURE;
        }

        $this->info('✅ TEST FINISHED');
        return Command::SUCCESS;
    }
}
