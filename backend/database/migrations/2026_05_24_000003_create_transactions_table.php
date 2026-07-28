<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('reference_type')->nullable(); // 'Order', 'Payment'
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->string('type')->default('credit'); // credit (payment received), debit (order billed)
            $table->decimal('amount', 12, 2);
            $table->decimal('running_balance', 12, 2)->default(0);
            
            $table->string('status')->default('completed'); 
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['party_id', 'user_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

