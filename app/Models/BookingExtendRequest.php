<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingExtendRequest extends Model
{
    protected $fillable = [
        'booking_id', 'start_date', 'end_date', 'start_time', 'end_time', 'status'
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'time',
        'end_time' => 'time',
        'status' => 'string',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

