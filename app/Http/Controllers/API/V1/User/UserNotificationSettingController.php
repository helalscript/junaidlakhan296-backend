<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
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
            return Helper::jsonResponse(true, 'Notifications settings fetched successfully', 200, $notifications, true);
        } catch (Exception $e) {
            Log::error("UserNotificationService::index" . $e->getMessage());
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

}
