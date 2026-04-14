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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            
            //Адрес за доставка
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_postcode')->nullable();
            $table->string('shipping_country')->default('Bulgaria');

            //Статус на поръчката
            $table->string('status')->default('pending');
            /*
                Възможни статуси:
                - pending (изчакваща)
                - shipped (изпратена)
                - processing (в процес на обработка)
                - completed (завършена)
                - cancelled (отменена)
            */

            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            //Плащане
            $table->string('payment_method'); //cash, card, bank_transfer и т.н.
            $table->string('payment_status')->default('unpaid');

            /*
                Възможни статуси:
                - unpaid (неплатена)
                - paid (платена)
                - refunded (възстановена)
            */

            //Бележки към поръчката
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
