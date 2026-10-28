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
        Schema::table('day_starts', function (Blueprint $table) {
            if (!Schema::hasColumn('day_starts', 'end_remarks')) {
                $table->text('end_remarks')->nullable()->after('remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            if (Schema::hasColumn('day_starts', 'end_remarks')) {
                $table->dropColumn('end_remarks');
            }
        });
    }
};
