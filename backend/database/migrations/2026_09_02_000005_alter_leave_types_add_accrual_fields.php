<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_types', 'accrual_frequency')) {
                $table->enum('accrual_frequency', ['annual', 'monthly'])->default('annual')->after('days_per_year');
            }
            if (! Schema::hasColumn('leave_types', 'days_per_month')) {
                $table->decimal('days_per_month', 5, 2)->default(0.00)->after('accrual_frequency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (Schema::hasColumn('leave_types', 'accrual_frequency')) {
                $table->dropColumn(['accrual_frequency', 'days_per_month']);
            }
        });
    }
};
