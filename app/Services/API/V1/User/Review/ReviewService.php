<?php

namespace App\Services\API\V1\User\Review;

use App\Models\Booking;
use App\Models\Review;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReviewService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }


    /**
     * Store a new resource.
     *
     * @param array $validatedData
     * @return mixed
     */
    public function store(array $validatedData)
    {
        try {
            // Get the booking
            $booking = Booking::where('unique_id', $validatedData['booking_unique_id'])
                ->where('user_id', $this->user->id)
                ->where('status', 'completed')
                ->first();
            if (!$booking) {
                throw new Exception('Booking not found or not completed');
            }
            // Check if a review already exists for this booking
            $existingReview = Review::where('booking_id', $booking->id)->first();
            if ($existingReview) {
                throw new Exception('Review already exists for this booking');
            }
            $validatedData['user_id'] = $this->user->id;
            $validatedData['booking_id'] = $booking->id;
            $validatedData['parking_space_id'] = $booking->parking_space_id;
            $validatedData['status'] = 'approved'; // App v2 version app will handle this pending to approved
            // Create the review
            $review = Review::create($validatedData)->fresh();
            $review->makeHidden(['created_at', 'updated_at', 'status']);
            return $review;
        } catch (Exception $e) {
            Log::error("ReviewService::store" . $e->getMessage());
            throw $e;
        }
    }
}