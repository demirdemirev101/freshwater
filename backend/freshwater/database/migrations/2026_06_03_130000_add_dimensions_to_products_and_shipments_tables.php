<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('height', 8, 2)->nullable()->after('weight');
            $table->decimal('width', 8, 2)->nullable()->after('height');
            $table->decimal('length', 8, 2)->nullable()->after('width');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('height', 8, 2)->nullable()->after('weight');
            $table->decimal('width', 8, 2)->nullable()->after('height');
            $table->decimal('length', 8, 2)->nullable()->after('width');
            $table->string('shipment_type')->nullable()->after('pack_count');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['height', 'width', 'length', 'shipment_type']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['height', 'width', 'length']);
        });
    }
};
