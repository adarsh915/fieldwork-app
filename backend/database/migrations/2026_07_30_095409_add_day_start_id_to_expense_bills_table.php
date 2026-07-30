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
            $table->foreignId('day_start_id')->nullable()->after('user_id')->constrained('day_starts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_bills', function (Blueprint $table) {
            $table->dropForeign(['day_start_id']);
            $table->dropColumn('day_start_id');
        });
    }
};
