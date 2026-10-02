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
            $table->boolean('is_after_day_end')->default(false)->after('is_system_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_bills', function (Blueprint $table) {
            $table->dropColumn('is_after_day_end');
        });
    }
};
