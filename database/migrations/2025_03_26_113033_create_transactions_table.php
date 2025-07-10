<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('transaction_number')->nullable();
            $table->decimal('sub_amount', 10, 2)->nullable();
            $table->decimal('service_fee', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->longText('location')->nullable();
            $table->enum('type', ['transfer', 'earning', 'withdraw', 'refunded'])->nullable();
            $table->enum('payment_gateway', ['cash', 'online', 'wallet'])->nullable();
            $table->enum('status', ['pending', 'success', 'cancelled', 'closed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
