<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\UserBookingExtendRequestViewResource;
use App\Http\Resources\API\V1\UserBookingIndexResource;
use App\Http\Resources\API\V1\UserBookingShowResource;
use App\Http\Resources\API\V1\UserDashboardTransactionResource;
use App\Models\Booking;
use App\Services\API\V1\User\Booking\BookingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserBookingController extends Controller
{
    protected $user;
    protected $userBookingService;

    /**
     * Set the authenticated user.
     *
     * This constructor method is called after all other service providers have
     * been registered, so we can rely on the Auth facade being available.
     */
    public function __construct(BookingService $userBookingService)
    {
        $this->user = auth()->user();
        $this->userBookingService = $userBookingService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $bookings = $this->userBookingService->index($request);
            return Helper::jsonResponse(true, 'Bookings fetched successfully', 200, UserBookingIndexResource::collection($bookings), true);
        } catch (Exception $e) {
            Log::error("UserBookingController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch parking spaces' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'parking_space_id' => 'required|exists:parking_spaces,id',
            'vehicle_details_id' => 'required|exists:vehicle_details,id',
            'number_of_slot' => 'required|integer|min:1',
            'pricing_type' => 'required|in:hourly,daily,monthly',
            // 'booking_date_start' => 'required|date|after_or_equal:today',
            // 'booking_date_end' => 'required|date|after_or_equal:booking_date_start',
            // 'booking_time_start' => 'required|date_format:H:i',
            // 'booking_time_end' => 'required|date_format:H:i|after:booking_time_start',
            'start_time' => 'required|date_format:Y-m-d\TH:i|after_or_equal:now',
            'end_time' => 'required|date_format:Y-m-d\TH:i|after:start_time',
        ], [
            'vehicle_details_id.required' => 'The vehicle details field is required.',
        ]);
        // Validate pricing_id based on pricing_type
        if ($request->pricing_type === 'hourly') {
            $pricingData = $request->validate([
                'pricing_id' => 'required|exists:hourly_pricings,id',
            ]);
        } elseif ($request->pricing_type === 'daily') {
            $pricingData = $request->validate([
                'pricing_id' => 'required|exists:daily_pricings,id',
            ]);
        } elseif ($request->pricing_type === 'monthly') {
            $pricingData = $request->validate([
                'pricing_id' => 'required|exists:monthly_pricings,id',
            ]);
        }

        // Merge pricing_id into validated data
        $validatedData = array_merge($validatedData, $pricingData);

        try {

            $booking = $this->userBookingService->store($validatedData);
            return Helper::jsonResponse(true, 'Booking created successfully', 200, new UserBookingShowResource($booking));
        } catch (Exception $e) {
            Log::error("UserBookingController::store" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to create booking' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $unique_id)
    {
        try {
            $booking = $this->userBookingService->show($unique_id);
            return Helper::jsonResponse(true, 'Booking fetched successfully', 200, new UserBookingShowResource($booking));
        } catch (Exception $e) {
            Log::error("UserBookingController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch booking', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validatedData = $request->validate([
                'parking_space_id' => 'required|exists:parking_spaces,id',
            ]);
            $validatedData['user_id'] = $this->user->id;
            $booking = Booking::where('id', $id)->update($validatedData);
            return Helper::jsonResponse(true, 'Booking updated successfully', 200, $booking);
        } catch (Exception $e) {
            Log::error("UserBookingController::update" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update booking', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->userBookingService->destroy($id);
            return Helper::jsonResponse(true, 'Booking deleted successfully', 200);
        } catch (Exception $e) {
            Log::error("UserBookingController::destroy" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to delete booking', 500);
        }
    }
    public function bookingExtendRequestView(Request $request)
    {
        $validatedData = $request->validate([
            'unique_id' => 'required|exists:bookings,unique_id',
            'extend_time' => 'required|numeric|min:1',
            'extend_type' => 'required|in:hourly,daily,monthly',
        ]);
        try {
            $booking = $this->userBookingService->bookingExtendRequestView($validatedData);
            return Helper::jsonResponse(true, 'Booking extended successfully', 200, new UserBookingExtendRequestViewResource($booking));
        } catch (Exception $e) {
            Log::error("UserBookingController::bookingExtendRequestView" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to extend booking' . $e->getMessage(), 500);
        }
    }
    public function bookingExtendRequestStore(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'unique_id' => 'required|exists:bookings,unique_id',
                'extend_time' => 'required|numeric|min:1',
                'extend_type' => 'required|in:hourly,daily,monthly',
            ]);
            $appVersion = 1; // in future this will be dynamic and booking will be extended only if the app version is greater than this
            if ($appVersion) {
                return Helper::jsonErrorResponse('Booking Extension not Available', 400);
            }
            $booking = $this->userBookingService->bookingExtendRequestStore($validatedData);
            return Helper::jsonResponse(true, 'Booking extended successfully', 200, $booking);
        } catch (Exception $e) {
            Log::error("UserBookingController::bookingExtendRequestStore" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to extend booking', 500);
        }
    }

    public function userDashboardData()
    {
        try {
            $data = $this->userBookingService->userDashboardData();
            return Helper::jsonResponse(true, 'Dashboard data fetched successfully', 200, $data);
        } catch (Exception $e) {
            Log::error("UserBookingController::userDashboardData" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch dashboard data', 500);
        }
    }
    public function userDashboardTransactions(Request $request)
    {
        try {
            $data = $this->userBookingService->userDashboardTransactions($request);
            return Helper::jsonResponse(true, 'Dashboard data fetched successfully', 200, UserDashboardTransactionResource::collection($data), true);
        } catch (Exception $e) {
            Log::error("UserBookingController::userDashboardData" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch dashboard data', 500);
        }
    }
}
