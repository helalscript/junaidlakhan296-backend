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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parking_space_id')->constrained('parking_spaces')->onDelete('cascade');
            $table->foreignId('vehicle_details_id')->constrained('vehicle_details')->onDelete('cascade');
            $table->integer('number_of_slot')->nullable();
            $table->enum('pricing_type', ['hourly', 'daily', 'monthly']);
            $table->unsignedBigInteger('pricing_id')->nullable();
            $table->string('estimated_hours')->nullable();
            $table->decimal('estimated_price', 10, 2)->nullable();
            $table->decimal('platform_fee', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->date('booking_date')->nullable();
            $table->time('booking_time_start')->nullable();
            $table->time('booking_time_end')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['pending', 'confirmed','active', 'cancelled', 'close','completed'])->default('pending');
            $table->timestamps();
            $table->index(['parking_space_id', 'booking_date', 'booking_time_start', 'booking_time_end'], 'booking_availability_index');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
