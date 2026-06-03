<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('direction')->default('outbound')->after('carrier');
            $table->index(['order_id', 'direction']);
        });

        DB::table('shipments')
            ->whereNull('direction')
            ->update(['direction' => 'outbound']);
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'direction']);
            $table->dropColumn('direction');
        });
    }
};
