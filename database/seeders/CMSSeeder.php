<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class CMSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('c_m_s')->insert([
            ['page' => 'home_page', 'section' => 'banner', 'title' => 'Find & Book a Parking Spot Near You', 'sub_title' => NULL, 'image' => NULL, 'background_image' => 'uploads/banner/1753701624-1753701624-frame-2147224274-1.png', 'description' => 'Secure and convenient parking spaces at your fingertips', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:20:24'), 'updated_at' => Carbon::parse('2025-07-28 23:20:24')],
            ['page' => 'home_page', 'section' => 'how_it_work', 'title' => 'How It Works?', 'sub_title' => 'Find and book parking spots in just three easy steps', 'image' => NULL, 'background_image' => NULL, 'description' => NULL, 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:24:56'), 'updated_at' => Carbon::parse('2025-07-28 23:24:56')],
            ['page' => 'home_page', 'section' => 'how_it_work_container', 'title' => 'Search & Find Parking', 'sub_title' => NULL, 'image' => 'uploads/HowItWorkContainer/1753701924-1753701924-frame-2147224244.png', 'background_image' => NULL, 'description' => 'Enter your location and find available parking spots near you. Real-time availability ensures you\'ll always find a spot.', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:25:24'), 'updated_at' => Carbon::parse('2025-07-28 23:25:24')],
            ['page' => 'home_page', 'section' => 'how_it_work_container', 'title' => 'Book & Pay Securely', 'sub_title' => NULL, 'image' => 'uploads/HowItWorkContainer/1753701940-1753701940-frame-2147224246.png', 'background_image' => NULL, 'description' => 'Choose your parking duration and pay securely with your preferred payment method. Instant confirmation guaranteed.', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:25:40'), 'updated_at' => Carbon::parse('2025-07-28 23:25:40')],
            ['page' => 'home_page', 'section' => 'how_it_work_container', 'title' => 'Park & Navigate Easily', 'sub_title' => NULL, 'image' => 'uploads/HowItWorkContainer/1753701954-1753701954-frame-2147224247.png', 'background_image' => NULL, 'description' => 'Get detailed directions to your parking spot. Use your preferred navigation app for turn-by-turn guidance.', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:25:54'), 'updated_at' => Carbon::parse('2025-07-28 23:25:54')],
            ['page' => 'home_page', 'section' => 'why_choose_us', 'title' => 'Why Choose Us?', 'sub_title' => 'Experience hassle-free parking with our trusted platform', 'image' => 'uploads/WhyChooseUs/1753702096-1753702096-microsoftteams-image-125-1.png', 'background_image' => NULL, 'description' => NULL, 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:26:30'), 'updated_at' => Carbon::parse('2025-07-28 23:28:16')],
            ['page' => 'home_page', 'section' => 'why_choose_us_container', 'title' => 'Secure & verified hosts', 'sub_title' => NULL, 'image' => 'uploads/WhyChooseUsContainer/1753702052-1753702052-frame-2147224292.png', 'background_image' => NULL, 'description' => 'Every parking host undergoes thorough verification. Your vehicle\'s safety is our top priority with trusted and reliable parking spaces.', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:27:32'), 'updated_at' => Carbon::parse('2025-07-28 23:27:32')],
            ['page' => 'home_page', 'section' => 'why_choose_us_container', 'title' => 'Affordable Rates', 'sub_title' => NULL, 'image' => 'uploads/WhyChooseUsContainer/1753702071-1753702071-frame-17.png', 'background_image' => NULL, 'description' => 'Find the perfect parking spot that fits your budget. Transparent pricing with no hidden fees, saving you money on every booking.', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:27:51'), 'updated_at' => Carbon::parse('2025-07-28 23:27:51')],
            ['page' => 'home_page', 'section' => 'why_choose_us_container', 'title' => 'Easy Booking & payments', 'sub_title' => NULL, 'image' => 'uploads/WhyChooseUsContainer/1753702085-1753702085-frame-2147224292-1.png', 'background_image' => NULL, 'description' => 'Book your parking spot in just a few clicks. Simple, secure payment process with instant confirmation for a hassle-free experience.', 'sub_description' => NULL, 'button_text' => NULL, 'link_url' => NULL, 'status' => 'active', 'created_at' => Carbon::parse('2025-07-28 23:28:05'), 'updated_at' => Carbon::parse('2025-07-28 23:28:05')]
        ]);
    }
}
