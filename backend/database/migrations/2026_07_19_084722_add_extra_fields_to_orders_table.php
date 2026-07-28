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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_destination')->nullable();
            $table->string('preferred_transport')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->date('dispatch_date')->nullable();
            $table->string('schema')->nullable();
            $table->string('order_type')->default('Sales Order');
            $table->string('payment_mode')->default('Cash');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_destination', 'preferred_transport', 'vehicle_no', 
                'dispatch_date', 'schema', 'order_type', 'payment_mode'
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_unit_id']);
            $table->dropColumn('product_unit_id');
        });
    }
};
