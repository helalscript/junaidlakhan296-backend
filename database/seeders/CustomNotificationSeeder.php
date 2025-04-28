<?php

namespace Database\Seeders;

use App\Models\CustomNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notifications = [
            [
                'title' => 'Booking Confirmation',
                'description' => 'You will receive a notification when your booking is confirmed.',
                'type' => 'booking_confirmation',
                'role' => 'user',
                'status' => 'active',
            ],
            [
                'title' => 'Booking Reminder',
                'description' => 'You will receive a notification when your booking is for booking reminder.',
                'type' => 'booking_reminder',
                'role' => 'user',
                'status' => 'active',
            ],
            [
                'title' => 'Booking Canclelled',
                'description' => 'You will receive a notification when your booking is cancelled.',
                'type' => 'booking_cancelled',
                'role' => 'user',
                'status' => 'active',
            ],
            [
                'title' => 'Payment Received',
                'description' => 'You will be notified when your payment is successfully processed.',
                'type' => 'payment_received',
                'role' => 'user',
                'status' => 'active',
            ],
            [
                'title' => 'New Message Received',
                'description' => 'You will be notified when a new message is received.',
                'type' => 'new_message_received',
                'role' => 'user',
                'status' => 'active',
            ],
            [
                'title' => 'Others Notification',
                'description' => 'You will be notified when a new alert is received.',
                'type' => 'others_notification',
                'role' => 'user',
                'status' => 'active',
            ],

            [
                'title' => 'Others Notification',
                'description' => 'You will be notified when a new alert is received.',
                'type' => 'others_notification',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'New Message Received',
                'description' => 'You will be notified when a new message is received.',
                'type' => 'new_message_received',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'New Booking Received',
                'description' => 'Hosts will get notified when a new booking is made.',
                'type' => 'new_booking_received',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Payout Processed',
                'description' => 'Hosts will be notified when their payout is processed.',
                'type' => 'payout_processed',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Booking Extention Request',
                'description' => 'Hosts will be notified when a booking extension request is made.',
                'type' => 'payout_processed',
                'role' => 'host',
                'status' => 'active',
            ],
        ];

        foreach ($notifications as $notification) {
            CustomNotification::create($notification);
        }
    }
}
