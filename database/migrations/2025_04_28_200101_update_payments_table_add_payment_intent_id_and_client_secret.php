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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_id');
            $table->string('payment_intent_id')->nullable()->after('payment_method');
            $table->string('client_secret')->nullable()->after('payment_intent_id');
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('payment_method');
            $table->dropColumn('payment_intent_id');
            $table->dropColumn('client_secret');
        });
    }
};
