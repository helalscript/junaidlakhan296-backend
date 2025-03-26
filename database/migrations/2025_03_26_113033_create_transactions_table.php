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
            $table->foreignId('sender_id')->constrained('users')->onDelete('set null');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('set null');
            $table->string('transaction_number')->nullable();
            $table->decimal('sub_amount', 10, 2);
            $table->decimal('service_fee', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->longText('location')->nullable();
            $table->enum('type', ['transfer', 'earning', 'withdraw', 'refunded']);
            $table->enum('payment_gateway', ['cash', 'online', 'wallet']);
            $table->enum('status', ['pending', 'success', 'cancelled', 'closed']);
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
