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
            $table->index(['user_id', 'status', 'from_date'], 'idx_leaves_user_status_from_date');
            $table->index(['user_id', 'from_date'], 'idx_leaves_user_from_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('idx_leaves_user_status_from_date');
            $table->dropIndex('idx_leaves_user_from_date');
        });
    }
};
