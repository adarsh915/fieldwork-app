<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // pending = submitted by field worker, awaiting admin approval
            // approved = visible and usable
            // rejected = rejected by admin
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                  ->default('approved')  // existing parties are already approved
                  ->after('is_active');

            $table->unsignedBigInteger('submitted_by')->nullable()->after('approval_status');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');

            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['approval_status', 'submitted_by']);
        });
    }
};
