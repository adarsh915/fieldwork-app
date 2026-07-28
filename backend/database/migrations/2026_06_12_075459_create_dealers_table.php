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
        Schema::create('dealers', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('User ID (salesman/manager) assigned to this dealer');
            
            // Core data columns
            $table->string('dealer_code', 50)->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile', 15);
            $table->string('alternate_mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 15)->nullable();
            
            // Address columns
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            
            // Business columns
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['Db', 'Cr'])->default('Db')->comment('Debit or Credit');
            
            // Status / enum columns
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('dealer_type', ['distributor', 'retailer', 'wholesaler', 'dealer'])->default('dealer');
            
            // Nullable / optional columns
            $table->text('notes')->nullable();
            $table->string('gst_number', 20)->nullable();
            $table->string('pan_number', 10)->nullable();
            
            // Audit columns
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Soft deletes
            $table->softDeletes();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('assigned_to');
            $table->index('status');
            $table->index('city');
            $table->index('dealer_code');
            
            // Foreign key constraints
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
