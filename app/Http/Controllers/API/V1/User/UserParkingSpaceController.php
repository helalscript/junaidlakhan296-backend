<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\IndexForUserDailyResource;
use App\Http\Resources\API\V1\IndexForUserHourlyResource;
use App\Http\Resources\API\V1\ShowForUserDailyResource;
use App\Http\Resources\API\V1\ShowForUserHourlyResource;
use App\Models\Booking;
use App\Models\HourlyPricing;
use App\Services\API\V1\User\ParkingSpace\UserParkingSpaceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserParkingSpaceController extends Controller
{
    protected $user;
    protected $userParkingSpaceService;

    /**
     * Set the authenticated user.
     *
     * This constructor method is called after all other service providers have
     * been registered, so we can rely on the Auth facade being available.
     */
    public function __construct(UserParkingSpaceService $userParkingSpaceService)
    {
        $this->user = auth()->user();
        $this->userParkingSpaceService = $userParkingSpaceService;
    }
    public function indexForUsersHourly(Request $request)
    {
        $validatedData = $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:10',
        ]);
        // dd($validatedData);
        try {
            $isStartTime = $validatedData['start_time'] ?? null;
            $result = $this->userParkingSpaceService->getHourlyPricing($request);

            $transformedData = $this->userParkingSpaceService->transformPricingData(
                $result,
                $validatedData['start_time'] ?? now()->format('H:i'),
                $isStartTime ? ($validatedData['end_time'] ?? (new Carbon($validatedData['start_time'] ?? now()->format('H:i')))->addHour()->format('H:i')) : (new Carbon($validatedData['start_time'] ?? now()->format('H:i')))->addHour()->format('H:i'),
                $validatedData['start_date'] ?? now()->format('Y-m-d'),
                $validatedData['end_date'] ?? null
            );
            $result->setCollection($transformedData);

            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, IndexForUserHourlyResource::collection($result), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsersHourly - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
        }
    }
    public function showForUsersHourly($id, Request $request)
    {
        $validatedData = $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:10',
        ]);
        // dd($validatedData);
        try {
            $pricing = $this->userParkingSpaceService->getHourlyPricingDetails($id, $request);
            return Helper::jsonResponse(true, 'Parking space details fetched successfully', 200, new ShowForUserHourlyResource($pricing));
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::showForUsersHourly - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch pricing details.', 500);
        }
    }

    // public function indexForUsersHourly(Request $request)
    // {
    //     try {
    //         $perPage = $request->per_page ?? 25;
    //         // $startTime = $request->start_time;
    //         // $endTime = $request->end_time;
    //         $startTime = $request->start_time ?? now()->format('H:i');
    //         $endTime = $request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i');
    //         $startDate = $request->start_date ?? now()->format('Y-m-d');
    //         $endDate = $request->end_date;
    //         // dd($startTime.' '. $endTime.' '.$startDate);
    //         $latitude = $request->latitude ?? 12.9716770;
    //         $longitude = $request->longitude ?? 77.5946770;
    //         $radius = $request->radius ?? 500;
    //         // dd($startDate);

    //         // Get list of day names (e.g., Monday, Tuesday) from given dates
    //         $dayNames = [];

    //         if ($startDate && !$endDate) {
    //             $dayNames[] = \Carbon\Carbon::parse($startDate)->format('l');
    //         } elseif ($startDate && $endDate) {
    //             $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
    //             foreach ($period as $date) {
    //                 $day = $date->format('l');
    //                 if (!in_array($day, $dayNames)) {
    //                     $dayNames[] = $day;
    //                 }
    //             }
    //         }
    //         // dd($dayNames);
    //         $haversine = "(6371 * acos(cos(radians(?)) 
    //                     * cos(radians(parking_spaces.latitude)) 
    //                     * cos(radians(parking_spaces.longitude) - radians(?)) 
    //                     + sin(radians(?)) 
    //                     * sin(radians(parking_spaces.latitude))))";

    //         $hourlyPricing = HourlyPricing::with([
    //             'parkingSpace.driverInstructions',
    //             'parkingSpace.reviews' => function ($query) {
    //                 $query->where('status', 'approved');
    //             },
    //             'parkingSpace.spotDetails',
    //             'days'
    //         ])
    //             ->join('parking_spaces', 'hourly_pricings.parking_space_id', '=', 'parking_spaces.id')
    //             ->where('hourly_pricings.status', 'active')
    //             ->where('parking_spaces.status', 'available')
    //             ->where('parking_spaces.is_verified', true)
    //             ->whereNull('parking_spaces.deleted_at')
    //             ->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $radius])
    //             ->select('hourly_pricings.*')
    //             ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
    //             ->whereHas('days', function ($q) use ($dayNames) {
    //                 if (!empty($dayNames)) {
    //                     $q->whereIn('day', $dayNames);
    //                 }
    //                 $q->where('status', 'available');
    //             })
    //             ->when($startTime, function ($query, $startTime) {
    //                 $query->whereTime('start_time', '<=', $startTime);
    //             })
    //             ->when($endTime, function ($query, $endTime) {
    //                 $query->whereTime('end_time', '>=', $endTime);
    //             })
    //             ->orderBy('distance', 'asc');


    //         $result = $hourlyPricing->paginate($perPage);

    //         $transformedData = $result->getCollection()->map(function ($hourlyPricing) use ($request, $startTime, $endTime, $startDate, $endDate) {
    //             Log::info('startTime ' . $startTime . ' endTime ' . $endTime . ' startDate ' . $startDate . ' endDate ' . $endDate);
    //             // Filter active bookings for this parking space during specified time
    //             $bookingsQuery = Booking::where('parking_space_id', $hourlyPricing->parking_space_id)
    //                 ->whereNotIn('status', ['cancelled', 'close', 'completed']);

    //             // Optional: Filter by date/time range if provided
    //             if ($startDate) {
    //                 $bookingsQuery->whereDate('start_time', '>=', $startDate);
    //             }
    //             if ($endDate) {
    //                 $bookingsQuery->whereDate('end_time', '<=', $endDate);
    //             }
    //             if ($startTime) {
    //                 $bookingsQuery->whereTime('booking_time_start', '<=', $startTime);
    //             }
    //             if ($endTime) {
    //                 $bookingsQuery->whereTime('booking_time_end', '>=', $endTime);
    //             }

    //             // Sum up number_of_slot (default to 1 if null)
    //             $bookingCount = $bookingsQuery->get()->sum(function ($booking) {
    //                 return $booking->number_of_slot ?? 1;
    //             });

    //             $hourlyPricing->booking_count = $bookingCount;
    //             $hourlyPricing->available_slots = max(0, $hourlyPricing->parkingSpace->total_slots - $bookingCount);
    //             // Calculate user search duration (in hours)
    //             if ($startTime && $endTime && $startDate) {
    //                 // dd($startTime.' '. $endTime.' '.$startDate);
    //                 $start = Carbon::parse($startTime);
    //                 $end = Carbon::parse($endTime);
    //                 Log::info('start time: ' . $start . " " . 'end time: ' . $end);
    //                 // Get float difference between times (same day)
    //                 $dailyHours = round($start->floatDiffInHours($end), 2);
    //                 Log::info('Hours Count: ' . $dailyHours);

    //                 // Count how many days
    //                 if ($endDate) {
    //                     $start_time_date = Carbon::parse("{$startDate} {$startTime}");
    //                     $end_time_date = Carbon::parse("{$endDate} {$endTime}");
    //                     Log::info('Start date ' . $start_time_date . " " . 'End date' . $end_time_date);
    //                     $totalHoursCount = round($start_time_date->floatDiffInHours($end_time_date), 2);
    //                     Log::info(' Total Days+Hours Count count: ' . $totalHoursCount);
    //                 } else {
    //                     $totalHoursCount = $dailyHours;
    //                 }

    //                 $totalHours = round($totalHoursCount, 2);
    //                 Log::info('Total hours: ' . $totalHours);
    //                 $hourlyPricing->estimated_hours = $totalHours;
    //                 $hourlyPricing->estimated_price = $totalHours * $hourlyPricing->rate;
    //             } else {
    //                 $hourlyPricing->estimated_hours = null;
    //                 $hourlyPricing->estimated_price = null;
    //             }

    //             // Calculate review count and average rating
    //             $reviews = $hourlyPricing->parkingSpace->reviews;
    //             $reviewCount = $reviews->count();
    //             $reviewAverage = $reviewCount > 0 ? round($reviews->avg('rating'), 2) : 0;

    //             $hourlyPricing->review_count = $reviewCount;
    //             $hourlyPricing->average_rating = $reviewAverage;

    //             return $hourlyPricing;
    //         });

    //         $result->setCollection($transformedData);

    //         // return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, $result, true);
    //         return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, IndexForUserHourlyResource::collection($result), true);
    //     } catch (Exception $e) {
    //         Log::error("ParkingSpaceController::indexForUsersHourly - " . $e->getMessage());
    //         return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
    //     }
    // }

    // public function showForUsersHourly(Request $request, $id)
    // {
    //     try {
    //         $latitude = $request->latitude ?? 12.9716770;
    //         $longitude = $request->longitude ?? 77.5946770;

    //         $startTime = $request->start_time ?? now()->format('H:i');
    //         $endTime = $request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i');
    //         $startDate = $request->start_date ?? now()->format('Y-m-d');
    //         $endDate = $request->end_date;

    //         $hourlyPricing = HourlyPricing::with([
    //             'parkingSpace.driverInstructions',
    //             'parkingSpace.reviews' => function ($query) {
    //                 $query->where('status', 'approved');
    //             },
    //             'parkingSpace.spotDetails',
    //             'days'
    //         ])
    //             ->where('status', 'active')
    //             ->findOrFail($id);

    //         $parkingSpace = $hourlyPricing->parkingSpace;

    //         if (
    //             !$parkingSpace ||
    //             $parkingSpace->status !== 'available' ||
    //             !$parkingSpace->is_verified ||
    //             $parkingSpace->deleted_at
    //         ) {
    //             return Helper::jsonErrorResponse('Parking space not available.', 404);
    //         }

    //         // Distance Calculation (Haversine Formula)
    //         $distance = 6371 * acos(
    //             cos(deg2rad($latitude)) *
    //             cos(deg2rad($parkingSpace->latitude)) *
    //             cos(deg2rad($parkingSpace->longitude) - deg2rad($longitude)) +
    //             sin(deg2rad($latitude)) *
    //             sin(deg2rad($parkingSpace->latitude))
    //         );

    //         $hourlyPricing->distance = round($distance, 2);

    //         // Booking Calculation
    //         $bookingsQuery = Booking::where('parking_space_id', $parkingSpace->id)
    //             ->whereNotIn('status', ['cancelled', 'close']);

    //         if ($startDate) {
    //             $bookingsQuery->whereDate('start_time', '>=', $startDate);
    //         }
    //         if ($endDate) {
    //             $bookingsQuery->whereDate('end_time', '<=', $endDate);
    //         }
    //         if ($startTime) {
    //             $bookingsQuery->whereTime('booking_time_start', '<=', $startTime);
    //         }
    //         if ($endTime) {
    //             $bookingsQuery->whereTime('booking_time_end', '>=', $endTime);
    //         }

    //         $bookingCount = $bookingsQuery->get()->sum(function ($booking) {
    //             return $booking->number_of_slot ?? 1;
    //         });

    //         $hourlyPricing->booking_count = $bookingCount;
    //         $hourlyPricing->available_slots = max(0, $parkingSpace->total_slots - $bookingCount);
    //         // Calculate review count and average rating
    //         $reviews = $hourlyPricing->parkingSpace->reviews;
    //         $reviewCount = $reviews->count();
    //         $reviewAverage = $reviewCount > 0 ? round($reviews->avg('rating'), 2) : 0;

    //         $hourlyPricing->review_count = $reviewCount;
    //         $hourlyPricing->average_rating = $reviewAverage;
    //         // Estimate Time and Price
    //         if ($startTime && $endTime && $startDate) {
    //             $start = Carbon::parse($startTime);
    //             $end = Carbon::parse($endTime);
    //             $dailyHours = round($start->floatDiffInHours($end), 2);

    //             if ($endDate) {
    //                 $start_time_date = Carbon::parse("{$startDate} {$startTime}");
    //                 $end_time_date = Carbon::parse("{$endDate} {$endTime}");
    //                 $totalHours = round($start_time_date->floatDiffInHours($end_time_date), 2);
    //             } else {
    //                 $totalHours = $dailyHours;
    //             }

    //             $hourlyPricing->estimated_hours = $totalHours;
    //             $hourlyPricing->estimated_price = $totalHours * $hourlyPricing->rate;
    //         } else {
    //             $hourlyPricing->estimated_hours = null;
    //             $hourlyPricing->estimated_price = null;
    //         }


    //         // return Helper::jsonResponse(true, 'Hourly pricing fetched successfully', 200, $hourlyPricing);
    //         return Helper::jsonResponse(true, 'Hourly pricing fetched successfully', 200, new ShowForUserHourlyResource($hourlyPricing));
    //     } catch (Exception $e) {
    //         Log::error("ParkingSpaceController::showForUsersHourly - " . $e->getMessage());
    //         return Helper::jsonErrorResponse('Failed to fetch hourly pricing. ' . $e->getMessage(), 500);
    //     }
    // }


    public function indexForUsersDaily(Request $request)
    {
        $validatedData = $request->validate([
            'start_time' => 'required|date_format:Y-m-d\TH:i|after_or_equal:now',
            'end_time' => 'nullable|date_format:Y-m-d\TH:i|after:start_time',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:10',
        ]);

        try {
            $startTime = $request->start_time ? (new Carbon($request->start_time))->format('H:i') : now()->format('H:i');
            $startDate = $request->start_time ? (new Carbon($request->start_time))->format('Y-m-d') : now()->format('Y-m-d');
            $endTime = $request->end_time ? (new Carbon($request->end_time))->format('H:i') : (new Carbon($startTime))->addHour()->format('H:i');

            $endDate = $request->end_time ? (new Carbon($request->end_time))->format('Y-m-d') : $startDate;
            $result = $this->userParkingSpaceService->getDailyPricing($request);

            // transform the data
            $transformedData = $this->userParkingSpaceService->transformPricingData(
                $result,
                $startTime,
                $endTime,
                $startDate,
                $endDate,
                'daily'
            );
            $result->setCollection($transformedData);

            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, IndexForUserDailyResource::collection($result), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsersDaily - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
        }
    }

    public function showForUsersDaily($id, Request $request)
    {
        $validatedData = $request->validate([
            'start_time' => 'required|date_format:Y-m-d\TH:i|after_or_equal:now',
            'end_time' => 'nullable|date_format:Y-m-d\TH:i|after:start_time',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:10',
        ]);
// dd($validatedData);
        try {
            $pricing = $this->userParkingSpaceService->getDailyPricingDetails($id, $request);
            return Helper::jsonResponse(true, 'Daily parking space details fetched successfully', 200, new ShowForUserDailyResource($pricing));
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::showForUsersDaily - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch pricing details.', 500);
        }
    }

}
