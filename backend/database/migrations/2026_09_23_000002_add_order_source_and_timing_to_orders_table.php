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
            if (!Schema::hasColumn('orders', 'order_source')) {
                $table->string('order_source', 20)->default('visit')->after('status');
            }
            if (!Schema::hasColumn('orders', 'is_after_day_end')) {
                $table->boolean('is_after_day_end')->default(false)->after('order_source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_source')) {
                $table->dropColumn('order_source');
            }
            if (Schema::hasColumn('orders', 'is_after_day_end')) {
                $table->dropColumn('is_after_day_end');
            }
        });
    }
};
