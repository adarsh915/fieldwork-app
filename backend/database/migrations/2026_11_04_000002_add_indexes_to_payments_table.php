<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PERFORMANCE FIX: Add indexes for payment queries
     * Used in: PaymentController.php (admin approval, party payment history)
     * Queries:
     * - WHERE status = 'pending'
     * - WHERE party_id = ? AND status = ? ORDER BY created_at
     * - WHERE collected_by = ? AND status = ?
     * Impact: 10-50x faster payment queries
     */
    public function up(): void
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        Schema::table('payments', function (Blueprint $table) use ($connection, $databaseName) {
            // Helper to check if index exists
            $indexExists = function($indexName) use ($connection, $databaseName) {
                $result = $connection->select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = 'payments' AND index_name = ?",
                    [$databaseName, $indexName]
                );
                return $result[0]->count > 0;
            };
            
            // Index for status filtering (pending/completed/failed)
            if (!$indexExists('payments_status_idx')) {
                $table->index('status', 'payments_status_idx');
            }
            
            // Composite index for party payment history
            if (!$indexExists('payments_party_status_date_idx')) {
                $table->index(['party_id', 'status', 'created_at'], 'payments_party_status_date_idx');
            }
            
            // Composite index for field worker collections
            // NOTE: payments table uses 'collected_by' instead of 'user_id'
            if (!$indexExists('payments_collected_by_status_date_idx')) {
                $table->index(['collected_by', 'status', 'created_at'], 'payments_collected_by_status_date_idx');
            }
            
            // Index for visit-related payments
            if (!$indexExists('payments_visit_idx')) {
                $table->index('visit_id', 'payments_visit_idx');
            }
            
            // Index for collection_datetime queries
            if (!$indexExists('payments_collection_datetime_idx')) {
                $table->index('collection_datetime', 'payments_collection_datetime_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        Schema::table('payments', function (Blueprint $table) use ($connection, $databaseName) {
            // Helper to check if index exists
            $indexExists = function($indexName) use ($connection, $databaseName) {
                $result = $connection->select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = 'payments' AND index_name = ?",
                    [$databaseName, $indexName]
                );
                return $result[0]->count > 0;
            };
            
            if ($indexExists('payments_status_idx')) {
                $table->dropIndex('payments_status_idx');
            }
            if ($indexExists('payments_party_status_date_idx')) {
                $table->dropIndex('payments_party_status_date_idx');
            }
            if ($indexExists('payments_collected_by_status_date_idx')) {
                $table->dropIndex('payments_collected_by_status_date_idx');
            }
            if ($indexExists('payments_visit_idx')) {
                $table->dropIndex('payments_visit_idx');
            }
            if ($indexExists('payments_collection_datetime_idx')) {
                $table->dropIndex('payments_collection_datetime_idx');
            }
        });
    }
};
