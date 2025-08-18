<?php

namespace App\Http\Controllers\API\V1\Host;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\ParkingSpaceResource;
use App\Models\HourlyPricing;
use App\Models\ParkingSpace;
use App\Models\SpotDetail;
use App\Models\User;
use App\Services\API\V1\User\NotificationOrMail\NotificationOrMailService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HostParkingSpaceController extends Controller
{
    protected $user;
    protected $notificationOrMailService;

    /**
     * Set the authenticated user.
     *
     * This constructor method is called after all other service providers have
     * been registered, so we can rely on the Auth facade being available.
     */
    public function __construct(NotificationOrMailService $notificationOrMailService)
    {
        $this->user = auth()->user();
        $this->notificationOrMailService = $notificationOrMailService;
    }

    /**
     * Display a paginated list of parking spaces for the authenticated user.
     *
     * This method fetches all parking spaces created by the currently authenticated user
     * along with their associated driver instructions, hourly pricing with days, daily pricing,
     * monthly pricing, and spot details. Results are paginated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexForHost(Request $request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $parkingSpaces = ParkingSpace::where('user_id', $this->user->id)
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
                    },
                    'bookings as total_bookings'
                ])->paginate($per_page);
            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, ParkingSpaceResource::collection($parkingSpaces), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForHost" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces', 500);
        }
    }

    /**
     * Show a single parking space
     *
     * @param Request $request
     * @param string $ParkingSpaceSlug
     * @return JsonResponse
     */
    public function showForHost(Request $request, $ParkingSpaceSlug)
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
                    // 'bookings'
                ])
                ->withCount([
                    'reviews as total_reviews' => function ($query) {
                        $query->where('status', 'approved');
                    },
                    'bookings as total_bookings'
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

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'type_of_spot' => 'required|string|max:255',
            'max_vehicle_size' => 'required|string|max:255',
            'total_slots' => 'required|integer|min:1',
            'description' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'required|string',

            // Gallery Images
            'gallery_images' => 'required|array',
            'gallery_images.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',

            // // Instructions
            // 'instructions' => 'required|array|min:1',
            // 'instructions.*' => 'required|string',

            // Hourly Pricing
            'hourly_pricing' => 'nullable|array',
            'hourly_pricing.*.rate' => 'nullable|numeric|min:0',
            'hourly_pricing.*.start_time' => 'nullable|date_format:H:i',
            'hourly_pricing.*.end_time' => 'nullable|date_format:H:i|after:hourly_pricing.*.start_time',
            'hourly_pricing.*.days' => 'nullable|array|min:1',
            'hourly_pricing.*.days.*.day' => 'nullable|string',
            // 'hourly_pricing.*.days.*.status' => 'required|in:available,unavailable,sold-out,close',

            // // Daily Pricing
            // 'daily_pricing' => 'nullable|array',
            // 'daily_pricing.*.rate' => 'nullable|numeric|min:0',
            // 'daily_pricing.*.start_time' => 'nullable|date_format:H:i',
            // 'daily_pricing.*.end_time' => 'nullable|date_format:H:i|after:daily_pricing.*.start_time',
            // 'daily_pricing.*.start_date' => 'nullable|date',
            // 'daily_pricing.*.end_date' => 'nullable|date|after_or_equal:daily_pricing.*.start_date',

            // // Monthly Pricing
            // 'monthly_pricing' => 'nullable|array',
            // 'monthly_pricing.*.rate' => 'nullable|numeric|min:0',
            // 'monthly_pricing.*.start_time' => 'nullable|date_format:H:i',
            // 'monthly_pricing.*.end_time' => 'nullable|date_format:H:i|after:monthly_pricing.*.start_time',
            // 'monthly_pricing.*.start_date' => 'nullable|date',
            // 'monthly_pricing.*.end_date' => 'nullable|date|after_or_equal:monthly_pricing.*.start_date',

            // Spot Details
            'spot_details' => 'nullable|array',
            'spot_details.*.icon' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'spot_details.*.details' => 'nullable|string',
        ]);
        // Log::info($validatedData);
        // dd($validatedData);
        try {
            DB::beginTransaction();

            if (!isset($validatedData['hourly_pricing']) && !isset($validatedData['daily_pricing']) && !isset($validatedData['monthly_pricing'])) {
                return Helper::jsonErrorResponse('Please select at least one pricing option', 400);
            }
            // Handle Gallery Images
            $galleryImages = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $key => $image) {
                    $imagePath = Helper::fileUpload($image, 'gallery_space_images', $key . '_' . getFileName($image));
                    $galleryImages[] = $imagePath;
                }
                $validatedData['gallery_images'] = $galleryImages;
            }

            // Create base Parking Space
            $parkingSpaceData = $validatedData;
            $parkingSpaceData['unique_id'] = (string) Str::uuid();
            $parkingSpaceData['user_id'] = auth()->id();
            $parkingSpaceData['slug'] = Helper::makeSlug($validatedData['title'], 'parking_spaces');
            // here option for future use verify
            $parkingSpaceData['is_verified'] = false;
            $parkingSpaceData['status'] = 'available';


            $parkingSpace = ParkingSpace::create($parkingSpaceData);

            // if (isset($validatedData['instructions'])) {
            //     // Save Instructions
            //     foreach ($validatedData['instructions'] as $instruction) {
            //         $parkingSpace->driverInstructions()->create([
            //             'instructions' => $instruction,
            //             'status' => 'active',
            //         ]);
            //     }
            // }

            if (isset($validatedData['hourly_pricing'])) {
                // Save Hourly Pricing with Days
                foreach ($validatedData['hourly_pricing'] as $hourly) {
                    $days = $hourly['days'];
                    unset($hourly['days']);
                    $hourly['is_free_pricing'] = $hourly['rate'] == 0 ? true : false; // Check if the rate is 0 to set free pricing

                    $hourlyModel = $parkingSpace->hourlyPricing()->create($hourly);
                    foreach ($days as $day) {
                        $hourlyModel->days()->create($day);
                    }
                }
            }

            // if (isset($validatedData['daily_pricing'])) {
            //     // Save Daily Pricing
            //     foreach ($validatedData['daily_pricing'] as $daily) {
            //         $parkingSpace->dailyPricing()->create($daily);
            //     }
            // }

            // if (isset($validatedData['monthly_pricing'])) {
            //     // Save Monthly Pricing
            //     foreach ($validatedData['monthly_pricing'] as $monthly) {
            //         $parkingSpace->monthlyPricing()->create($monthly);
            //     }
            // }

            if (isset($validatedData['spot_details'])) {
                // Save Spot Details with icon image upload
                foreach ($validatedData['spot_details'] as $key => $detail) {
                    $imagePath = Helper::fileUpload($request->file("spot_details.$key.icon"), 'spot_details_images', $key . '_' . getFileName($request->file("spot_details.$key.icon")));
                    $parkingSpace->spotDetails()->create([
                        'icon' => $imagePath,
                        'details' => $detail['details'],
                        'status' => 'active',
                    ]);
                }
            }
            //send notification to admin
            $this->notificationOrMailService->sendNotification(true, User::where('role', 'admin')->first(), 'New Parking Space Created please check request', 'new_parking_space');
            DB::commit();
            $data = $parkingSpace->load([
                'driverInstructions',
                'hourlyPricing.days',
                // 'dailyPricing',
                // 'monthlyPricing',
                'spotDetails',
            ]);
            // Log::info("ParkingSpaceController::store => " . json_encode($data));
            return Helper::jsonResponse(true, 'Parking space created successfully', 200, ParkingSpaceResource::make($data));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ParkingSpaceController::store => " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to create parking space: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing parking space.
     *
     * This method validates and updates the details of a parking space identified by its slug.
     * It updates fields such as title, type of spot, vehicle size, and various pricing details.
     * It also handles the upload and update of gallery images, driver instructions, hourly, 
     * daily, and monthly pricing, and spot details including icons.
     *
     * @param Request $request
     * @param string $ParkingSpaceSlug
     * @return JsonResponse
     */

    public function update(Request $request, string $ParkingSpaceSlug): JsonResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'type_of_spot' => 'required|string|max:255',
            'max_vehicle_size' => 'required|string|max:255',
            'total_slots' => 'required|integer|min:1',
            'description' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'required|string',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',

            // 'instructions' => 'required|array|min:1',
            // 'instructions.*' => 'required|string',

            'hourly_pricing' => 'nullable|array',
            'hourly_pricing.*.rate' => 'nullable|numeric|min:0',
            'hourly_pricing.*.start_time' => 'nullable|date_format:H:i',
            'hourly_pricing.*.end_time' => 'nullable|date_format:H:i|after:hourly_pricing.*.start_time',
            'hourly_pricing.*.days' => 'nullable|array|min:1',
            'hourly_pricing.*.days.*.day' => 'nullable|string',
            'hourly_pricing.*.days.*.status' => 'nullable|in:available,unavailable,sold-out,close',

            // 'daily_pricing' => 'nullable|array',
            // 'daily_pricing.*.rate' => 'nullable|numeric|min:0',
            // 'daily_pricing.*.start_time' => 'nullable|date_format:H:i',
            // 'daily_pricing.*.end_time' => 'nullable|date_format:H:i|after:daily_pricing.*.start_time',
            // 'daily_pricing.*.start_date' => 'nullable|date',
            // 'daily_pricing.*.end_date' => 'nullable|date|after_or_equal:daily_pricing.*.start_date',

            // 'monthly_pricing' => 'nullable|array',
            // 'monthly_pricing.*.rate' => 'nullable|numeric|min:0',
            // 'monthly_pricing.*.start_time' => 'nullable|date_format:H:i',
            // 'monthly_pricing.*.end_time' => 'nullable|date_format:H:i|after:monthly_pricing.*.start_time',
            // 'monthly_pricing.*.start_date' => 'nullable|date',
            // 'monthly_pricing.*.end_date' => 'nullable|date|after_or_equal:monthly_pricing.*.start_date',

            'spot_details' => 'nullable|array',
            'spot_details.*.icon' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'spot_details.*.details' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $parkingSpace = ParkingSpace::where('slug', $ParkingSpaceSlug)->where('user_id', $this->user->id)->firstOrFail();

            // Handle gallery images
            $galleryImages = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $key => $image) {
                    $imagePath = Helper::fileUpload($image, 'gallery_space_images', $key . '_' . getFileName($image));
                    $galleryImages[] = $imagePath;
                }
            } else {
                $galleryImages = $parkingSpace->gallery_images ?? [];
            }
            $validatedData['gallery_images'] = $galleryImages;

            // Update basic fields
            $parkingSpace->update($validatedData);

            // if (isset($validatedData['instructions'])) {
            //     // Delete and recreate instructions
            //     $parkingSpace->driverInstructions()->delete();
            //     foreach ($validatedData['instructions'] as $instruction) {
            //         $parkingSpace->driverInstructions()->create([
            //             'instructions' => $instruction,
            //             'status' => 'active',
            //         ]);
            //     }
            // }

            if (isset($validatedData['hourly_pricing'])) {
                // Delete and recreate hourly pricing and days
                $parkingSpace->hourlyPricing()->each(function ($hourly) {
                    $hourly->days()->delete();
                    $hourly->delete();
                });
                foreach ($validatedData['hourly_pricing'] as $hourly) {
                    $days = $hourly['days'];
                    unset($hourly['days']);
                    $hourly['is_free_pricing'] = $hourly['rate'] == 0 ? true : false; // Check if the rate is 0 to set free pricing
                    $newHourly = $parkingSpace->hourlyPricing()->create($hourly);
                    foreach ($days as $day) {
                        $newHourly->days()->create($day);
                    }
                }
            }

            // if (isset($validatedData['daily_pricing'])) {
            //     // Delete and recreate daily pricing
            //     $parkingSpace->dailyPricing()->delete();
            //     foreach ($validatedData['daily_pricing'] as $pricing) {
            //         $pricing['is_free_pricing'] = $pricing['rate'] == 0 ? true : false; // Check if the rate is 0 to set free pricing
            //         $parkingSpace->dailyPricing()->create($pricing);
            //     }

            //     // Delete and recreate monthly pricing
            //     $parkingSpace->monthlyPricing()->delete();
            //     foreach ($validatedData['monthly_pricing'] as $pricing) {
            //         $pricing['is_free_pricing'] = $pricing['rate'] == 0 ? true : false; // Check if the rate is 0 to set free pricing
            //         $parkingSpace->monthlyPricing()->create($pricing);
            //     }
            // }

            if (array_key_exists('spot_details', $validatedData)) {
                // Delete and recreate spot details
                // $parkingSpace->spotDetails()->delete();
                foreach ($validatedData['spot_details'] as $key => $spotDetail) {
                    $imagePath = null;
                    if (isset($spotDetail['icon']) && $spotDetail['icon']) {
                        $image = $spotDetail['icon'];
                        unset($spotDetail['icon']);
                        $imagePath = Helper::fileUpload($image, 'spot_details_images', $key . '_' . getFileName($image));
                    }
                    if ($imagePath) {
                        $spotDetail['icon'] = $imagePath;
                    }
                    $parkingSpace->spotDetails()->create($spotDetail);
                }

            }

            DB::commit();

            return Helper::jsonResponse(true, 'Parking space updated successfully', 200, ParkingSpaceResource::make($parkingSpace->load([
                'driverInstructions',
                'hourlyPricing.days',
                // 'dailyPricing',
                // 'monthlyPricing',
                'spotDetails'
            ])));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ParkingSpaceController::update => " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update parking space: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $ParkingSpaceSlug
     * @return JsonResponse
     */

    public function destroy(string $ParkingSpaceSlug): JsonResponse
    {
        try {
            DB::beginTransaction();
            $parkingSpace = ParkingSpace::where('slug', $ParkingSpaceSlug)->where('user_id', $this->user->id)->first();
            if (!$parkingSpace) {
                return Helper::jsonErrorResponse('Parking space not found', 404);
            }

            // check if the parking space is booked actively
            if ($parkingSpace->bookings()->count() > 0) {
                return Helper::jsonErrorResponse('You cannot delete this parking space because it is currently being used.', 400);
            }
            // Delete associated driver instructions
            $parkingSpace->driverInstructions()->delete();
            // Delete associated hourly pricing
            $parkingSpace->hourlyPricing()->each(function ($hourlyPricing) {
                $hourlyPricing->days()->delete();
                $hourlyPricing->delete();
            });
            // Delete associated daily pricing
            $parkingSpace->dailyPricing()->delete();
            // Delete associated monthly pricing
            $parkingSpace->monthlyPricing()->delete();
            // Delete associated spot details' icons
            if ($parkingSpace->spotDetails) {
                foreach ($parkingSpace->spotDetails as $spotDetail) {
                    // Log::info($spotDetail->icon);
                    if ($spotDetail->icon) {
                        // Convert URL to file path
                        $filePath = str_replace(url('/'), public_path(), $spotDetail->icon);

                        // Check if the file exists and delete it
                        if (file_exists($filePath)) {
                            Helper::fileDelete($filePath);
                        }
                    }
                }
            }
            // Delete associated spot details
            $parkingSpace->spotDetails()->delete();
            // Delete the gallery images
            if ($parkingSpace->gallery_images) {
                foreach ($parkingSpace->gallery_images as $image) {
                    $filePath = str_replace(url('/'), public_path(), $image);
                    if (file_exists($filePath)) {
                        Helper::fileDelete($filePath);
                    }
                }
            }
            // Delete the parking space
            $parkingSpace->delete();

            DB::commit();

            return Helper::jsonResponse(true, 'Parking space and associated data deleted successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ParkingSpaceController::destroy => " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to delete parking space' . $e->getMessage(), 403);
        }
    }


    public function updateSpotDetail(Request $requst, string $spotDetailId, )
    {
        $validateData = request()->validate([
            'icon' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'details' => 'required|string',
        ]);
        try {
            $spotDetail = SpotDetail::find($spotDetailId);
            if (!$spotDetail) {
                return Helper::jsonErrorResponse('Spot detail not found', 404);
            }
            if ($requst->hasFile('icon')) {
                $validateData['icon'] = Helper::fileUpload($validateData['icon'], 'spot_details_images', $spotDetailId . '_' . getFileName($validateData['icon']));
            } else {
                $validateData['icon'] = $spotDetail->icon;
            }

            $spotDetail->update($validateData);

            return Helper::jsonResponse(true, 'Spot detail updated successfully', 200);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::updateSpotDetail => " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update spot detail' . $e->getMessage(), 403);
        }

    }
}
