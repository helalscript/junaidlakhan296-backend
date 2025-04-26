<?php

namespace App\Services\API\V1\User\Notification;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserNotificationService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    /**
     * Fetch all resources.
     *
     * @return mixed
     */
    public function index($request)
    {
        try {

        } catch (Exception $e) {
            Log::error("UserNotificationService::index" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        try {

        } catch (Exception $e) {
            Log::error("UserNotificationService::index" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store a new resource.
     *
     * @param array $validatedData
     * @return mixed
     */
    public function store(array $validatedData)
    {
        try {

        } catch (Exception $e) {
            Log::error("UserNotificationService::store" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Display a specific resource.
     *
     * @param int $id
     * @return mixed
     */
    public function show(int $id)
    {
        try {

        } catch (Exception $e) {
            Log::error("UserNotificationService::show" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Show the form for editing a resource.
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id)
    {
        try {

        } catch (Exception $e) {
            Log::error("UserNotificationService::edit" . $e->getMessage());
            throw $e;
        }
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

    /**
     * Delete a specific resource.
     *
     * @param int $id
     * @return mixed
     */
    public function destroy(int $id)
    {
        try {
            // Logic to delete a specific resource
        } catch (Exception $e) {
            Log::error("UserNotificationService::destroy" . $e->getMessage());
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