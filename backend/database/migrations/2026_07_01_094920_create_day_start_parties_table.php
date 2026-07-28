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
        Schema::create('day_start_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_start_id')->constrained('day_starts')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('visit_status', ['pending', 'done'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_start_parties');
    }
};
