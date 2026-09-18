<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->unsignedSmallInteger('days_per_year')->default(0); // 0 = unlimited
            $table->boolean('carry_forward')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default leave types
        DB::table('leave_types')->insert([
            ['name' => 'Casual Leave',  'code' => 'CL', 'days_per_year' => 12, 'carry_forward' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Earned Leave',  'code' => 'EL', 'days_per_year' => 12, 'carry_forward' => true,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sick Leave',    'code' => 'SL', 'days_per_year' => 6,  'carry_forward' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Unpaid Leave',  'code' => 'UL', 'days_per_year' => 0,  'carry_forward' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
