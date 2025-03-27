<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyPricing extends Model
{
    // Table associated with the model
    protected $table = 'monthly_pricing';

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
        'start_time' => 'time',
        'end_time' => 'time',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'string',
    ];


    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'parking_space_id');
    }
}

