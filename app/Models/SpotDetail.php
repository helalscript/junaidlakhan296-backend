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

    public function getIconAttribute($value): string|null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        // Check if the request is an API request
        if (request()->is('api/*') && !empty($value)) {
            // Return the full URL for API requests
            return url($value);
        }

        // Return only the path for web requests
        return $value;
    }
}
