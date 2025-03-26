<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'parking_space_id', 'vehicle_details_id', 'start_time', 'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'parking_space_id' => 'integer',
        'vehicle_details_id' => 'integer',
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
}
