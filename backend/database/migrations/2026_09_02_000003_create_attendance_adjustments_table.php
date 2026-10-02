<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('original_status')->nullable();
            $table->string('new_status'); // present, half_day_present, short_shift, on_leave, holiday, weekly_off, absent
            
            $table->time('original_start_time')->nullable();
            $table->time('new_start_time')->nullable();
            $table->time('original_end_time')->nullable();
            $table->time('new_end_time')->nullable();

            $table->decimal('original_km', 10, 2)->nullable();
            $table->decimal('new_km', 10, 2)->nullable();

            $table->text('reason'); // Required rationale for admin audit
            $table->foreignId('adjusted_by')->constrained('users');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
    }
};
