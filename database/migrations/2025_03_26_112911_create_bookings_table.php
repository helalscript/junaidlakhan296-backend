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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parking_space_id')->constrained('parking_spaces')->onDelete('cascade');
            $table->foreignId('vehicle_details_id')->constrained('vehicle_details')->onDelete('cascade');
            $table->timestamp('start_time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled','close']);
            $table->timestamps();
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
