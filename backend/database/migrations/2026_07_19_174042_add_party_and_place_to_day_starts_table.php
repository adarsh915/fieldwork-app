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
            $table->foreignId('party_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('visit_place')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
            $table->dropColumn(['party_id', 'visit_place']);
        });
    }
};
