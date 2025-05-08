<?php

namespace App\Services\API\V1\User\NotificationOrMail;

use App\Helpers\Helper;
use App\Models\CustomNotification;
use App\Models\User;
use App\Models\UserCustomNotification;
use App\Notifications\InfoNotification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Pest\ArchPresets\Custom;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class NotificationOrMailService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    public function sendNotificationAndMail($user = null, $messages = null, $type = null, $subject = null, $data = null)
    {
        if ($user && $type) {
            // check user notification settings on
            $checkNotificationSetting = $this->userNotificationSetting($user, $type);
            // check user notification settings on
            if (!$checkNotificationSetting) {
                Log::info('User notification setting is off for user: ' . $user->name);
                return;
            }
            // $check = User::where('email', 'husoaib422@gmail.com')->first();
            if ($data) {
                $qrCodeImage = $this->qrCodeGenerator($data);
            }
            Log::info('QR Code generated: ' . $qrCodeImage);
            // dd($qrCodeImage);
            $notificationData = [
                'title' => $subject,
                'message' => $messages,
                'url' => '',
                'type' => $type,
                'thumbnail' => asset('backend/admin/assets/images/messages_user.png' ?? ''),
                'qrCodeImage' => $qrCodeImage ?? null,
                'user' => $this->user,
                'subject' => $subject,
            ];
            $user->notify(new InfoNotification($notificationData));
            Log::info('Notification sent to user: ' . $user->name);
        }

    }



    private function qrCodeGenerator($data)
    {
        try {
            $fileName = 'booking_' . time() . '.svg';
            $relativePath = 'qr-codes/' . $fileName;
            $fullPath = public_path('uploads/' . $relativePath);

            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $qrCode = QrCode::format('svg')->size(300)->encoding('UTF-8')->generate($data);

            // Save the image to disk
            file_put_contents($fullPath, $qrCode);

            Log::info('QR Code generated: ' . $relativePath);
            return 'uploads/' . $relativePath;
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
            return null;
        }
    }

    private function userNotificationSetting($user, $type)
    {
        $userNotificationSetting = CustomNotification::where('status', 'active')
            ->where('type', $type)
            ->whereHas('userCustomNotifications', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'active');
            })
            ->first();
        if ($userNotificationSetting) {
            return true;
        }
        return false;
    }

}