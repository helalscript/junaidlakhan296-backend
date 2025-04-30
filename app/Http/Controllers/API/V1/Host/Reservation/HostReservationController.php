<?php

namespace App\Http\Controllers\API\V1\Host\Reservation;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
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
            return Helper::jsonResponse(true, 'Reservations fetched successfully', 200, $reservations, true);
        } catch (Exception $e) {
            Log::error("HostReservationController::index" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
