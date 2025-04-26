<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\API\V1\User\Notification\UserNotificationService;
use Exception;
use Illuminate\Http\Request;
use Log;

class UserNotificationController extends Controller
{

    protected $userNotificationService;

    public function __construct(UserNotificationService $userNotificationService)
    {
        $this->userNotificationService = $userNotificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getAllNotifications(Request $request)
    {
        try {
            $notifications = $this->userNotificationService->userNotificationSettings($request);
            return Helper::jsonResponse(true, 'Notifications settings fetched successfully', 200, $notifications, true);
        } catch (Exception $e) {
            Log::error("UserNotificationService::index" . $e->getMessage());
        }
    }
}
