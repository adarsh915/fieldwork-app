<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trips')) {
            Schema::create('trips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('day_start_id')->nullable()->constrained('day_starts')->nullOnDelete();
                $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->nullOnDelete();
                $table->string('vehicle_no')->nullable();
                $table->string('start_location')->nullable();
                $table->string('end_location')->nullable();
                $table->decimal('opening_km', 10, 2)->nullable();
                $table->decimal('closing_km', 10, 2)->nullable();
                $table->decimal('distance_km', 10, 2)->default(0);
                $table->decimal('applied_rate_per_km', 8, 2)->nullable();
                $table->string('applied_rate_source', 50)->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->date('date');
                $table->enum('status', ['pending', 'completed', 'approved', 'rejected'])->default('completed');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'date']);
                $table->index(['status', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
