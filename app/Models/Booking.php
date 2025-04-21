<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'parking_space_id',
        'vehicle_details_id',
        'number_of_slot',
        'pricing_type',
        'pricing_id',
        'booking_date',
        'booking_time_start',
        'booking_time_end',
        'start_time',
        'end_time',
        'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'parking_space_id' => 'integer',
        'vehicle_details_id' => 'integer',
        'number_of_slot' => 'integer',
        'pricing_type' => 'string',
        'pricing_id' => 'integer',
        'booking_date' => 'date',
        'booking_time_start' => 'datetime',
        'booking_time_end' => 'datetime',
        'start_time' => 'datetime',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function vehicleDetail()
    {
        return $this->belongsTo(VehicleDetail::class);
    }

    // Accessor for start_time (only time part)
    public function getBookingTimeStartAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    // Accessor for end_time (only time part)
    public function getBookingTimeEndAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }
}
