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
        // 1. Districts table
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('state', 100)->default('Gujarat');
            $table->string('code', 20)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        // 2. Areas / Beats table
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('pincode', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['district_id', 'is_active']);
            $table->index('name');
        });

        // 3. Add district_id and area_id to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('state')->constrained('districts')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('district_id')->constrained('areas')->nullOnDelete();

            $table->index(['district_id', 'area_id']);
        });

        // 4. Add district_id and area_id to day_starts table
        Schema::table('day_starts', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('tour_type_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('district_id')->constrained('areas')->nullOnDelete();
            $table->string('area_name_custom', 200)->nullable()->after('area_id');

            $table->index(['district_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_starts', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['area_id']);
            $table->dropColumn(['district_id', 'area_id', 'area_name_custom']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['area_id']);
            $table->dropColumn(['district_id', 'area_id']);
        });

        Schema::dropIfExists('areas');
        Schema::dropIfExists('districts');
    }
};
