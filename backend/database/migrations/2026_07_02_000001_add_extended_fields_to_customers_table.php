<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('party_code', 50)->nullable()->unique()->after('id');
            $table->string('owner_name')->nullable()->after('name');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('pincode', 10)->nullable()->after('state');
            $table->string('party_type', 50)->nullable()->after('pincode');
            $table->string('category', 100)->nullable()->after('party_type');
            $table->decimal('credit_limit', 15, 2)->default(0)->after('category');
            $table->integer('credit_days')->default(0)->after('credit_limit');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('credit_days');
            $table->boolean('is_active')->default(true)->after('opening_balance');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'party_code', 'owner_name', 'city', 'state', 'pincode',
                'party_type', 'category', 'credit_limit', 'credit_days',
                'opening_balance', 'is_active',
            ]);
        });
    }
};
