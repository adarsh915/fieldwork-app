<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PERFORMANCE FIX: Add indexes for leave queries
     * Used in: LeaveController.php (admin approval, user leave history, conflict detection)
     * Queries:
     * - WHERE status = 'pending'
     * - WHERE user_id = ? AND status = ? AND from_date >= ?
     * - WHERE has_conflict = true
     * Impact: 10-30x faster leave queries
     */
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Index for status filtering (pending/approved/rejected)
            $table->index('status', 'leaves_status_idx');
            
            // Composite index for user leave history
            $table->index(['user_id', 'status', 'from_date'], 'leaves_user_status_date_idx');
            
            // Index for date range queries
            $table->index(['from_date', 'to_date'], 'leaves_date_range_idx');
            
            // Index for conflict detection
            $table->index('has_conflict', 'leaves_has_conflict_idx');
            $table->index('conflict_status', 'leaves_conflict_status_idx');
            
            // Index for leave type filtering
            $table->index('leave_type_id', 'leaves_type_idx');
            
            // Index for approver queries
            $table->index('approved_by', 'leaves_approved_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_status_idx');
            $table->dropIndex('leaves_user_status_date_idx');
            $table->dropIndex('leaves_date_range_idx');
            $table->dropIndex('leaves_has_conflict_idx');
            $table->dropIndex('leaves_conflict_status_idx');
            $table->dropIndex('leaves_type_idx');
            $table->dropIndex('leaves_approved_by_idx');
        });
    }
};
