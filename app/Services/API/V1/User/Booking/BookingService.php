<?php

namespace App\Services\API\V1\User\Booking;

use App\Models\Booking;
use App\Models\BookingPlatformFee;
use App\Models\DailyPricing;
use App\Models\HourlyPricing;
use App\Models\MonthlyPricing;
use App\Models\ParkingSpace;
use App\Models\PlatformSetting;
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
            $startDateTime = Carbon::parse($validatedData['booking_date_start'] . ' ' . $validatedData['booking_time_start'])->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($validatedData['booking_date_end'] . ' ' . $validatedData['booking_time_end'])->format('Y-m-d H:i:s');
            $validatedData['start_time'] = $startDateTime;
            $validatedData['end_time'] = $endDateTime;
            // dd($startDateTime);
            $validatedData['user_id'] = $this->user->id;
            $validatedData['unique_id'] = (string) Str::uuid();
            // dd($validatedData);

            $checkPricingType = $this->checkPricingType($validatedData);
            $validatedData['estimated_hours'] = $checkPricingType->estimated_hours;
            $validatedData['estimated_price'] = $checkPricingType->estimated_price;
            $validatedData['platform_fee'] = $this->platformFee($validatedData['estimated_price']);
            // dd($validatedData);
            // $validatedData['total_price'] = ;
            $singlePrice = $checkPricingType->rate;
            $validatedData['per_hour_price'] = $singlePrice;
            
            $this->checkParkingSlotAvailbelity($validatedData);
          
            // Create the booking
            $booking = Booking::create($validatedData);
            $this->platformFeeAssign($booking->id);
            DB::commit();
            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("BookingService::store" . $e->getMessage());
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
        if ($validatedData['booking_date_start']) {
            $bookingsQuery->whereDate('start_time', '>=', $validatedData['booking_date_start']);
        }
        if ($validatedData['booking_date_end']) {
            $bookingsQuery->whereDate('end_time', '<=', $validatedData['booking_date_end']);
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

            $startDate = $validatedData['booking_date_start'];
            $endDate = $validatedData['booking_date_end'];
            if ($startDate && !$endDate) {
                $dayNames[] = Carbon::parse($startDate)->format('l');
            } elseif ($startDate && $endDate) {
                $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
                foreach ($period as $date) {
                    $day = $date->format('l');
                    if (!in_array($day, $dayNames)) {
                        $dayNames[] = $day;
                    }
                }
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
            // Log::info($dayNames);
            if (!$hourlyPricing) {
                throw new Exception(' Hourly pricing not found', 404);
            }
            $startTime = $validatedData['booking_time_start'] ?? now()->format('H:i');
            $endTime = $validatedData['booking_time_end'] ?? (new Carbon($startTime))->addHour()->format('H:i');
            $startDate = $validatedData['booking_date_start'] ?? now()->format('Y-m-d');
            $endDate = $validatedData['booking_date_end'];

            if ($startTime && $endTime) {
                $dailyHours = Carbon::parse($startTime)->floatDiffInHours(Carbon::parse($endTime));
                if ($endDate) {
                    $totalHours = Carbon::parse("$startDate $startTime")->floatDiffInHours(Carbon::parse("$endDate $endTime"));
                    Log::info('totalHours' . $totalHours);
                } else {
                    $totalHours = $dailyHours;
                }

                $hourlyPricing->estimated_hours = round($totalHours, 2);
                $hourlyPricing->estimated_price = round($totalHours * $hourlyPricing->rate, 2);
            }
            Log::info($hourlyPricing);
            return $hourlyPricing;




        } elseif ($validatedData['pricing_type'] == 'daily') {
            $dailyPricing = DailyPricing::where('id', $validatedData['pricing_id'])->Where('status', 'active')->first();
            if (!$dailyPricing) {
                throw new Exception('Daily pricing not found', 404);
            }
            Log::info($dailyPricing);
            return $dailyPricing;
        } elseif ($validatedData['pricing_type'] == 'monthly') {
            $monthlyPricing = MonthlyPricing::where('id', $validatedData['pricing_id'])->Where('status', 'active')->first();
            if (!$monthlyPricing) {
                throw new Exception('Monthly pricing not found', 404);
            }
            Log::info($monthlyPricing);
            return $monthlyPricing;
        }
        ;
    }

    private function platformFeeAssign($booking_id)
    {
        $platform_fee = PlatformSetting::where('status', 'active')->get();
        // Log::info("Platform fee: " . $platform_fee);
        if (!$platform_fee) {
            return true;
        } else {
            foreach ($platform_fee as $fee) {
                $platform_fee = BookingPlatformFee::create([
                    'booking_id' => $booking_id,
                    'platform_setting_id' => $fee->id,
                    'key' => $fee->key,
                    'value' => $fee->value,
                ]);
            }
            Log::info("Platform fee assigned booking_id: " . $booking_id);
        }
    }

    private function platformFee($price)
    {
        $platform_fee = PlatformSetting::where('status', 'active')->get();
        // Log::info("Platform fee: " . $platform_fee);
        if (!$platform_fee) {
            return 0;
        }

        $total = 0;
        foreach ($platform_fee as $fee) {
            $total += $price / 100 * $fee->value;
        }

        Log::info("Platform fee total: " . $total);
        return $total;
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