<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParkingSpaceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $parkingSpaces = ParkingSpace::where('status', 'active')->paginate($per_page);
            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, $parkingSpaces, true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces', 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $parkingSpace = ParkingSpace::find($id);
            return Helper::jsonResponse(true, 'Parking space fetched successfully', 200, $parkingSpace, true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking space', 500);
        }
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'type_of_spot' => 'required|string|max:255',
            'max_vehicle_size' => 'required|string|max:255',
            'total_slots' => 'required|integer|min:1',
            'description' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            // 'gallery_images.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'gallery_images.*' => 'required|regex:/^data:image\/(jpeg|png|jpg);base64,/',
            'status' => 'required|in:available,unavailable,sold-out,close',

            // Instructions
            'instructions' => 'required|array|min:1',
            'instructions.*' => 'required|string',

            // Hourly Pricing
            'hourly_pricing.rate' => 'nullable|numeric|min:0',
            'hourly_pricing.start_time' => 'nullable|date_format:H:i',
            'hourly_pricing.end_time' => 'nullable|date_format:H:i|after:hourly_pricing.start_time',
            'hourly_pricing.status' => 'nullable|in:active,inactive',
            'hourly_pricing.days' => 'nullable|array|min:1',
            'hourly_pricing.days.*.day' => 'required|string',
            'hourly_pricing.days.*.status' => 'required|in:available,unavailable,sold-out,close',

            // Daily Pricing
            'daily_pricing.rate' => 'nullable|numeric|min:0',
            'daily_pricing.start_time' => 'nullable|date_format:H:i',
            'daily_pricing.end_time' => 'nullable|date_format:H:i|after:daily_pricing.start_time',
            'daily_pricing.start_date' => 'nullable|date',
            'daily_pricing.end_date' => 'nullable|date|after_or_equal:daily_pricing.start_date',
            'daily_pricing.status' => 'nullable|in:active,inactive',

            // Monthly Pricing
            'monthly_pricing.rate' => 'nullable|numeric|min:0',
            'monthly_pricing.start_time' => 'nullable|date_format:H:i',
            'monthly_pricing.end_time' => 'nullable|date_format:H:i|after:monthly_pricing.start_time',
            'monthly_pricing.start_date' => 'nullable|date',
            'monthly_pricing.end_date' => 'nullable|date|after_or_equal:monthly_pricing.start_date',
            'monthly_pricing.status' => 'nullable|in:active,inactive',

            // Spot Details
            'spot_details' => 'nullable|array',
            'spot_details.*.icon' => 'required|string|max:255',
            'spot_details.*.details' => 'required|string',
            'spot_details.*.status' => 'required|in:active,inactive',
        ]);
dd($validatedData);
        try {
            // Begin database transaction
            DB::beginTransaction();
            // Handle Gallery Images Upload
            $galleryImages = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $key => $image) {
                    $imagePath = Helper::fileUpload($image, 'services_gallery_images', $key . '_' . getFileName($image));
                    $galleryImages[] = $imagePath;
                }
                $validatedData['gallery_images'] = json_encode($galleryImages);
            } else {
                $validatedData['gallery_images'] = null;
            }

            // Prepare parking space data
            $data = $validatedData;
            $data['unique_id'] = (string) Str::uuid();
            $data['user_id'] = auth()->check() ? auth()->id() : null;

            // Create Parking Space
            $parkingSpace = ParkingSpace::create($data);

            // Create Driver Instructions
            foreach ($validatedData['instructions'] as $instruction) {
                $parkingSpace->driverInstructions()->create([
                    'instructions' => $instruction,
                    'status' => 'active',
                ]);
            }

            // Hourly Pricing (with optional days)
            if ($request->filled('hourly_pricing')) {
                $hourlyData = $validatedData['hourly_pricing'];
                $hourlyDays = $hourlyData['days'] ?? [];
                unset($hourlyData['days']);

                $hourlyPricing = $parkingSpace->hourlyPricing()->create($hourlyData);

                foreach ($hourlyDays as $day) {
                    $hourlyPricing->days()->create($day);
                }
            }

            // Daily Pricing
            if ($request->filled('daily_pricing')) {
                $parkingSpace->dailyPricing()->create($validatedData['daily_pricing']);
            }

            // Monthly Pricing
            if ($request->filled('monthly_pricing')) {
                $parkingSpace->monthlyPricing()->create($validatedData['monthly_pricing']);
            }

            // Spot Details
            if ($request->filled('spot_details')) {
                foreach ($validatedData['spot_details'] as $spotDetail) {
                    $parkingSpace->spotDetails()->create($spotDetail);
                }
            }

            // Commit transaction if everything is successful
            DB::commit();

            // Return success response with created parking space data
            return Helper::jsonResponse(true, 'Parking space created successfully', 200, $parkingSpace->load([
                'driverInstructions',
                'hourlyPricing.days',
                'dailyPricing',
                'monthlyPricing',
                'spotDetails'
            ]), true);

        } catch (Exception $e) {
            // Rollback transaction if error occurs
            DB::rollBack();
            Log::error("ParkingSpaceController::store => " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to create parking space', 500);
        }
    }


}
