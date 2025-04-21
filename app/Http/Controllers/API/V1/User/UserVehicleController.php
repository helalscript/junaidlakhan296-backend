<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\VehicleDetail;
use App\Services\API\V1\User\Vehicle\VehicleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserVehicleController extends Controller
{

    protected $userVehicleService;
    protected $user;

    /**
     * Set the authenticated user.
     *
     * This constructor method is called after all other service providers have
     * been registered, so we can rely on the Auth facade being available.
     */
    public function __construct(VehicleService $userVehicleService)
    {
        $this->user = auth()->user();
        $this->userVehicleService = $userVehicleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $vehicles = $this->userVehicleService->index($request->per_page ?? 25);
            return Helper::jsonResponse(true, 'Vehicles fetched successfully', 200, $vehicles, true);
        } catch (Exception $e) {
            Log::error("UserVehicleController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch vehicles' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'registration_number' => 'required',
            'type' => 'required',
            'make' => 'required',
            'model' => 'required',
            'license_plate_number_eng' => 'required',
            'license_plate_number_ara' => 'required',
        ]);
        try {
            $vehicle = $this->userVehicleService->store($validatedData);
            return Helper::jsonResponse(true, 'Vehicle created successfully', 200, $vehicle);
        } catch (Exception $e) {
            Log::error("UserVehicleController::store" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to create vehicle', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $vehicle = $this->userVehicleService->show($id);
            return Helper::jsonResponse(true, 'Vehicle fetched successfully', 200, $vehicle);
        } catch (Exception $e) {
            Log::error("UserVehicleController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch vehicle', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'registration_number' => 'required',
            'type' => 'required',
            'make' => 'required',
            'model' => 'required',
            'license_plate_number_eng' => 'required',
            'license_plate_number_ara' => 'required',
        ]);
        try {
            $vehicle = $this->userVehicleService->update($id, $validatedData);
            return Helper::jsonResponse(true, 'Vehicle updated successfully', 200, $vehicle);
        } catch (Exception $e) {
            Log::error("UserVehicleController::update" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update vehicle' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->userVehicleService->destroy($id);
            return Helper::jsonResponse(true, 'Vehicle deleted successfully', 200);
        } catch (Exception $e) {
            Log::error("UserVehicleController::destroy" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to delete vehicle', 500);
        }
    }
}
