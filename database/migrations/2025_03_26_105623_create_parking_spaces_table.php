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
            $table->unsignedBigInteger('user_id')->nullable(); // Make user_id nullable
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
            $table->enum('status', ['available', 'unavailable', 'sold-out', 'close']);
            $table->timestamps();
            $table->softDeletes(); // Add soft delete column

            // Add foreign key constraint with onDelete set to NULL
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
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
