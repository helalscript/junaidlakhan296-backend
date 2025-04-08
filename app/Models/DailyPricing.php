<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPricing extends Model
{
    // Table associated with the model
    protected $table = 'daily_pricings';

    // Mass assignable attributes
    protected $fillable = [
        'parking_space_id',
        'rate',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'status',
    ];

    // Cast attributes
    protected $casts = [
        'parking_space_id' => 'integer',
        'rate' => 'decimal:2',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date', // Cast to date
        'end_date' => 'date', // Cast to date
        'status' => 'string',
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'parking_space_id');
    }
}

