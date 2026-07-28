<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_starts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // treating as employee_id
            $table->date('date');
            $table->foreignId('tour_purpose_id')->constrained();
            $table->foreignId('tour_type_id')->constrained();
            $table->foreignId('vehicle_type_id')->constrained();
            $table->string('vehicle_no')->nullable();
            
            $table->decimal('opening_km', 10, 2)->nullable();
            $table->string('opening_km_photo')->nullable();
            $table->decimal('closing_km', 10, 2)->nullable();
            $table->string('closing_km_photo')->nullable();
            
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_starts');
    }
};
