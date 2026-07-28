<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_employee_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_by')
                ->constrained('users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['party_id', 'is_active']);
            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_employee_map');
    }
};
