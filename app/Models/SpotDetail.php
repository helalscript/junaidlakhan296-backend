<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotDetail extends Model
{
    protected $fillable = [
        'parking_space_id', 'icon', 'details', 'status'
    ];

    protected $casts = [
        'parking_space_id' => 'integer',
        'icon' => 'string',
        'details' => 'string',
        'status' => 'string',
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }
}
