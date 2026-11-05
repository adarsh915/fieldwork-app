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
        Schema::table('day_starts', function (Blueprint $table) {
            if (!Schema::hasColumn('day_starts', 'total_orders')) {
                $table->integer('total_orders')->nullable()->default(0)->after('end_remarks');
            }
            if (!Schema::hasColumn('day_starts', 'total_sales')) {
                $table->decimal('total_sales', 12, 2)->nullable()->default(0.00)->after('total_orders');
            }
            if (!Schema::hasColumn('day_starts', 'total_collection')) {
                $table->decimal('total_collection', 12, 2)->nullable()->default(0.00)->after('total_sales');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('day_starts', 'total_orders')) {
                $columnsToDrop[] = 'total_orders';
            }
            if (Schema::hasColumn('day_starts', 'total_sales')) {
                $columnsToDrop[] = 'total_sales';
            }
            if (Schema::hasColumn('day_starts', 'total_collection')) {
                $columnsToDrop[] = 'total_collection';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
