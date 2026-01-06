<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->decimal('delivery_price', 10, 2)->default(0.00);
            $table->decimal('free_delivery_over', 10, 2)->nullable();
            $table->boolean('delivery_enabled')->default(true);

            $table->decimal('vat_percentage', 5, 2)->default(20.00);
            $table->decimal('cod_fee', 10, 2)->default(0.00);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
