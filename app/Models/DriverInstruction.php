<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverInstruction extends Model
{
    protected $fillable = [
        'parking_space_id', 'instructions', 'status'
    ];

    protected $casts = [
        'parking_space_id' => 'integer',
        'instructions' => 'string',
        'status' => 'string',
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }
}

