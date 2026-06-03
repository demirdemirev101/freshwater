<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('return_carrier_shipment_id')->nullable()->after('carrier_shipment_id');
            $table->string('return_tracking_number')->nullable()->after('tracking_number');
            $table->string('return_label_url')->nullable()->after('label_url');
            $table->json('return_carrier_payload')->nullable()->after('carrier_payload');
            $table->json('return_carrier_response')->nullable()->after('carrier_response');
            $table->string('return_status')->nullable()->after('status');
            $table->text('return_error_message')->nullable()->after('error_message');
            $table->timestamp('return_sent_to_carrier_at')->nullable()->after('sent_to_carrier_at');

            $table->index('return_carrier_shipment_id');
            $table->index('return_tracking_number');
            $table->index('return_status');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['return_carrier_shipment_id']);
            $table->dropIndex(['return_tracking_number']);
            $table->dropIndex(['return_status']);

            $table->dropColumn([
                'return_carrier_shipment_id',
                'return_tracking_number',
                'return_label_url',
                'return_carrier_payload',
                'return_carrier_response',
                'return_status',
                'return_error_message',
                'return_sent_to_carrier_at',
            ]);
        });
    }
};
