<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HourlyPricing extends Model
{
    // Table associated with the model
    protected $table = 'hourly_pricing';

    // Mass assignable attributes
    protected $fillable = [
        'parking_space_id',
        'rate',
        'start_time',
        'end_time',
        'status',
    ];

    // Cast attributes
    protected $casts = [
        'parking_space_id' => 'integer',
        'rate' => 'decimal:2', // Cast to decimal with 2 decimal places
        'start_time' => 'time', // Cast to time
        'end_time' => 'time', // Cast to time
        'status' => 'string', // Cast to string (enum)
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'parking_space_id');
    }
}

