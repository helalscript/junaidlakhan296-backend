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
        Schema::create('hourly_pricing_days', function (Blueprint $table) {
            $table->id();
        $table->foreignId('hourly_pricing_id')->constrained('hourly_pricings')->onDelete('cascade');
        $table->string('day');
        $table->enum('status', ['available', 'unavailable', 'sold-out', 'close'])->default('available');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_pricing_days');
    }
};
