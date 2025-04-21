<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\ParkingSpaceResource;
use App\Models\Booking;
use App\Models\HourlyPricing;
use App\Models\ParkingSpace;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserParkingSpaceController extends Controller
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

    public function indexForUsers(Request $request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $parkingSpaces_pricing_type = $request->parking_spaces_pricing_type ?? 'hourly_pricing';
            $start_date = $request->start_date ?? null;
            $end_date = $request->end_date ?? null;
            $start_time = $request->start_time ?? null;
            $end_time = $request->end_time ?? null;

            $parkingSpaces = ParkingSpace::where('status', 'available')
                ->withAvg([
                    'reviews as average_rating' => function ($query) {
                        $query->where('status', 'approved');
                    }
                ], 'rating')
                ->with([
                    'driverInstructions' => function ($query) {
                        $query->select('id', 'parking_space_id', 'instructions');
                    },
                    'hourlyPricing.days',
                    'dailyPricing' => function ($query) {
                        $query->select('id', 'parking_space_id', 'rate', 'start_time', 'end_time', 'start_date', 'end_date');
                    },
                    'monthlyPricing' => function ($query) {
                        $query->select('id', 'parking_space_id', 'rate', 'start_time', 'end_time', 'start_date', 'end_date');
                    },
                    'spotDetails' => function ($query) {
                        $query->select('id', 'parking_space_id', 'icon', 'details');
                    },
                    'reviews' => function ($query) {
                        $query->with([
                            'user' => function ($query) {
                                $query->select('id', 'name', 'avatar');
                            }
                        ])->where('status', 'approved')->select('id', 'user_id', 'parking_space_id', 'rating', 'comment');
                    },
                ])
                ->withCount([
                    'reviews as total_reviews' => function ($query) {
                        $query->where('status', 'approved');
                    }
                ])
                ->paginate($per_page);
            // dd($parkingSpaces->toArray());
            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, ParkingSpaceResource::collection($parkingSpaces), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsers" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces', 500);
        }
    }

    // public function indexForUsers(Request $request)
    // {
    //     try {
    //         $per_page = $request->per_page ?? 25;
    //         $pricingType = $request->parking_spaces_pricing_type ?? 'hourly_pricing';

    //         $start_date = $request->start_date;
    //         $end_date = $request->end_date;
    //         $start_time = $request->start_time;
    //         $end_time = $request->end_time;

    //         $parkingSpaces = ParkingSpace::where('status', 'available');

    //         // Apply filters based on selected pricing type
    //         if ($pricingType === 'hourly_pricing') {
    //             $parkingSpaces->whereHas('hourlyPricing', function ($query) use ($start_date, $end_date, $start_time, $end_time) {
    //                 $query->where(function ($query) use ($start_date, $end_date, $start_time, $end_time) {
    //                     if ($start_date && $end_date) {
    //                         $query->whereDate('start_date', '<=', $start_date)
    //                             ->whereDate('end_date', '>=', $end_date);
    //                     }
    //                     if ($start_time && $end_time) {
    //                         $query->whereTime('start_time', '<=', $start_time)
    //                             ->whereTime('end_time', '>=', $end_time);
    //                     }
    //                 });
    //             });
    //         } elseif ($pricingType === 'daily_pricing') {
    //             $parkingSpaces->whereHas('dailyPricing', function ($query) use ($start_date, $end_date) {
    //                 $query->where(function ($query) use ($start_date, $end_date) {
    //                     if ($start_date && $end_date) {
    //                         $query->whereDate('start_date', '<=', $start_date)
    //                             ->whereDate('end_date', '>=', $end_date);
    //                     }
    //                 });
    //             });
    //         } elseif ($pricingType === 'monthly_pricing') {
    //             $parkingSpaces->whereHas('monthlyPricing', function ($query) use ($start_date, $end_date) {
    //                 $query->where(function ($query) use ($start_date, $end_date) {
    //                     if ($start_date && $end_date) {
    //                         $query->whereDate('start_date', '<=', $start_date)
    //                             ->whereDate('end_date', '>=', $end_date);
    //                     }
    //                 });
    //             });
    //         }

    //         // Common relationships to always load
    //         $with = [
    //             'driverInstructions:id,parking_space_id,instructions',
    //             'spotDetails:id,parking_space_id,icon,details',
    //             'reviews' => function ($query) {
    //                 $query->with('user:id,name,avatar')
    //                     ->where('status', 'approved')
    //                     ->select('id', 'user_id', 'parking_space_id', 'rating', 'comment');
    //             },
    //         ];

    //         // Add only selected pricing relation
    //         if ($pricingType === 'hourly_pricing') {
    //             $with[] = 'hourlyPricing.days';
    //         } elseif ($pricingType === 'daily_pricing') {
    //             $with[] = 'dailyPricing:id,parking_space_id,rate,start_time,end_time,start_date,end_date';
    //         } elseif ($pricingType === 'monthly_pricing') {
    //             $with[] = 'monthlyPricing:id,parking_space_id,rate,start_time,end_time,start_date,end_date';
    //         }

    //         $parkingSpaces = $parkingSpaces
    //             ->withAvg([
    //                 'reviews as average_rating' => function ($query) {
    //                     $query->where('status', 'approved');
    //                 }
    //             ], 'rating')
    //             ->withCount([
    //                 'reviews as total_reviews' => function ($query) {
    //                     $query->where('status', 'approved');
    //                 }
    //             ])
    //             ->with($with)
    //             ->paginate($per_page);

    //         return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, $parkingSpaces, true);

    //     } catch (Exception $e) {
    //         Log::error("ParkingSpaceController::indexForUsers " . $e->getMessage());
    //         return Helper::jsonErrorResponse('Failed to fetch parking spaces', 500);
    //     }
    // }


    public function showForUsers(Request $request, $ParkingSpaceSlug)
    {
        try {
            $parkingSpace = ParkingSpace::where('slug', $ParkingSpaceSlug)
                ->withAvg([
                    'reviews as average_rating' => function ($query) {
                        $query->where('status', 'approved');
                    }
                ], 'rating')
                ->with([
                    'driverInstructions' => function ($query) {
                        $query->select('id', 'parking_space_id', 'instructions');
                    },
                    'hourlyPricing.days',
                    'dailyPricing' => function ($query) {
                        $query->select('id', 'parking_space_id', 'rate', 'start_time', 'end_time', 'start_date', 'end_date');
                    },
                    'monthlyPricing' => function ($query) {
                        $query->select('id', 'parking_space_id', 'rate', 'start_time', 'end_time', 'start_date', 'end_date');
                    },
                    'spotDetails' => function ($query) {
                        $query->select('id', 'parking_space_id', 'icon', 'details');
                    },
                    'reviews' => function ($query) {
                        $query->with([
                            'user' => function ($query) {
                                $query->select('id', 'name', 'avatar');
                            }
                        ])->where('status', 'approved')->select('id', 'user_id', 'parking_space_id', 'rating', 'comment');
                    },
                ])
                ->withCount([
                    'reviews as total_reviews' => function ($query) {
                        $query->where('status', 'approved');
                    }
                ])
                ->firstOrFail();
            return Helper::jsonResponse(true, 'Parking space fetched successfully', 200, ParkingSpaceResource::make($parkingSpace));
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking space', 500);
        }
    }
    /**
     * Store a newly created parking space in the database.
     *
     * Validates the incoming request data and creates a new parking space along with
     * associated driver instructions, pricing details, and spot details. Handles image
     * uploads for gallery images and spot icons. If any step fails, the transaction is
     * rolled back, and an error response is returned.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */




    public function indexForUsersHourly(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 25;
            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Get list of day names (e.g., Monday, Tuesday) from given dates
            $dayNames = [];

            if ($startDate && !$endDate) {
                $dayNames[] = \Carbon\Carbon::parse($startDate)->format('l');
            } elseif ($startDate && $endDate) {
                $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
                foreach ($period as $date) {
                    $day = $date->format('l');
                    if (!in_array($day, $dayNames)) {
                        $dayNames[] = $day;
                    }
                }
            }
            // dd($dayNames);
            $hourlyPricing = HourlyPricing::with(['parkingSpace', 'days'])
                ->where('status', 'active')
                ->whereHas('parkingSpace', function ($q) {
                    $q->where('status', 'available');
                })
                ->whereHas('days', function ($q) use ($dayNames) {
                    if (!empty($dayNames)) {
                        $q->whereIn('day', $dayNames);
                    }
                    $q->where('status', 'available'); // Filter days by status
                })
                ->when($startTime, function ($query, $startTime) {
                    $query->whereTime('start_time', '>=', $startTime);
                })
                ->when($endTime, function ($query, $endTime) {
                    $query->whereTime('end_time', '<=', $endTime);
                });
                

                $result = $hourlyPricing->paginate($perPage);

                $transformedData = $result->getCollection()->map(function ($hourlyPricing) {
                    $hourlyPricing->booking_count = Booking::where('parking_space_id', $hourlyPricing->parking_space_id)->count();
                    return $hourlyPricing;
                });
                
                $result->setCollection($transformedData);
                dd($result->toArray());

            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, $result, true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsersHourly - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
        }
    }

}
