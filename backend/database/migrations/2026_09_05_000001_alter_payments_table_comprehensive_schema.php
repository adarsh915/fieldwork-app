<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing simple table and recreate with full enterprise schema
        Schema::dropIfExists('payments');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 50)->unique();                          // e.g. RCP-20260905-1234
            $table->foreignId('party_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('collected_by')->constrained('users')->cascadeOnDelete(); // Field Rep
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();

            // Financial Details
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['cash', 'upi', 'bank_transfer', 'cheque']);
            $table->string('reference_number', 100)->nullable();                // UPI UTR / Cheque No / Bank Ref
            $table->string('proof_photo_url', 255)->nullable();                 // Cheque image / receipt proof

            // Location & Timestamp
            $table->decimal('collection_latitude', 10, 7)->nullable();
            $table->decimal('collection_longitude', 10, 7)->nullable();
            $table->timestamp('collection_datetime');                           // Server timestamp

            // Allocation & Remarks
            $table->unsignedBigInteger('allocated_invoice_id')->nullable();
            $table->text('notes')->nullable();

            // Workflow Status
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');

            // Admin Confirmation Fields
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Idempotency check key (prevent double-taps)
            $table->string('idempotency_key', 64)->nullable()->unique();

            $table->timestamps();

            // Indices
            $table->index(['party_id', 'status']);
            $table->index(['collected_by', 'status']);
            $table->index('collection_datetime');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
