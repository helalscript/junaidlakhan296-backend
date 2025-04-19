<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\VehicleDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserVehicleController extends Controller
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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $vehicles = VehicleDetail::where('user_id', $this->user->id)->paginate($per_page);
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
        // dd($validatedData);
        try {
            $validatedData['user_id'] = $this->user->id;
            $vehicle = VehicleDetail::create($validatedData);
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
