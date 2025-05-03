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
                'title' => 'Payment Information',
                'description' => 'You will be notified your payment related information.',
                'type' => 'payment_information',
                'role' => 'user',
                'status' => 'active',
            ],
            // [
            //     'title' => 'Message Information',
            //     'description' => 'You will be notified message related information.',
            //     'type' => 'message_information',
            //     'role' => 'user',
            //     'status' => 'active',
            // ],
            [
                'title' => 'Others Notification',
                'description' => 'You will be notified when a new alert is received.',
                'type' => 'others_notification',
                'role' => 'user',
                'status' => 'active',
            ],
            // host notifications
            [
                'title' => 'Others Notification',
                'description' => 'You will be notified when a new alert is received.',
                'type' => 'others_notification',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Message Information',
                'description' => 'You will be notified message related information.',
                'type' => 'message_information',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'New Reservation Received',
                'description' => 'Hosts will get notified when a new reservation is made.',
                'type' => 'new_reservation_received',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Reservation Remainder',
                'description' => 'Hosts will get notified when a reservation is for booking reminder.',
                'type' => 'reservation_reminder',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Reservation Completed',
                'description' => 'Hosts will get notified when a reservation is completed.',
                'type' => 'reservation_completed',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Reservation Cancelled',
                'description' => 'Hosts will get notified when areservation is cancelled.',
                'type' => 'reservation_cancelled',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Payment Information',
                'description' => 'Hosts will be notified payment related information.',
                'type' => 'payment_information',
                'role' => 'host',
                'status' => 'active',
            ],
            [
                'title' => 'Reservation Extention Request',
                'description' => 'Hosts will be notified when a reservation extension request is made.',
                'type' => 'extension_reservation_received',
                'role' => 'host',
                'status' => 'active',
            ],
        ];

        foreach ($notifications as $notification) {
            CustomNotification::create($notification);
        }
    }
}
