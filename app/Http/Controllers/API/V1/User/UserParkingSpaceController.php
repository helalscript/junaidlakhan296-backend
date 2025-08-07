<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\IndexForUserDailyResource;
use App\Http\Resources\API\V1\IndexForUserHourlyResource;
use App\Http\Resources\API\V1\IndexForUserMonthlyResource;
use App\Http\Resources\API\V1\ShowForUserDailyResource;
use App\Http\Resources\API\V1\ShowForUserHourlyResource;
use App\Http\Resources\API\V1\ShowForUserMonthlyResource;
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
            'start_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
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
                $validatedData['end_date'] ?? now()->format('Y-m-d'),
                'hourly'
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
            'start_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
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
    public function indexForUsersMonthly(Request $request)
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
            $result = $this->userParkingSpaceService->getMonthlyPricing($request);

            // transform the data
            $transformedData = $this->userParkingSpaceService->transformPricingData(
                $result,
                $startTime,
                $endTime,
                $startDate,
                $endDate,
                'monthly'
            );
            $result->setCollection($transformedData);

            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, IndexForUserMonthlyResource::collection($result), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsersMonthly - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
        }
    }

    public function showForUsersMonthly($id, Request $request)
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
            $pricing = $this->userParkingSpaceService->getMonthlyPricingDetails($id, $request);
            return Helper::jsonResponse(true, 'Monthly parking space details fetched successfully', 200, new ShowForUserMonthlyResource($pricing));
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::showForUsersMonthly - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch pricing details.', 500);
        }
    }


    public function indexForUsers(Request $request)
    {
        $validatedData = $request->validate([
            'per_page' => 'nullable|numeric|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:10',
        ]);
        try {
            $result = $this->userParkingSpaceService->indexForUsers($request);
            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, $result, true);
            // return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, IndexForUserResource::collection($result), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsers - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
        }
    }
    public function showForUsers(string $ParkingSpaceSlug)
    {
        try {
            $result = $this->userParkingSpaceService->showForUsers($ParkingSpaceSlug);
            return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, $result);
            // return Helper::jsonResponse(true, 'Parking spaces fetched successfully', 200, IndexForUserResource::collection($result), true);
        } catch (Exception $e) {
            Log::error("ParkingSpaceController::indexForUsers - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces. ' . $e->getMessage(), 500);
        }
    }

    
}
