<?php

namespace App\Services\API\V1\User\ParkingSpace;

use App\Models\Booking;
use App\Models\DailyPricing;
use App\Models\HourlyPricing;
use App\Models\MonthlyPricing;
use App\Models\ParkingSpace;
use App\Models\PlatformSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Request;

class UserParkingSpaceService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function getHourlyPricing($request)
    {
        $perPage = $request->per_page ?? 25;
        $startTime = $request->start_time ?? now()->format('H:i');
        $isStartTime = $request->start_time ?? null;
        $endTime = $isStartTime ? ($request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i')) : (new Carbon($startTime))->addHour()->format('H:i');
        $startDate = $request->start_date ?? now()->format('Y-m-d');
        $endDate = $request->end_date;
        $latitude = $request->latitude ?? 12.9716770;
        $longitude = $request->longitude ?? 77.5946770;
        $radius = $request->radius ?? 500;
        // dd($endTime);
        $dayNames = [];
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

        $haversine = "(6371 * acos(cos(radians(?)) 
                    * cos(radians(parking_spaces.latitude)) 
                    * cos(radians(parking_spaces.longitude) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(parking_spaces.latitude))))";

        $hourlyPricing = HourlyPricing::with([
            'parkingSpace.driverInstructions',
            'parkingSpace.reviews' => function ($query) {
                $query->where('status', 'approved');
            },
            'parkingSpace.spotDetails',
            'days'
        ])
            ->join('parking_spaces', 'hourly_pricings.parking_space_id', '=', 'parking_spaces.id')
            ->where('hourly_pricings.status', 'active')
            ->where('parking_spaces.status', 'available')
            ->whereNotNull('parking_spaces.user_id')
            ->where('parking_spaces.is_verified', true)
            ->whereNull('parking_spaces.deleted_at')
            ->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $radius])
            ->select('hourly_pricings.*')
            ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
            ->whereHas('days', function ($q) use ($dayNames) {
                if (!empty($dayNames)) {
                    $q->whereIn('day', $dayNames);
                }
                $q->where('status', 'available');
            })
            ->when($startTime, fn($q) => $q->whereTime('start_time', '<=', $startTime))
            ->when($endTime, fn($q) => $q->whereTime('end_time', '>=', $endTime))
            ->orderBy('distance', 'asc');

        return $hourlyPricing->paginate($perPage);
    }

    public function getHourlyPricingDetails($id, $request)
    {
        try {
            $startTime = $request->start_time ?? now()->format('H:i');
            $endTime = $request->start_time ? ($request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i')) : (new Carbon($startTime))->addHour()->format('H:i');
            $startDate = $request->start_date ?? now()->format('Y-m-d');
            $endDate = $request->end_date;
            $userLat = $request->latitude;
            $userLng = $request->longitude;
            // get day names
            $dayNames = [];
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
            // Log::info($dayNames);
            $pricing = HourlyPricing::with([
                'parkingSpace.driverInstructions',
                'parkingSpace.reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->select('id', 'parking_space_id', 'user_id', 'comment', 'rating', 'created_at')
                        ->with('user:id,name,avatar');
                },
                'parkingSpace.spotDetails',
                'days'
            ])
                ->whereHas('days', function ($q) {
                    $q->where('status', 'available');
                })
                ->wherehas('parkingSpace', fn($q) => $q->whereNotNull('user_id'))
                ->when($startTime, fn($q) => $q->whereTime('start_time', '<=', $startTime))
                ->when($endTime, fn($q) => $q->whereTime('end_time', '>=', $endTime))
                ->findOrFail($id);

            // boooking slots check
            $bookingCount = $this->getBookingCount($pricing, $startDate, $endDate, $startTime, $endTime);
            $pricing->booking_count = $bookingCount;
            $pricing->available_slots = max(0, $pricing->parkingSpace->total_slots - $bookingCount);

            // Calculate distance
            if ($userLat && $userLng && $pricing->parkingSpace->latitude && $pricing->parkingSpace->longitude) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $pricing->parkingSpace->latitude,
                    $pricing->parkingSpace->longitude
                );
                $pricing->distance = round($distance, 2);
            }

            // Estimate time and price
            if ($startTime && $endTime) {
                $estimate = $this->calculateEstimatedPricing($startDate, $startTime, $endDate, $endTime, $pricing->rate, 'hourly');
                $pricing->estimated_hours = $estimate['estimated_hours'];
                $pricing->estimated_price = $estimate['estimated_price'];
            }
            // Reviews
            $this->attachReviewStats($pricing);
            // platform fee
            $pricing->platform_fee = $this->platformFee() ?? [];
            return $pricing;
        } catch (Exception $e) {
            Log::error("UserParkingSpaceService::getHourlyPricingDetails - " . $e->getMessage());
            throw $e;
        }
    }

    public function getDailyPricing($request)
    {
        try {

            $perPage = $request->per_page ?? 25;
            $startTime = $request->start_time ? (new Carbon($request->start_time))->format('H:i') : now()->format('H:i');
            $startDate = $request->start_time ? (new Carbon($request->start_time))->format('Y-m-d') : now()->format('Y-m-d');

            $isStartTime = $request->start_time ?? null;
            $isStartDate = $request->start_time ?? null;
            $endTime = $isStartTime ? ($request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i')) : (new Carbon($startTime))->addHour()->format('H:i');
            $endDate = $request->end_time ? (new Carbon($request->end_time))->format('Y-m-d') : $startDate;
            $latitude = $request->latitude ?? 12.9716770;
            $longitude = $request->longitude ?? 77.5946770;
            $radius = $request->radius ?? 500;

            // haversine formula
            $haversine = "(6371 * acos(cos(radians(?)) 
                        * cos(radians(parking_spaces.latitude)) 
                        * cos(radians(parking_spaces.longitude) - radians(?)) 
                        + sin(radians(?)) 
                        * sin(radians(parking_spaces.latitude))))";
            //daily pricing
            $dailyPricings = DailyPricing::with([
                'parkingSpace.driverInstructions',
                'parkingSpace.reviews' => function ($query) {
                    $query->where('status', 'approved');
                },
                'parkingSpace.spotDetails'
            ])
                ->join('parking_spaces', 'daily_pricings.parking_space_id', '=', 'parking_spaces.id')
                ->where('daily_pricings.status', 'active')
                ->where('parking_spaces.status', 'available')
                ->whereNotNull('parking_spaces.user_id')
                ->where('parking_spaces.is_verified', true)
                ->whereNull('parking_spaces.deleted_at')
                ->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $radius])
                ->select('daily_pricings.*')
                ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
                ->when(
                    $startDate && $endDate,
                    fn($q) =>
                    $q->whereDate('start_date', '<=', $startDate)
                        ->whereDate('end_date', '>=', $endDate)
                        ->whereTime('start_time', '<=', $startTime)
                        ->whereTime('end_time', '>=', $endTime)
                )
                ->orderBy('distance', 'asc');
            return $dailyPricings->paginate($perPage);

        } catch (Exception $e) {
            Log::error("UserParkingSpaceService::getDailyPricing - " . $e->getMessage());
            throw $e;
        }
    }
    public function getDailyPricingDetails($id, $request)
    {
        try {
            $startTime = $request->start_time ? Carbon::parse($request->start_time)->format('H:i') : now()->format('H:i');
            $startDate = $request->start_time ? Carbon::parse($request->start_time)->format('Y-m-d') : now()->format('Y-m-d');
            $endTime = $request->end_time ? Carbon::parse($request->end_time)->format('H:i') : Carbon::parse($startTime)->addHour()->format('H:i');
            $endDate = $request->end_time ? Carbon::parse($request->end_time)->format('Y-m-d') : $startDate;
            $userLat = $request->latitude;
            $userLng = $request->longitude;
            $pricing = DailyPricing::with([
                'parkingSpace.driverInstructions',
                'parkingSpace.reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->select('id', 'parking_space_id', 'user_id', 'comment', 'rating', 'created_at')
                        ->with('user:id,name,avatar');
                },
                'parkingSpace.spotDetails',
            ])
                ->whereHas('parkingSpace', fn($q) => $q->whereNotNull('user_id')->where('status', 'available')->whereNull('deleted_at')->where('is_verified', true))
                ->where('status', 'active')
                ->findOrFail($id);

            // Bookings for the same parking space
            $bookingCount = $this->getBookingCount($pricing, $startDate, $endDate, $startTime, $endTime);

            $pricing->booking_count = $bookingCount;
            $pricing->available_slots = max(0, $pricing->parkingSpace->total_slots - $bookingCount);
            // Calculate distance
            if ($userLat && $userLng && $pricing->parkingSpace->latitude && $pricing->parkingSpace->longitude) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $pricing->parkingSpace->latitude,
                    $pricing->parkingSpace->longitude
                );
                $pricing->distance = round($distance, 2);
            }
            // Estimate time and price
            if ($startTime && $endTime) {
                $estimate = $this->calculateEstimatedPricing($startDate, $startTime, $endDate, $endTime, $pricing->rate, 'daily');
                $pricing->estimated_hours = $estimate['estimated_hours'];
                $pricing->estimated_price = $estimate['estimated_price'];
            }
            // Reviews
            $this->attachReviewStats($pricing);
            // return $pricing;
            $pricing->platform_fee = $this->platformFee() ?? [];
            // return $pricing;
            return $pricing;

        } catch (Exception $e) {
            Log::error("UserParkingSpaceService::getDailyPricingDetails - " . $e->getMessage());
            throw $e;
        }
    }
    public function getMonthlyPricing($request)
    {
        try {

            $perPage = $request->per_page ?? 25;
            $startTime = $request->start_time ? (new Carbon($request->start_time))->format('H:i') : now()->format('H:i');
            $startDate = $request->start_time ? (new Carbon($request->start_time))->format('Y-m-d') : now()->format('Y-m-d');

            $isStartTime = $request->start_time ?? null;
            $isStartDate = $request->start_time ?? null;
            $endTime = $isStartTime ? ($request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i')) : (new Carbon($startTime))->addHour()->format('H:i');
            $endDate = $request->end_time ? (new Carbon($request->end_time))->format('Y-m-d') : (new Carbon($startDate))->endOfMonth()->format('Y-m-d');
            $latitude = $request->latitude ?? 12.9716770;
            $longitude = $request->longitude ?? 77.5946770;
            $radius = $request->radius ?? 500;

            // haversine formula
            $haversine = "(6371 * acos(cos(radians(?)) 
                        * cos(radians(parking_spaces.latitude)) 
                        * cos(radians(parking_spaces.longitude) - radians(?)) 
                        + sin(radians(?)) 
                        * sin(radians(parking_spaces.latitude))))";
            //monthly pricing
            $monthlyPricings = MonthlyPricing::with([
                'parkingSpace.driverInstructions',
                'parkingSpace.reviews' => function ($query) {
                    $query->where('status', 'approved');
                },
                'parkingSpace.spotDetails'
            ])
                ->join('parking_spaces', 'monthly_pricings.parking_space_id', '=', 'parking_spaces.id')
                ->where('monthly_pricings.status', 'active')
                ->where('parking_spaces.status', 'available')
                ->whereNotNull('parking_spaces.user_id')
                ->where('parking_spaces.is_verified', true)
                ->whereNull('parking_spaces.deleted_at')
                ->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $radius])
                ->select('monthly_pricings.*')
                ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
                ->when(
                    $startDate && $endDate,
                    fn($q) =>
                    $q->whereDate('start_date', '<=', $startDate)
                        ->whereDate('end_date', '>=', $endDate)
                        ->whereTime('start_time', '<=', $startTime)
                        ->whereTime('end_time', '>=', $endTime)
                )
                ->orderBy('distance', 'asc');
            return $monthlyPricings->paginate($perPage);

        } catch (Exception $e) {
            Log::error("UserParkingSpaceService::getMonthlyPricing - " . $e->getMessage());
            throw $e;
        }
    }
    public function getMonthlyPricingDetails($id, $request)
    {
        try {
            $startTime = $request->start_time ? Carbon::parse($request->start_time)->format('H:i') : now()->format('H:i');
            $startDate = $request->start_time ? Carbon::parse($request->start_time)->format('Y-m-d') : now()->format('Y-m-d');
            $endTime = $request->end_time ? Carbon::parse($request->end_time)->format('H:i') : Carbon::parse($startTime)->addHour()->format('H:i');
            $endDate = $request->end_time ? Carbon::parse($request->end_time)->format('Y-m-d') : $startDate;
            $userLat = $request->latitude;
            $userLng = $request->longitude;
            $pricing = MonthlyPricing::with([
                'parkingSpace.driverInstructions',
                'parkingSpace.reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->select('id', 'parking_space_id', 'user_id', 'comment', 'rating', 'created_at')
                        ->with('user:id,name,avatar');
                },
                'parkingSpace.spotDetails',
            ])
                ->whereHas('parkingSpace', fn($q) => $q->whereNotNull('user_id')->where('status', 'available')->whereNull('deleted_at')->where('is_verified', true))
                ->where('status', 'active')
                ->findOrFail($id);

            // Bookings for the same parking space
            $bookingCount = $this->getBookingCount($pricing, $startDate, $endDate, $startTime, $endTime);

            $pricing->booking_count = $bookingCount;
            $pricing->available_slots = max(0, $pricing->parkingSpace->total_slots - $bookingCount);
            // Calculate distance
            if ($userLat && $userLng && $pricing->parkingSpace->latitude && $pricing->parkingSpace->longitude) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $pricing->parkingSpace->latitude,
                    $pricing->parkingSpace->longitude
                );
                $pricing->distance = round($distance, 2);
            }
            // Estimate time and price
            if ($startTime && $endTime) {
                $estimate = $this->calculateEstimatedPricing($startDate, $startTime, $endDate, $endTime, $pricing->rate, 'monthly');
                $pricing->estimated_hours = $estimate['estimated_hours'];
                $pricing->estimated_price = $estimate['estimated_price'];
            }
            // Reviews
            $this->attachReviewStats($pricing);
            // return $pricing;
            $pricing->platform_fee = $this->platformFee() ?? [];
            // return $pricing;
            return $pricing;

        } catch (Exception $e) {
            Log::error("UserParkingSpaceService::getMonthlyPricingDetails - " . $e->getMessage());
            throw $e;
        }
    }

    public function transformPricingData($result, $startTime, $endTime, $startDate, $endDate, $pricingType = 'hourly')
    {
        // dd($startTime. $endTime. $startDate. $endDate);
        // Log::info($pricingType . 'pricing: ', [$startTime, $endTime, $startDate, $endDate]);
        return $result->getCollection()->map(function ($pricing) use ($startTime, $endTime, $startDate, $endDate, $pricingType) {
            // Bookings for the same parking space
            $bookingCount = $this->getBookingCount($pricing, $startDate, $endDate, $startTime, $endTime);
            $pricing->booking_count = $bookingCount;
            $pricing->available_slots = max(0, $pricing->parkingSpace->total_slots - $bookingCount);
            // estimated hours and price
            if ($startTime && $endTime) {
                $estimate = $this->calculateEstimatedPricing($startDate, $startTime, $endDate, $endTime, $pricing->rate, $pricingType);
                $pricing->estimated_hours = $estimate['estimated_hours'];
                $pricing->estimated_price = $estimate['estimated_price'];
            }
            // Reviews
            $this->attachReviewStats($pricing);
            return $pricing;
        });
    }
    /**
     * Calculates the distance between two points on the surface of the Earth.
     * The two points are given by their latitude and longitude coordinates.
     * The distance is returned in the given unit, which can be either 'K' (kilometers) or 'M' (miles).
     * @param float $lat1 The latitude of the first point in degrees.
     * @param float $lon1 The longitude of the first point in degrees.
     * @param float $lat2 The latitude of the second point in degrees.
     * @param float $lon2 The longitude of the second point in degrees.
     * @param string $unit The unit to use for the result, either 'K' (kilometers) or 'M' (miles).
     * @return float The distance between the two points in the given unit.
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = 'K')
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $unit === 'K' ? $miles * 1.609344 : $miles;
    }
    /**
     * Calculates the estimated pricing for a given time period.
     *
     * @param string $startDate The start date in the format 'Y-m-d'.
     * @param string $startTime The start time in the format 'H:i'.
     * @param string $endDate The end date in the format 'Y-m-d'.
     * @param string $endTime The end time in the format 'H:i'.
     * @param float $rate The pricing rate.
     * @param string $pricingType The pricing type, either 'hourly' or 'daily'. Defaults to 'hourly'.
     * @return array An array containing the estimated hours and estimated price.
     */
    public function calculateEstimatedPricing($startDate, $startTime, $endDate, $endTime, $rate, $pricingType = 'hourly')
    {
        if (!$startDate || !$startTime || !$endTime || !$rate) {
            return [
                'estimated_hours' => 0,
                'estimated_price' => 0,
            ];
        }

        $start = Carbon::parse("$startDate $startTime");
        $end = Carbon::parse("$endDate $endTime");

        if ($pricingType === 'hourly') {
            $totalHours = $start->floatDiffInHours($end);
        } elseif ($pricingType === 'daily') {
            $totalHours = ceil($start->floatDiffInDays($end)); // full days
        } elseif ($pricingType === 'monthly') {
            $totalHours = ceil($start->floatDiffInMonths($end)); // full months
        } else {
            $totalHours = $start->floatDiffInHours($end); // fallback to hourly
        }

        return [
            'estimated_hours' => round($totalHours, 2),
            'estimated_price' => round($totalHours * $rate, 2),
        ];
    }
    /**
     * Attach review stats to a pricing object.
     *
     * @param object $pricing
     * @return object
     */
    private function attachReviewStats($pricing)
    {
        $reviews = $pricing->parkingSpace->reviews;

        $pricing->review_count = $reviews->count();
        $pricing->average_rating = $reviews->isNotEmpty()
            ? round($reviews->avg('rating'), 2)
            : 0;

        return $pricing;
    }
    /**
     * Gets the total number of bookings for a given pricing object
     * during a given time period.
     *
     * @param object $pricing
     * @param string $startDate The start date in the format 'Y-m-d'.
     * @param string $endDate The end date in the format 'Y-m-d'.
     * @param string $startTime The start time in the format 'H:i'.
     * @param string $endTime The end time in the format 'H:i'.
     * @return int The total number of bookings.
     */
    private function getBookingCount($pricing, $startDate, $endDate, $startTime, $endTime)
    {
        return Booking::where('parking_space_id', $pricing->parking_space_id)
            ->whereNotIn('status', ['cancelled', 'close', 'completed'])
            ->whereDate('start_time', '>=', $startDate)
            ->whereDate('end_time', '<=', $endDate)
            ->whereTime('booking_time_start', '<=', $startTime)
            ->whereTime('booking_time_end', '>=', $endTime)
            ->get()
            ->sum(fn($b) => $b->number_of_slot ?? 1);
    }
    /**
     * Retrieve active platform fees from the platform settings.
     *
     * @return \Illuminate\Support\Collection A collection of platform settings with 'id', 'key', and 'value' fields.
     */
    private function platformFee()
    {
        $platformFee = PlatformSetting::where('status', 'active')->select('id', 'key', 'value')->get();
        return $platformFee;
    }



    public function indexForUsers($request)
    {
        try {
            $per_page = $request->per_page ?? 10;
            $radius = $request->radius ?? 500;

            $parkingSpaces = ParkingSpace::whereIn('status', ['available', 'unavailable', 'sold-out', 'close'])
                ->where('is_verified', true)
                ->select('id', 'slug', 'unique_id', 'user_id', 'title', 'description', 'address', 'latitude', 'longitude', 'gallery_images', 'status')
                ->with('hourlyPricing:id,parking_space_id,rate', 'dailyPricing:id,parking_space_id,rate', 'monthlyPricing:id,parking_space_id,rate')
                ->withCount(['bookings as total_bookings', 'reviews as total_reviews'])
                ->orderBy('total_reviews', 'desc')
                ->when($request->has('latitude') && $request->has('longitude'), function ($query) use ($request, $radius) {
                    $query->havingRaw("ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) < ?", [$request->longitude, $request->latitude, $radius]);
                })
                ->paginate($per_page);
            return $parkingSpaces;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}