<?php

namespace App\Services\API\V1\User\Booking;

use App\Models\Booking;
use App\Models\DailyPricing;
use App\Models\HourlyPricing;
use App\Models\MonthlyPricing;
use App\Models\ParkingSpace;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

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
            DB::beginTransaction();
            // Combine date + time to generate full timestamps
            $startDateTime = Carbon::parse($validatedData['booking_date'] . ' ' . $validatedData['booking_time_start']);
            $endDateTime = Carbon::parse($validatedData['booking_date'] . ' ' . $validatedData['booking_time_end']);
            $validatedData['user_id'] = $this->user->id;
            $validatedData['booking_date'] = now();
            $validatedData['start_time'] = $startDateTime;
            $validatedData['end_time'] = $endDateTime;
            $validatedData['unique_id'] = (string) Str::uuid();
            $this->checkPricingType($validatedData);
            // dd($validatedData);
            $this->checkParkingSlotAvailbelity($validatedData);
            // Create the booking
            $booking = Booking::create($validatedData);
            DB::commit();
            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
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


    private function checkParkingSlotAvailbelity($validatedData)
    {

        $parkingSpace = ParkingSpace::where('status', 'available')->where('id', $validatedData['parking_space_id'])->first();
        if (!$parkingSpace) {
            throw new Exception('Parking Space not available', 404);
        }
        $bookingsQuery = Booking::where('parking_space_id', $validatedData['parking_space_id'])
            ->whereNotIn('status', ['cancelled', 'completed', 'close']);

        // Optional: Filter by date/time range if provided
        if ($validatedData['booking_date']) {
            $bookingsQuery->whereDate('start_time', '>=', $validatedData['booking_date']);
        }
        if ($validatedData['booking_date']) {
            $bookingsQuery->whereDate('end_time', '<=', $validatedData['booking_date']);
        }
        if ($validatedData['start_time']) {
            $bookingsQuery->whereTime('booking_time_start', '<=', $validatedData['start_time']);
        }
        if ($validatedData['end_time']) {
            $bookingsQuery->whereTime('booking_time_end', '>=', $validatedData['end_time']);
        }

        // Sum up number_of_slot (default to 1 if null)
        $bookingCount = $bookingsQuery->get()->sum(function ($booking) {
            return $booking->number_of_slot ?? 1;
        });

        $booking_count = $bookingCount;
        // dd($booking_count);
        $available_slots = max(0, $parkingSpace->total_slots - $bookingCount);
        Log::info('parking_space_id: ' . $validatedData['parking_space_id'] . ' ,Available slots: ' . $available_slots . ', Booking count: ' . $booking_count);
        if ($available_slots < $validatedData['number_of_slot']) {
            throw new Exception(' Not enough available slots', 400);
        }

        // dd($available_slots);
    }

    private function checkPricingType($validatedData)
    {
        // Validate pricing_id based on pricing_type
        if ($validatedData['pricing_type'] == 'hourly') {
            $dayNames = [];

            if ($startDate = $validatedData['booking_date']) {
                $dayNames[] = Carbon::parse($startDate)->format('l');
            }
            $hourlyPricing = HourlyPricing::where('id', $validatedData['pricing_id'])
                ->whereHas('days', function ($q) use ($dayNames) {
                    if (!empty($dayNames)) {
                        $q->whereIn('day', $dayNames);
                    }
                    $q->where('status', 'available'); // Filter days by status
                })
                ->Where('status', 'active')
                ->When($validatedData['booking_time_start'], function ($query) use ($validatedData) {
                    $query->whereTime('start_time', '<=', $validatedData['booking_time_start'])
                        ->whereTime('end_time', '>=', $validatedData['booking_time_end']);
                })
                ->first();
            if (!$hourlyPricing) {
                throw new Exception(' Hourly pricing not found', 404);
            }
            Log::info($hourlyPricing);
        } elseif ($validatedData['pricing_type'] == 'daily') {
            $dailyPricing = DailyPricing::where('id', $validatedData['pricing_id'])->Where('status', 'active')->first();
            if (!$dailyPricing) {
                throw new Exception('Daily pricing not found', 404);
            }
            Log::info($dailyPricing);
        } elseif ($validatedData['pricing_type'] == 'monthly') {
            $monthlyPricing = MonthlyPricing::where('id', $validatedData['pricing_id'])->Where('status', 'active')->first();
            if (!$monthlyPricing) {
                throw new Exception('Monthly pricing not found', 404);
            }
            Log::info($monthlyPricing);
        }
        ;
    }
}