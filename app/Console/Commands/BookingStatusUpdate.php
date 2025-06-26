<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BookingStatusUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:booking-status-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates booking statuses based on time and payment conditions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // part:1 Find bookings that are paid and confirmed
        $confirmBookings = Booking::where('status', 'confirmed')
            ->whereHas('payment', function ($query) {
                $query->where('status', 'paid');
            })
            ->where('start_time', '<=', Carbon::now())
            ->get();

        // check confirmed booking
        if ($confirmBookings->count() > 0) {
            Log::info('confirmed booking status: ' . $confirmBookings->count());
        } else {
            Log::info('confirmed booking status: 0');
        }
        // changes booking status confirmed to active
        foreach ($confirmBookings as $booking) {
            try {
                // Optionally update booking/payment status
                $booking->update(['status' => 'active']);

                // $this->info('active booking status: ' . $booking->id);
                Log::info('active booking status: ' . $booking->id);
            } catch (Exception $e) {
                Log::error('Failed to update booking status for booking ' . $booking->id . ': ' . $e->getMessage());
            }
        }

        // part:2 Find bookings that are active and time end is less than now
        $activeBookings = Booking::where('status', 'active')
            ->where('end_time', '<=', Carbon::now())
            ->get();

        //check active booking
        if ($activeBookings->count() > 0) {
            Log::info('active booking status: ' . $activeBookings->count());
        } else {
            Log::info('active booking status: 0');
        }

        // changes booking status active to completed
        foreach ($activeBookings as $booking) {
            try {
                // Optionally update booking/payment status
                $booking->update(['status' => 'completed']);
                // $this->info('completed booking status: ' . $booking->id);
                Log::info('completed booking status: ' . $booking->id);
            } catch (Exception $e) {
                Log::error('Failed to update booking status for booking' . $booking->id . ': ' . $e->getMessage());
            }
        }
        Log::info('BookingStatusUpdate job completed at: ' . now()->format('Y-M-d h:i:s A'));
    }


}
