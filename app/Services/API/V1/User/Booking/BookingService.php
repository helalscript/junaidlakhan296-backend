<?php

namespace App\Services\API\V1\User\Booking;

use App\Models\Booking;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingService
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
            $bookings = Booking::with('parkingSpace')->where('user_id', $this->user->id)->paginate($request->per_page ?? 25);
            return $bookings;
        } catch (Exception $e) {
            Log::error("BookingService::index" . $e->getMessage());
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
            // Combine date + time to generate full timestamps
            $startDateTime = Carbon::parse($validatedData['booking_date'] . ' ' . $validatedData['booking_time_start']);
            $endDateTime = Carbon::parse($validatedData['booking_date'] . ' ' . $validatedData['booking_time_end']);
            $validatedData['user_id'] = $this->user->id;
            $validatedData['booking_date'] = now();
            $validatedData['start_time'] = $startDateTime;
            $validatedData['end_time'] = $endDateTime;
            // Create the booking
            $booking = Booking::create($validatedData);
            return $booking;
        } catch (Exception $e) {
            Log::error("BookingService::store" . $e->getMessage());
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
            $booking = Booking::with('parkingSpace')->where('id', $id)->where('user_id', $this->user->id)->first();
            if (!$booking) {
                throw new Exception('Booking not found or you do not own this booking', 404);
            }
            return $booking;
        } catch (Exception $e) {
            Log::error("BookingService::show" . $e->getMessage());
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
            Log::error("BookingService::update" . $e->getMessage());
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
            $booking = Booking::where('id', $id)->where('user_id', $this->user->id)->first();
            if (!$booking) {
                throw new Exception('Booking not found or you do not own this booking', 404);
            }
            $booking->delete();
            return true;
        } catch (Exception $e) {
            Log::error("BookingService::destroy" . $e->getMessage());
            throw $e;
        }
    }

}