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
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->string('recipient_type', 50)->default('all')->index();
            $table->unsignedBigInteger('recipient_user_id')->nullable()->index();
            $table->string('title', 150);
            $table->text('body');
            $table->string('screen', 100)->nullable()->default('Home');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('devices_reached')->default(0);
            $table->string('status', 50)->default('delivered');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
