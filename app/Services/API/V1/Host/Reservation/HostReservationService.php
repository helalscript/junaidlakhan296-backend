<?php

namespace App\Services\API\V1\Host\Reservation;

use App\Models\Booking;
use App\Models\ParkingSpace;
use Carbon\Carbon;
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
                ->with(['user:id,name,avatar', 'parkingSpace:id,address,title'])
                ->select('id', 'unique_id', 'parking_space_id', 'user_id', 'start_time', 'end_time', 'total_price', 'estimated_hours', 'estimated_price', 'status', 'created_at')
                ->latest()
                ->paginate($per_page);
            // filter added time for reservation
            $reservation->getCollection()->transform(function ($booking) {
                $now = Carbon::now();
                $start = Carbon::parse($booking->start_time);
                $end = Carbon::parse($booking->end_time);

                $booking->is_critical = false;
                $booking->is_expired = false;
                $booking->is_running = false;

                if ($now->lt($start)) {
                    // Before start time
                    $diffInMinutes = $now->diffInMinutes($start);
                    $diffInDays = floor($diffInMinutes / (60 * 24));
                    $hours = floor(($diffInMinutes % (60 * 24)) / 60);
                    $minutes = $diffInMinutes % 60;

                    if ($diffInMinutes <= 10) {
                        $booking->is_critical = true;
                    }

                    $status = '';
                    if ($diffInDays > 0) {
                        $status .= "{$diffInDays} Day(s) ";
                    }
                    if ($hours > 0) {
                        $status .= "{$hours} Hour(s) ";
                    }
                    $status .= "{$minutes} Minute(s) Left To Start Parking";

                    $booking->parking_status = $status;

                } elseif ($now->between($start, $end)) {
                    // Parking Running
                    $diffInMinutes = $now->diffInMinutes($end);
                    $diffInDays = floor($diffInMinutes / (60 * 24));
                    $hours = floor(($diffInMinutes % (60 * 24)) / 60);
                    $minutes = $diffInMinutes % 60;

                    $booking->is_running = true;

                    if ($diffInMinutes <= 10) {
                        $booking->is_critical = true;
                    }

                    $status = '';
                    if ($diffInDays > 0) {
                        $status .= "{$diffInDays} Day(s) ";
                    }
                    if ($hours > 0) {
                        $status .= "{$hours} Hour(s) ";
                    }
                    $status .= "{$minutes} Minute(s) Left To End Parking";

                    $booking->parking_status = $status;

                } else {
                    // After end time
                    $booking->is_expired = true;
                    $booking->parking_status = "Parking Time Ended";
                }

                return $booking;
            });

            return $reservation;
        } catch (Exception $e) {
            Log::error("HostReservationService::index" . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Display a specific resource.
     *
     * @param string $unique_id
     * @return mixed
     */
    public function show(string $unique_id)
    {
        try {
            $booking = Booking::where('unique_id', $unique_id)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->with(['payment:id,booking_id,status', 'user:id,name,avatar,email,phone', 'parkingSpace:id,title,address,description,latitude,longitude','parkingSpace.spotDetails:id,parking_space_id,icon,details','vehicleDetail:id,registration_number,type,make,model,license_plate_number_eng,license_plate_number_ara'])
                ->select('id', 'unique_id', 'parking_space_id', 'vehicle_details_id','user_id', 'start_time', 'end_time', 'total_price', 'estimated_hours', 'estimated_price', 'status', 'created_at')
                ->firstOrFail();

            // Time status logic
            $now = Carbon::now();
            $start = Carbon::parse($booking->start_time);
            $end = Carbon::parse($booking->end_time);

            $booking->is_critical = false;
            $booking->is_expired = false;
            $booking->is_running = false;

            if ($now->lt($start)) {
                $diffInMinutes = $now->diffInMinutes($start);
                $diffInDays = floor($diffInMinutes / (60 * 24));
                $hours = floor(($diffInMinutes % (60 * 24)) / 60);
                $minutes = $diffInMinutes % 60;

                if ($diffInMinutes <= 10) {
                    $booking->is_critical = true;
                }

                $status = '';
                if ($diffInDays > 0) {
                    $status .= "{$diffInDays} Day(s) ";
                }
                if ($hours > 0) {
                    $status .= "{$hours} Hour(s) ";
                }
                $status .= "{$minutes} Minute(s) Left To Start Parking";

                $booking->parking_status = $status;

            } elseif ($now->between($start, $end)) {
                $diffInMinutes = $now->diffInMinutes($end);
                $diffInDays = floor($diffInMinutes / (60 * 24));
                $hours = floor(($diffInMinutes % (60 * 24)) / 60);
                $minutes = $diffInMinutes % 60;

                $booking->is_running = true;

                if ($diffInMinutes <= 10) {
                    $booking->is_critical = true;
                }

                $status = '';
                if ($diffInDays > 0) {
                    $status .= "{$diffInDays} Day(s) ";
                }
                if ($hours > 0) {
                    $status .= "{$hours} Hour(s) ";
                }
                $status .= "{$minutes} Minute(s) Left To End Parking";

                $booking->parking_status = $status;

            } else {
                $booking->is_expired = true;
                $booking->parking_status = "Parking Time Ended";
            }

            return $booking;

        } catch (Exception $e) {
            Log::error("HostReservationService::show - " . $e->getMessage());
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