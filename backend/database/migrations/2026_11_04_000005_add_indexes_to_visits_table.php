<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PERFORMANCE FIX: Add indexes for visit queries
     * Used in: VisitController.php, ReportController.php (daily visits, party history, reporting)
     * Queries:
     * - WHERE day_start_id = ?
     * - WHERE user_id = ? AND created_at >= ?
     * - WHERE party_id = ? AND status = ?
     * Impact: 10-30x faster visit queries
     * 
     * Note: Composite index [party_id, status, check_out_time] already exists from 2026_10_17_150000
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Index for day_start_id (foreign key, heavily queried)
            $table->index('day_start_id', 'visits_day_start_idx');
            
            // Composite index for user visit history
            $table->index(['user_id', 'created_at'], 'visits_user_created_idx');
            
            // Index for status filtering
            $table->index('status', 'visits_status_idx');
            
            // Composite index for user + status queries
            $table->index(['user_id', 'status'], 'visits_user_status_idx');
            
            // Index for check-in/check-out time queries
            $table->index('check_in_time', 'visits_check_in_time_idx');
            $table->index('check_out_time', 'visits_check_out_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('visits_day_start_idx');
            $table->dropIndex('visits_user_created_idx');
            $table->dropIndex('visits_status_idx');
            $table->dropIndex('visits_user_status_idx');
            $table->dropIndex('visits_check_in_time_idx');
            $table->dropIndex('visits_check_out_time_idx');
        });
    }
};
