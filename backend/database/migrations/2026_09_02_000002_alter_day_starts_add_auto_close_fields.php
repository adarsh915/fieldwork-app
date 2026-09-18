<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            if (! Schema::hasColumn('day_starts', 'is_auto_closed')) {
                $table->boolean('is_auto_closed')->default(false)->after('status');
            }
            if (! Schema::hasColumn('day_starts', 'flagged_reason')) {
                $table->string('flagged_reason')->nullable()->after('is_auto_closed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            if (Schema::hasColumn('day_starts', 'is_auto_closed')) {
                $table->dropColumn('is_auto_closed');
            }
        });
    }
};
