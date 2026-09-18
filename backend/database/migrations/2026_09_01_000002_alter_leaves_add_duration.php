<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Half-day support
            $table->enum('duration', ['full', 'half_am', 'half_pm'])
                ->default('full')
                ->after('to_date');

            // Audit trail
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejected_reason')->nullable()->after('approved_at');

            // Fix leave_type_id — add proper FK (column already exists as unsignedBigInteger)
            $table->foreign('leave_type_id')
                ->references('id')
                ->on('leave_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropColumn(['duration', 'approved_at', 'rejected_reason']);
        });
    }
};
