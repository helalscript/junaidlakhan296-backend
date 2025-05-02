<?php

namespace App\Http\Controllers\API\V1\Host\Reservation;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\hostReservationIndexResource;
use App\Services\API\V1\Host\Reservation\HostReservationService;
use Exception;
use Illuminate\Http\Request;
use Log;

class HostReservationController extends Controller
{
    protected $hostReservationService;

    public function __construct(HostReservationService $hostReservationService)
    {
        $this->hostReservationService = $hostReservationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $reservations = $this->hostReservationService->index($request);
            return Helper::jsonResponse(true, 'Reservations fetched successfully', 200, hostReservationIndexResource::collection($reservations), true);
        } catch (Exception $e) {
            Log::error("HostReservationController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch reservations', 403);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $unique_id)
    {
        try {
            $reservation = $this->hostReservationService->show($unique_id);
            return Helper::jsonResponse(true, 'Reservation fetched successfully', 200, $reservation);
        } catch (Exception $e) {
            Log::error("HostReservationController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch reservation', 403);
        }
    }

    public function acceptReservation(string $unique_id)
    {
        try {
            $reservation = $this->hostReservationService->acceptReservation($unique_id);
            return Helper::jsonResponse(true, 'Reservation accepted successfully', 200, $reservation);
        } catch (Exception $e) {
            Log::error("HostReservationController::acceptReservation" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to accept reservation', 403);
        }
    }

    public function cancleReservation(string $unique_id)
    {
        try {
            $reservation = $this->hostReservationService->cancleReservation($unique_id);
            return Helper::jsonResponse(true, 'Reservation cancle successfully', 200, $reservation);
        } catch (Exception $e) {
            Log::error("HostReservationController::cancleReservation" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to cancle reservation', 403);
        }
    }
}
