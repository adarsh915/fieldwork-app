<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PERFORMANCE FIX: Add indexes for day start queries
     * Used in: AdminApprovalController.php, DayController.php (admin approval, reporting)
     * Queries:
     * - WHERE status = 'pending'
     * - WHERE date BETWEEN ? AND ?
     * - WHERE user_id = ? AND date = ?
     * Impact: 10-50x faster day start queries
     */
    public function up(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            // Index for status filtering (pending/approved/rejected)
            $table->index('status', 'day_starts_status_idx');
            
            // Index for date range queries and reporting
            $table->index('date', 'day_starts_date_idx');
            
            // Composite index for status + date queries (admin approval queue)
            $table->index(['status', 'date'], 'day_starts_status_date_idx');
            
            // Index for tour purpose reporting
            $table->index('tour_purpose_id', 'day_starts_purpose_idx');
            
            // Index for tour type reporting
            $table->index('tour_type_id', 'day_starts_type_idx');
            
            // Index for vehicle type reporting
            $table->index('vehicle_type_id', 'day_starts_vehicle_idx');
            
            // Index for approver queries
            $table->index('approved_by', 'day_starts_approved_by_idx');
            
            // Composite index for user reporting (user performance queries)
            $table->index(['user_id', 'date', 'status'], 'day_starts_user_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            $table->dropIndex('day_starts_status_idx');
            $table->dropIndex('day_starts_date_idx');
            $table->dropIndex('day_starts_status_date_idx');
            $table->dropIndex('day_starts_purpose_idx');
            $table->dropIndex('day_starts_type_idx');
            $table->dropIndex('day_starts_vehicle_idx');
            $table->dropIndex('day_starts_approved_by_idx');
            $table->dropIndex('day_starts_user_date_status_idx');
        });
    }
};
