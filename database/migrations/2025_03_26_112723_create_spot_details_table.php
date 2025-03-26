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
        Schema::create('spot_details', function (Blueprint $table) {
            $table->id();
        $table->foreignId('parking_space_id')->constrained('parking_spaces')->onDelete('cascade');
        $table->string('icon');
        $table->text('details');
        $table->enum('status', ['active', 'inactive']);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_details');
    }
};
