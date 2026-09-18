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
        Schema::table('leaves', function (Blueprint $table) {
            $table->boolean('has_conflict')->default(false)->after('status');
            $table->date('conflict_date')->nullable()->after('has_conflict');
            $table->enum('conflict_status', ['none', 'pending_review', 'resolved'])->default('none')->after('conflict_date');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->after('conflict_status');
            $table->enum('resolved_action', ['marked_present', 'kept_as_leave'])->nullable()->after('resolved_by');
            $table->timestamp('resolved_at')->nullable()->after('resolved_action');
            $table->text('resolution_note')->nullable()->after('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn([
                'has_conflict',
                'conflict_date',
                'conflict_status',
                'resolved_by',
                'resolved_action',
                'resolved_at',
                'resolution_note',
            ]);
        });
    }
};
