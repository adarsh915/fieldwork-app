<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            if (!Schema::hasColumn('day_starts', 'applied_rate_per_km')) {
                $table->decimal('applied_rate_per_km', 8, 2)->nullable()->after('closing_km');
            }
            if (!Schema::hasColumn('day_starts', 'applied_rate_source')) {
                $table->string('applied_rate_source', 50)->nullable()->after('applied_rate_per_km');
            }
            if (!Schema::hasColumn('day_starts', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('applied_rate_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('day_starts', 'applied_rate_per_km')) {
                $columnsToDrop[] = 'applied_rate_per_km';
            }
            if (Schema::hasColumn('day_starts', 'applied_rate_source')) {
                $columnsToDrop[] = 'applied_rate_source';
            }
            if (Schema::hasColumn('day_starts', 'total_amount')) {
                $columnsToDrop[] = 'total_amount';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
