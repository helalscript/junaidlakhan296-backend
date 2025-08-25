<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use App\Services\API\V1\User\NotificationOrMail\NotificationOrMailService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Notification;
use Yajra\DataTables\DataTables;
use App\Helpers\Helper;

class ParkingSpaceController extends Controller
{
    protected $notificationOrMailService;
    public function __construct(NotificationOrMailService $notificationOrMailService)
    {
        $this->notificationOrMailService = $notificationOrMailService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isVerified = $request->isVerified ?? false;
        //  $data = ParkingSpace::where('is_verified', $isVerified)->latest()->get();
        //  dd($data);
        if ($request->ajax()) {
            $data = ParkingSpace::where('is_verified', $isVerified)->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('is_feature', function ($data) {
                    $is_feature = '<div class="form-check form-switch">';
                    $is_feature .= '<input onclick="changeStatus(event,' . $data->id . ')" type="checkbox" class="form-check-input" style="border-radius: 25rem;width:40px"' . $data->id . '" name="is_feature"';

                    if ($data->is_feature == "active") {
                        $is_feature .= ' checked';
                    }

                    $is_feature .= '>';
                    $is_feature .= '</div>';

                    return $is_feature;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="action-wrapper">
                     <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="View" onclick="window.location.href=\'' . route('parking_spaces.show', $data->id) . '\'">
                        <i class="material-symbols-outlined fs-16 text-primary">visibility</i>
                        </button>
                        <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit" onclick="window.location.href=\'' . route('parking_spaces.edit', $data->id) . '\'">
                         <i class="material-symbols-outlined fs-16 text-body">edit</i>
                        </button>
                    <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete" onclick="deleteRecord(event,' . $data->id . ')">
                               <i class="material-symbols-outlined fs-16 text-danger">delete</i>
                    </button>
                            </div>';
                })
                ->rawColumns(['action', 'is_feature'])
                ->make(true);
        }
        return view("backend.layouts.parking_space.index", compact('isVerified'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = ParkingSpace::with(['user', 'hourlyPricing', 'dailyPricing', 'monthlyPricing', 'reviews', 'driverInstructions', 'spotDetails', 'bookings'])->find($id);
        // dd($data->toArray());
        return view("backend.layouts.parking_space.show", compact('data'));
    }

    public function destroy(string $id)
    {
        $data = ParkingSpace::find($id);
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }
        if ($data->gallery_images) {
            // Delete gallery images from storage
            foreach ($data->gallery_images as $image) {
                Helper::fileDelete($image);
            }
        }
        // Delete the parking space record
        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.'
        ]);
    }


    /**
     * Change the status of the specified resource from storage.
     */
    public function status(Request $request, $id)
    {
        $data = ParkingSpace::find($id);

        // check if the category exists
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }

        // toggle status of the category
        if ($data->status == 'active') {
            $data->status = 'inactive';
        } else {
            $data->status = 'active';
        }

        // save the changes
        $data->save();
        return response()->json([
            'success' => true,
            'message' => 'Item status changed successfully.'
        ]);
    }

    public function verified($id)
    {
        try {
            DB::beginTransaction();
            $data = ParkingSpace::with(['user'])->find($id);
            // check if the category exists
            if (empty($data)) {
                return back()->with('error', 'Item not found.');
            }

            // toggle status of the category
            if ($data->is_verified == '1') {
                $data->is_verified = '0';
            } else {
                $data->is_verified = '1';
            }

            // save the changes
            $data->save();

            //send notification to host
            $this->notificationOrMailService->sendNotification(false, $data->user, 'Your parking space has been verified', 'others_notification');
            DB::commit();
            return back()->with('success', 'Item status changed successfully.');
        } catch (Exception $e) {
            Log::error('ParkingSpaceController::verified- ' . $e->getMessage());
            return back()->with('error', 'Something went wrong.');
        }
    }

    public function isFeature(Request $request, $id)
    {
        $data = ParkingSpace::find($id);
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }
        if ($data->is_feature == 'active') {
            $data->is_feature = 'inactive';
        } else {
            $data->is_feature = 'active';
        }

        $data->save();
        return response()->json([
            'success' => true,
            'message' => 'Item status changed successfully.'
        ]);
    }

    public function edit(string $id)
    {
        $data = ParkingSpace::with([
            'user',
            'hourlyPricing' => function ($q) {
                $q->with(['days']);
            },
            'dailyPricing',
            'monthlyPricing',
            'reviews',
            'driverInstructions',
            'spotDetails',
            'bookings'
        ])->find($id);
        // Pass the selected days as an array of day names
        $selectedDays = $data->hourlyPricing[0]->days->pluck('day')->toArray();  // Get the array of day names
        // dd($data->toArray());
        return view("backend.layouts.parking_space.edit", compact('data', 'selectedDays'));
    }
    public function update(Request $request, string $ParkingSpaceId)
    {
        // dd($request->all());
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
        // dd($validatedData);
        try {
            DB::beginTransaction();

            $parkingSpace = ParkingSpace::findOrFail($ParkingSpaceId);

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

                    if (isset($validatedData['hourly_pricing'][0]['days']) && $hourly->days()->exists()) {
                        $hourly->days()->delete();
                    }
                    $hourly->delete();
                });
                foreach ($validatedData['hourly_pricing'] as $hourly) {
                    $days = isset($hourly['days']) ? $hourly['days'] : [];
                    unset($hourly['days']);
                    $hourly['is_free_pricing'] = $hourly['rate'] == 0 ? true : false; // Check if the rate is 0 to set free pricing
                    $newHourly = $parkingSpace->hourlyPricing()->create($hourly);
                    if (!empty($days)) {
                        foreach ($days as $day) {
                            $newHourly->days()->create($day);
                        }
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

            return redirect()->back()->with('success', 'Parking space updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ParkingSpaceController::update => " . $e->getMessage());
            return back()->with('error', 'Something went wrong. Please try again.')->withInput();
        }
    }

    public function deleteImage(Request $request, $id)
    {
        $parkingSpace = ParkingSpace::find($id);
        if (empty($parkingSpace)) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ]);
        }

        $imageKey = $request->input('image_key');
        Log::info("imageKey => " . $imageKey);
        $galleryImages = $parkingSpace->gallery_images ?? [];

        if (isset($galleryImages[$imageKey])) {
            $imagePath = $galleryImages[$imageKey];

            // Delete file from storage (assuming public disk)
            if (!empty($imagePath)) {
                if (file_exists(public_path($imagePath))) {
                    Helper::fileDelete(public_path($imagePath));
                }
            }

            // Remove from array and save
            unset($galleryImages[$imageKey]);
            $parkingSpace->gallery_images = array_values($galleryImages); // reset index
            $parkingSpace->save();

            return response()->json([
                'success' => true,
                'message' => 'Item status changed successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image not found'
        ]);
    }
}
