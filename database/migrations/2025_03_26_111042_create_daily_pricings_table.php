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
        Schema::create('daily_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parking_space_id');
            $table->decimal('rate', 8, 2); // Rate as a decimal
            $table->time('start_time'); // Time-based pricing
            $table->time('end_time');
            $table->date('start_date'); // Date range for pricing
            $table->date('end_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('parking_space_id')->references('id')->on('parking_spaces')->onDelete('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_pricings');
    }
};
