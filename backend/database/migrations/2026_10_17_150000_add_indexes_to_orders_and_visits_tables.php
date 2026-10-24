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
            $table->index(['party_id', 'created_at'], 'orders_party_created_idx');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->index(['party_id', 'status', 'check_out_time'], 'visits_party_status_checkout_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_party_created_idx');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('visits_party_status_checkout_idx');
        });
    }
};
