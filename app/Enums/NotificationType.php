<?php

namespace App\Enums;

enum NotificationType: string
{
    case BookingConfirmation = 'booking_confirmation';
    case BookingReminder = 'booking_reminder';
    case BookingCancelled = 'booking_cancelled';
    case PaymentReceived = 'payment_received';
    case NewMessageReceived = 'new_message_received';
    case NewBookingReceived = 'new_booking_received';
    case PayoutProcessed = 'payout_processed';
    case OthersNotification = 'others_notification';
    case ContactSupport = 'contact_support';
    case InfoNotification = 'info_notification';
    case InfoNotificationWithQrCode = 'info_notification_with_qrcode';
    case Paymnet = 'payment';
    

}