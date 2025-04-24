<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create promo codes using factory
        PromoCode::factory()->count(10)->create();

        // Optional: Add specific promo codes manually
        PromoCode::create([
            'code' => 'WELCOME50',
            'value' => 50.00,
            'uses_limit' => 100,
            'start_time' => now(),
            'end_time' => now()->addDays(30),
            'status' => 'active',
        ]);

        PromoCode::create([
            'code' => 'SPRING25',
            'value' => 25.00,
            'uses_limit' => 50,
            'start_time' => now(),
            'end_time' => now()->addDays(15),
            'status' => 'inactive',
        ]);
    }
}
