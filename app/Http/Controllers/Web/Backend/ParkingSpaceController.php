<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Helpers\Helper;

class ParkingSpaceController extends Controller
{
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

                ->addColumn('action', function ($data) {
                    return '<div class="action-wrapper">
                     <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="View" onclick="window.location.href=\'' . route('parking_spaces.show', $data->id) . '\'">
                        <i class="material-symbols-outlined fs-16 text-primary">visibility</i>
                        </button>
                                <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete" onclick="deleteRecord(event,' . $data->id . ')">
                                <i class="material-symbols-outlined fs-16 text-danger">delete</i>
                                </button>
                            </div>';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return view("backend.layouts.parking_space.index", compact('isVerified'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
        $data = ParkingSpace::with(['user', 'hourlyPricing', 'dailyPricing', 'monthlyPricing', 'reviews', 'driverInstructions', 'spotDetails', 'bookings'])->find($id);
        // dd($data->toArray());
        return view("backend.layouts.parking_space.show", compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
            $data = ParkingSpace::find($id);
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
            return back()->with('success', 'Item status changed successfully.');
        } catch (Exception $e) {
            Log::error('ParkingSpaceController::verified- ' . $e->getMessage());
            return back()->with('error', 'Something went wrong.');
        }
    }
}
