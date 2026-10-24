<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_allowance_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->cascadeOnDelete();
            $table->enum('scope_type', ['global', 'role', 'user'])->default('global');
            $table->unsignedBigInteger('scope_id')->nullable()->comment('role_id for role scope, user_id for user scope, null for global');
            $table->decimal('rate_per_km', 8, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_type_id', 'scope_type', 'scope_id', 'is_active'], 'fuel_rates_lookup_idx');
            $table->index(['effective_from', 'effective_to'], 'fuel_rates_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_allowance_rates');
    }
};
