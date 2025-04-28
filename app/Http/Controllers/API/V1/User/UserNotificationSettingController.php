<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\UserNotificationSettingResource;
use App\Services\API\V1\User\Notification\UserNotificationSettingService;
use Exception;
use Illuminate\Http\Request;
use Log;

class UserNotificationSettingController extends Controller
{

    protected $userNotificationService;

    public function __construct(UserNotificationSettingService $userNotificationService)
    {
        $this->userNotificationService = $userNotificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $notifications = $this->userNotificationService->userNotificationSettings($request);
            return Helper::jsonResponse(true, 'Notifications settings fetched successfully', 200, UserNotificationSettingResource::collection($notifications), true);

        } catch (Exception $e) {
            Log::error("UserNotificationService::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch notifications settings' . $e->getMessage(), 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        try {
            $this->userNotificationService->updateUserNotification($id);
            return Helper::jsonResponse(true, 'Notification status updated successfully', 200, null, true);
        } catch (Exception $e) {
            Log::error("UserNotificationService::update" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update notification status', 500);
        }
    }

}
