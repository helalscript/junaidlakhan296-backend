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
                               
                            </div>';
                })
                ->rawColumns(['action', 'is_feature'])
                ->make(true);
        }
        return view("backend.layouts.parking_space.index", compact('isVerified'));
    }
    //  <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete" onclick="deleteRecord(event,' . $data->id . ')">
    //                             <i class="material-symbols-outlined fs-16 text-danger">delete</i>
    //                             </button>

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
}
