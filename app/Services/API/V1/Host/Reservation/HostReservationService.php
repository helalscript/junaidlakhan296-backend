<?php

namespace App\Services\API\V1\Host\Reservation;

use App\Models\Booking;
use App\Models\ParkingSpace;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HostReservationService
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
            $per_page = $request->per_page ?? 25;
            $userParkingSpaces = ParkingSpace::where('user_id', $this->user->id)->pluck('id')->toArray();
            $reservation = Booking::whereIn('parking_space_id', $userParkingSpaces)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->with(['payment', 'user'])
                ->latest()
                ->paginate($per_page);
            return $reservation;
        } catch (Exception $e) {
            Log::error("HostReservationService::index" . $e->getMessage());
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
            Log::error("HostReservationService::index" . $e->getMessage());
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
            Log::error("HostReservationService::store" . $e->getMessage());
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
            Log::error("HostReservationService::show" . $e->getMessage());
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
            Log::error("HostReservationService::edit" . $e->getMessage());
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
            Log::error("HostReservationService::update" . $e->getMessage());
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
            Log::error("HostReservationService::destroy" . $e->getMessage());
            throw $e;
        }
    }

}