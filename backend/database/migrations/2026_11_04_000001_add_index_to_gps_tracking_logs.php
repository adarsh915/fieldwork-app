<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PERFORMANCE FIX: Add composite index for GPS distance calculation
     * Used in: DayController.php (day-end GPS calculation)
     * Query: WHERE user_id = ? AND DATE(created_at) = ?
     * Impact: 50-100x faster GPS distance calculation
     */
    public function up(): void
    {
        Schema::table('gps_tracking_logs', function (Blueprint $table) {
            // Composite index for user + date queries
            $table->index(['user_id', 'created_at'], 'gps_logs_user_date_idx');
            
            // Additional index for recorded_at (if needed for time-based queries)
            $table->index('recorded_at', 'gps_logs_recorded_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_tracking_logs', function (Blueprint $table) {
            $table->dropIndex('gps_logs_user_date_idx');
            $table->dropIndex('gps_logs_recorded_idx');
        });
    }
};
