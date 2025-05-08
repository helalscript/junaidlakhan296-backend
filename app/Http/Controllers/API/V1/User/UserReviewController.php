<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\API\V1\User\Review\ReviewService;
use Exception;
use Illuminate\Http\Request;
use Log;
use Pest\Console\Help;

class UserReviewController extends Controller
{
    protected $user;
    protected $userReviewService;

    /**
     * Set the authenticated user.
     *
     * This constructor method is called after all other service providers have
     * been registered, so we can rely on the Auth facade being available.
     */
    public function __construct(ReviewService $userReviewService)
    {
        $this->user = auth()->user();
        $this->userReviewService = $userReviewService;
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'booking_unique_id' => 'required|exists:bookings,unique_id',
            'comment' => 'nullable|string|max:150',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        try {
            $review = $this->userReviewService->store($validatedData);
            return Helper::jsonResponse(true, 'Review created successfully', 201, $review);
        } catch (Exception $e) {
            Log::error("UserReviewController::store" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to create review ' . $e->getMessage(), 403);
        }
    }
}
