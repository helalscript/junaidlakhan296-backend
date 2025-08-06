<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::create([
            'title' => 'Rakkin – Parking Directory and Booking Solution',
            'system_name' => 'Rakkin – Parking Directory and Booking Solution',
            'email' => 'info@rakkin.com',
            'contact_number' => Null,
            'company_open_hour' => Null,
            'copyright_text' => '© Copyright 2025, All right reserved',
            'logo' => 'uploads/logos/logo.png',
            'favicon' => 'uploads/favicons/favicon.png',
            'address' => 'Suite No. 5, 2nd floor, Al Surooh Business Center  Hail Street, Jeddah 21313, Saudi Arabia',
            'description' => 'https://g.co/kgs/D18pJZE',
        ]);
    }
}
