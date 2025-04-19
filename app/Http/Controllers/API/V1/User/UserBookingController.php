<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserBookingController extends Controller
{
    protected $user;

    /**
     * Set the authenticated user.
     *
     * This constructor method is called after all other service providers have
     * been registered, so we can rely on the Auth facade being available.
     */
    public function __construct()
    {
        $this->user = auth()->user();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $bookings = Booking::with('parkingSpace')->where('user_id', $this->user->id)->get();
            return Helper::jsonResponse(true, 'Bookings fetched successfully', 200, $bookings, true);
        } catch (Exception $e) {
            Log::error("UserBookingController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces', 500);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'parking_space_id' => 'required|exists:parking_spaces,id',
            ]);
            $validatedData['user_id'] = $this->user->id;
            $booking = Booking::create($validatedData);
            return Helper::jsonResponse(true, 'Booking created successfully', 200, $booking, true);
        } catch (Exception $e) {
            Log::error("UserBookingController::store" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to create booking', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $booking = Booking::with('parkingSpace')->where('id', $id)->first();
            return Helper::jsonResponse(true, 'Booking fetched successfully', 200, $booking, true);
        } catch (Exception $e) {
            Log::error("UserBookingController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch booking', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validatedData = $request->validate([
                'parking_space_id' => 'required|exists:parking_spaces,id',
            ]);
            $validatedData['user_id'] = $this->user->id;
            $booking = Booking::where('id', $id)->update($validatedData);
            return Helper::jsonResponse(true, 'Booking updated successfully', 200, $booking, true);
        } catch (Exception $e) {
            Log::error("UserBookingController::update" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update booking', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $booking = Booking::where('id', $id)->delete();
            return Helper::jsonResponse(true, 'Booking deleted successfully', 200, $booking, true);
        } catch (Exception $e) {
            Log::error("UserBookingController::destroy" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to delete booking', 500);
        }
    }
}
