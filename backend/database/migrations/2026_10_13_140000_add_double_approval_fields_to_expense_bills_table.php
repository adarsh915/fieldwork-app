<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Change status column to VARCHAR(30) to allow new statuses ('temp_approved', 'manager_rejected')
        try {
            DB::statement("ALTER TABLE expense_bills MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // Fallback for sqlite/other
            Schema::table('expense_bills', function (Blueprint $table) {
                $table->string('status', 30)->default('pending')->change();
            });
        }

        // 2. Add audit and double approval fields
        Schema::table('expense_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_bills', 'temp_approved_by')) {
                $table->foreignId('temp_approved_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expense_bills', 'temp_approved_at')) {
                $table->timestamp('temp_approved_at')->nullable()->after('temp_approved_by');
            }
            if (!Schema::hasColumn('expense_bills', 'temp_approved_note')) {
                $table->text('temp_approved_note')->nullable()->after('temp_approved_at');
            }
            if (!Schema::hasColumn('expense_bills', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('temp_approved_note');
            }
            if (!Schema::hasColumn('expense_bills', 'final_note')) {
                $table->text('final_note')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('expense_bills', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('final_note')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expense_bills', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('expense_bills', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_bills', function (Blueprint $table) {
            $table->dropForeign(['temp_approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'temp_approved_by',
                'temp_approved_at',
                'temp_approved_note',
                'approved_at',
                'final_note',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
