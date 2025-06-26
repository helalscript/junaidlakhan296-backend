<?php

namespace App\Http\Controllers\Web\Backend\CMS;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use App\Enums\Page;
use App\Enums\Section;
use App\Helpers\Helper;
use App\Models\CMS;
use Exception;

use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Faq::latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($data) {
                    $status = '<div class="form-check form-switch">';
                    $status .= '<input onclick="changeStatus(event,' . $data->id . ')" type="checkbox" class="form-check-input" style="border-radius: 25rem;width:40px"' . $data->id . '" name="status"';

                    if ($data->status == "active") {
                        $status .= ' checked';
                    }

                    $status .= '>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="action-wrapper">
                        <a type="button" href="' . route('faqs.edit', $data->id) . '"
                                class="ps-0 border-0 bg-transparent lh-1 position-relative top-2"
                                 ><i class="material-symbols-outlined fs-16 text-body">edit</i>
                            </a>
                        <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete" onclick="deleteRecord(event,' . $data->id . ')">
                        <i class="material-symbols-outlined fs-16 text-danger">delete</i>
                        </button>
             
                </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view("backend.layouts.faq.index");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("backend.layouts.faq.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'type' => 'required|in:user,admin,host',
        ]);
// dd($validatedData);
        try {
            Faq::Create($validatedData);
            flash()->success('Faq created Successfully');
            return redirect()->route('faqs.index');
        } catch (Exception $e) {
            Log::error("HomePageController::store" . $e->getMessage());
            flash()->error('Faq created Failed');
            return redirect()->route('faqs.index');
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = Faq::findOrFail($id);

        return view("backend.layouts.faq.edit", compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'type' => 'required|in:user,admin,host',
        ]);

        try {
            $data = Faq::findOrFail($id);
            $data->update($validatedData);
            flash()->success('Faq updated Successfully');
            return redirect()->route('faqs.index');
        } catch (Exception $e) {
            Log::error("HomePageController::store" . $e->getMessage());
            flash()->error('Faq updated Failed');
            return redirect()->route('faqs.index');
        }
    }

    public function destroy(string $id)
    {
        $data = Faq::findOrFail($id);
        // check here BookingRequest hotel_id === identifyHotel
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }

        if (!empty($data->image)) {
            Helper::fileDelete(public_path($data->image));
        }

        $data->delete();

        return response()->json([
            "success" => true,
            "message" => "Item deleted successfully."
        ]);
    }

    public function status(Request $request, $id)
    {
        $data = Faq::find($id);
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }
        if ($data->status == 'active') {
            $data->status = 'inactive';
        } else {
            $data->status = 'active';
        }

        $data->save();
        return response()->json([
            'success' => true,
            'message' => 'Item status changed successfully.'
        ]);
    }
}
