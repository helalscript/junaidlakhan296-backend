<?php

namespace App\Services\API\V1\User\Notification;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserNotificationSettingService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    /**
     * Update a specific resource.
     *
     * @param int $id
     * @param array $validatedData
     * @return mixed
     */
    public function update(int $id, array $validatedData)
    {
        try {

        } catch (Exception $e) {
            Log::error("UserNotificationService::update" . $e->getMessage());
            throw $e;
        }
    }


    public function userNotificationSettings($request)
    {
        try {
            $per_page = $request->has('per_page') ? $request->per_page : 25;
            $notifications = $this->user->customNotifications()
                ->where('custom_notifications.status', 'active') // notification must be active
                ->paginate($per_page);

            return $notifications;
        } catch (Exception $e) {
            Log::error("UserNotificationService::userNotificationSettings" . $e->getMessage());
            throw $e;
        }
    }

}