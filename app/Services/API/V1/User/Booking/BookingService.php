<?php

namespace App\Services\API\V1\User\Booking;

use App\Enums\NotificationType;
use App\Models\Booking;
use App\Models\BookingPlatformFee;
use App\Models\DailyPricing;
use App\Models\HourlyPricing;
use App\Models\MonthlyPricing;
use App\Models\ParkingSpace;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Services\API\V1\User\NotificationOrMail\NotificationOrMailService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

class BookingService
{
    protected $user;
    protected $notificationOrMailService;
    public function __construct(NotificationOrMailService $notificationOrMailService)
    {
        $this->user = Auth::user();
        $this->notificationOrMailService = $notificationOrMailService;
    }
    /**
     * Fetch all resources.
     *
     * @return mixed
     */

    public function index($request)
    {
        try {
            $status = $request->status;
            $bookings = Booking::with(['parkingSpace:id,slug,title,gallery_images,address,latitude,longitude', 'payment'])
                ->where('user_id', $this->user->id)
                ->select('id', 'unique_id', 'parking_space_id', 'number_of_slot', 'start_time', 'end_time', 'status', 'created_at')
                ->when($status, fn($query) => $query->where('status', $status))
                ->latest()
                ->paginate($request->per_page ?? 25);
            $bookings->getCollection()->transform(function ($booking) {
                return $this->applyTimeStatus($booking);
            });
            return $bookings;
        } catch (Exception $e) {
            Log::error("BookingService::index " . $e->getMessage());
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
            // $startDateTime = Carbon::parse($validatedData['booking_date_start'] . ' ' . $validatedData['booking_time_start'])->format('Y-m-d H:i:s');
            // $endDateTime = Carbon::parse($validatedData['booking_date_end'] . ' ' . $validatedData['booking_time_end'])->format('Y-m-d H:i:s');

            $startDateTime = Carbon::parse($validatedData['start_time'])->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($validatedData['end_time'])->format('Y-m-d H:i:s');
            // dd($startDateTime);
            $validatedData['start_time'] = $startDateTime;
            $validatedData['end_time'] = $endDateTime;

            $validatedData['booking_date_start'] = Carbon::parse($validatedData['start_time'])->format('Y-m-d');
            $validatedData['booking_date_end'] = Carbon::parse($validatedData['end_time'])->format('Y-m-d');
            $validatedData['booking_time_start'] = Carbon::parse($validatedData['start_time'])->format('H:i:s');
            $validatedData['booking_time_end'] = Carbon::parse($validatedData['end_time'])->format('H:i:s');
            // dd($validatedData);

            $validatedData['user_id'] = $this->user->id;
            $validatedData['unique_id'] = (string) Str::uuid();
            $checkPricingType = $this->checkPricingType($validatedData);
            $validatedData['estimated_hours'] = $checkPricingType->estimated_hours;
            $validatedData['estimated_price'] = $checkPricingType->estimated_price;
            $validatedData['platform_fee'] = $this->platformFee($validatedData['estimated_price']);
            $validatedData['total_price'] = ($checkPricingType->estimated_price + $validatedData['platform_fee']) * $validatedData['number_of_slot'];
            $singlePrice = $checkPricingType->rate;
            $validatedData['per_hour_price'] = $singlePrice;
            $this->checkParkingSlotAvailbelity($validatedData);
            // Create the booking
            $booking = Booking::create($validatedData);
            $this->platformFeeAssign($booking->id);
            // notification send
            $qrData = <<<EOT
            📌 Booking Confirmation

            🅿️ Parking Space: {$booking->parkingSpace->title}
            📍 Address: {$booking->parkingSpace->address}
            🌐 Location: https://www.google.com/maps?q={$booking->parkingSpace->latitude},{$booking->parkingSpace->longitude}

            🕒 Time:
            {$booking->start_time->format('M d, Y h:i A')}
            to
            {$booking->end_time->format('M d, Y h:i A')}

            📝 Description:
            Your booking has been confirmed.
            EOT;
            // Send notification to user
            $this->notificationOrMailService->sendNotificationAndMail(
                $booking?->parkingSpace?->user,
                'You have a new reservation request. Please check your dashboard. within 1hr not accept or auto reject the request',
                NotificationType::NewReservationReceivedNotification,
                'New Reservation Received',
                $qrData
            );
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
            Log::info('Booking day names: ' . json_encode($dayNames));
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

    public function show($unique_id)
    {
        try {
            $booking = Booking::with(['parkingSpace:id,slug,title,gallery_images,address,latitude,longitude', 'payment'])
                ->where('user_id', $this->user->id)
                ->select('id', 'unique_id', 'parking_space_id', 'number_of_slot', 'start_time', 'end_time', 'status', 'created_at')
                ->where('unique_id', $unique_id)
                ->firstOrFail();

            $now = Carbon::now();
            $start = Carbon::parse($booking->start_time);
            $end = Carbon::parse($booking->end_time);

            $booking->is_critical = false;
            $booking->is_expired = false;
            $booking->is_running = false;

            // filter added time for reservation
            $booking = $this->applyTimeStatus($booking);

            return $booking;

        } catch (Exception $e) {
            Log::error("BookingService::show " . $e->getMessage());
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


    public function userDashboardData()
    {
        try {
            $bookingCounts = Booking::where('user_id', $this->user->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');
            $data = [
                'booking_pending' => $bookingCounts['pending'] ?? 0,
                'booking_active' => $bookingCounts['active'] ?? 0,
                'booking_confirmed' => $bookingCounts['confirmed'] ?? 0,
                'booking_cancelled' => $bookingCounts['cancelled'] ?? 0,
                'booking_close' => $bookingCounts['close'] ?? 0,
                'booking_completed' => $bookingCounts['completed'] ?? 0,
            ];

            return $data;
        } catch (Exception $e) {
            Log::error("BookingService::userDashboardData" . $e->getMessage());
            throw $e;
        }
    }
    public function userDashboardTransactions($request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $payments = Payment::where('user_id', $this->user->id)
                ->select('id', 'transaction_number', 'booking_id', 'amount', 'status', 'created_at')
                ->with(['booking.parkingSpace:id,address', 'booking:id,parking_space_id'])
                ->paginate($per_page);
            return $payments;
        } catch (Exception $e) {
            Log::error("BookingService::userDashboardData" . $e->getMessage());
            throw $e;
        }
    }

    private function applyTimeStatus($booking)
    {
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
    }


    public function bookingExtendRequestView($validatedData)
    {
        try {
            // dd($validatedData);
            $booking = Booking::where('unique_id', $validatedData['unique_id'])
                ->where('user_id', $this->user->id)
                ->with(['parkingSpace:id,slug,title,gallery_images,address,latitude,longitude', 'payment'])
                ->select('id', 'unique_id', 'parking_space_id', 'number_of_slot', 'start_time', 'end_time', 'status', 'created_at', 'pricing_type', 'per_hour_price')
                ->whereIn('status', ['active', 'confirmed', 'pending'])
                ->first();

            if (!$booking) {
                throw new Exception('Booking not found or not allowed', 404);
            }
            //check pricing type
            if ($booking->pricing_type !== $validatedData['extend_type']) {
                throw new Exception('Pricing type not matched', 404);
            }
            $booking = $this->applyTimeStatus($booking);
            //calculate price
            $this->calculateBookingExtendPrice($booking, $validatedData);
            return $booking;
        } catch (Exception $e) {
            Log::error("BookingService::bookingExtendRequest" . $e->getMessage());
            throw $e;
        }
    }
    public function bookingExtendRequestStore($validatedData)
    {
        try {
            // dd($validatedData);
            $booking = Booking::where('unique_id', $validatedData['unique_id'])
                ->where('user_id', $this->user->id)
                ->with(['parkingSpace:id,slug,title,gallery_images,address,latitude,longitude', 'payment'])
                ->select('id', 'unique_id', 'parking_space_id', 'number_of_slot', 'start_time', 'end_time', 'status', 'created_at')
                ->whereIn('status', ['active', 'confirmed', 'pending'])
                ->first();

            if (!$booking) {
                throw new Exception('Booking not found or not allowed', 404);
            }
            //check pricing type
            if ($booking->pricing_type !== $validatedData['extend_type']) {
                throw new Exception('Pricing type not matched', 404);
            }
            $booking = $this->applyTimeStatus($booking);
            //calculate price
            $price = $this->calculateBookingExtendPrice($booking, $validatedData);
            dd($booking->toArray());
            return $booking;
        } catch (Exception $e) {
            Log::error("BookingService::bookingExtendRequest" . $e->getMessage());
            throw $e;
        }
    }


    private function calculateBookingExtendPrice($booking, $validatedData)
    {
        $booking->extension_price = 0;
        $hoursInDay = 24;
        $hoursInMonth = 30 * $hoursInDay;

        switch ($validatedData['extend_type']) {
            case 'hourly':
                $extendTime = (int) $validatedData['extend_time'];
                $basePrice = $booking->per_hour_price * $validatedData['extend_time'];
                $booking->extension_new_end_time = $booking->end_time->copy()->addHours($extendTime)->format('M-d-Y H:i A');
                break;
            case 'daily':
                $extendTime = (int) $validatedData['extend_time'];
                $basePrice = ($booking->per_hour_price / $hoursInDay) * $validatedData['extend_time'];
                $booking->extension_new_end_time = $booking->end_time->copy()->addDays($extendTime)->format('M-d-Y H:i A');
            case 'monthly':
                $extendTime = (int) $validatedData['extend_time'];
                $basePrice = ($booking->per_hour_price / $hoursInMonth) * $validatedData['extend_time'];
                $booking->extension_new_end_time = $booking->end_time->copy()->addDays($extendTime)->format('M-d-Y H:i A');
                break;
            default:
                throw new Exception('Invalid extend type', 400);
        }

        $price = $basePrice * $booking->number_of_slot;
        $fee = $this->platformFee($price);
        $total = $price + $fee;

        // Cast to string with 2 decimal places
        $booking->extension_price = number_format($price, 2, '.', '');
        $booking->extension_fee = number_format($fee, 2, '.', '');
        $booking->extension_total_price = number_format($total, 2, '.', '');

        return true;
    }

}