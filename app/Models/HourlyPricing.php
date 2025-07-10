<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class HourlyPricing extends Model
{
    // Table associated with the model
    protected $table = 'hourly_pricings';

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
        'start_time' => 'datetime:H:i', // Cast to time
        'end_time' => 'datetime:H:i', // Cast to time
        'status' => 'string', // Cast to string (enum)
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'parking_space_id');
    }

    public function days()
    {
        return $this->hasMany(HourlyPricingDay::class);
    }


    // Accessor for start_time (only time part)
    public function getStartTimeAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    // Accessor for end_time (only time part)
    public function getEndTimeAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

}

