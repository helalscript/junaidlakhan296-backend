<?php

namespace App\Services\API\V1\User\NotificationOrMail;

use App\Models\User;
use App\Notifications\InfoNotification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class NotificationOrMailService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    public function sendNotificationAndMail($user = null, $messages, $type = null, $subject = null, $qrData = null, $descriptionMessage = null, $data = null)
    {
        $Users = User::where('role', 'admin')->where('status', 'active')->get();
        $qrCodeImage = $this->qrCodeGenerator($data, $qrData);
        $notificationData = [
            'title' => 'A new Support Message has been submitted.',
            'message' => $messages,
            'url' => '',
            'type' => $type,
            'thumbnail' => asset('backend/admin/assets/images/messages_user.png' ?? ''),
            'qrCodeImage' => $qrCodeImage,
            'descriptionMessage' => $descriptionMessage,
            'user' => $this->user,
            'customMessage' => $messages,
            'subject' => $subject,
        ];

        foreach ($Users as $admin) {
            $admin->notify(new InfoNotification($notificationData));
            Log::info('Notification sent to admin: ' . $admin->name);
        }
    }

    private function qrCodeGenerator($data, $qrData)
    {
        try {
            $qrImageName = 'qr-codes/booking_' . $data->id . '.png';
            QrCode::format('png')->size(300)->generate($qrData, asset('qr-codes/' . $qrImageName));
            Log::info('QR Code generated and email sent to user: ');
            return $qrImageName;
        } catch (Exception $e) {
            Log::error('Error generating QR code or sending email: ' . $e->getMessage());
        }
    }
}