<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('carrier'); // 'econt', 'speedy', etc.

            // Еконт специфични
            $table->string('carrier_shipment_id')->nullable(); // shipmentNumber от Еконт
            $table->string('tracking_number')->nullable(); // същото като shipmentNumber
            $table->string('label_url')->nullable(); // PDF URL на етикета
            
            // Детайли за пратката
            $table->decimal('weight', 8, 3)->nullable(); // kg
            $table->integer('pack_count')->default(1); // брой пакети
            $table->decimal('declared_value', 10, 2)->nullable();
            $table->decimal('cash_on_delivery', 10, 2)->nullable();

            // Цени
            $table->decimal('shipping_price_estimated', 10, 2)->nullable();
            $table->decimal('shipping_price_real', 10, 2)->nullable();

            // Адреси и локации
            $table->string('delivery_type')->nullable(); // 'office', 'address', 'apm'
            $table->string('office_code')->nullable(); // ако е до офис/автомат
            
            // API комуникация
            $table->json('carrier_payload')->nullable(); // какво сме пратили
            $table->json('carrier_response')->nullable(); // какво сме получили
            $table->json('tracking_events')->nullable(); // статуси от проследяването

            $table->string('status')->default('created');
            /*
                created - създаден локално
                pending - чака изпращане до Еконт
                sent_to_carrier - изпратен към Еконт API
                confirmed - потвърден от Еконт
                picked_up - взет от куриер
                in_transit - в транспорт
                out_for_delivery - на куриер за доставка
                delivered - доставен
                returned - върнат
                cancelled - отказан
                error - грешка
            */
            
            $table->text('error_message')->nullable(); // за грешки от API
            $table->timestamp('sent_to_carrier_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();
            
            // Индекси за по-бързо търсене
            $table->index('tracking_number');
            $table->index('status');
            $table->index('carrier_shipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};