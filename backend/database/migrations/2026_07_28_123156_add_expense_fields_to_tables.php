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
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->decimal('rate_per_km', 8, 2)->default(0)->after('name');
        });

        Schema::table('day_starts', function (Blueprint $table) {
            $table->decimal('gps_calculated_km', 10, 2)->nullable()->after('closing_km_photo');
            $table->string('flagged_reason')->nullable()->after('status');
        });

        Schema::table('expense_bills', function (Blueprint $table) {
            $table->boolean('is_system_generated')->default(false)->after('bill_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn('rate_per_km');
        });

        Schema::table('day_starts', function (Blueprint $table) {
            $table->dropColumn(['gps_calculated_km', 'flagged_reason']);
        });

        Schema::table('expense_bills', function (Blueprint $table) {
            $table->dropColumn('is_system_generated');
        });
    }
};
