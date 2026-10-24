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
        Schema::table('expense_bills', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'date'], 'idx_expense_bills_user_status_date');
            $table->index(['user_id', 'date'], 'idx_expense_bills_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_bills', function (Blueprint $table) {
            $table->dropIndex('idx_expense_bills_user_status_date');
            $table->dropIndex('idx_expense_bills_user_date');
        });
    }
};
