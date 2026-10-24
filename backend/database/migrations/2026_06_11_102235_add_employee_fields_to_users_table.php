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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('employee_id', 50)->nullable()->unique()->after('phone');
            $table->text('address')->nullable()->after('employee_id');
            $table->date('date_of_birth')->nullable()->after('address');
            $table->date('hire_date')->nullable()->after('date_of_birth');
            $table->string('department', 100)->nullable()->after('hire_date');
            $table->string('position', 100)->nullable()->after('department');
            $table->decimal('salary', 10, 2)->nullable()->after('position');
            $table->boolean('is_active')->default(true)->after('salary');
            $table->string('profile_image')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'employee_id',
                'address',
                'date_of_birth',
                'hire_date',
                'department',
                'position',
                'salary',
                'is_active',
                'profile_image',
            ]);
        });
    }
};
