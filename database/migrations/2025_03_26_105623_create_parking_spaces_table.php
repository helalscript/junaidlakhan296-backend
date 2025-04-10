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
        Schema::create('parking_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('unique_id')->unique();
            $table->string('title');
            $table->string('type_of_spot');
            $table->string('max_vehicle_size');
            $table->integer('total_slots');
            $table->longText('description');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->longText('address')->nullable();
            $table->json('gallery_images')->nullable();
            $table->string('slug')->unique();
            $table->enum('status', ['available', 'unavailable', 'sold-out', 'close'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spaces');
    }
};
