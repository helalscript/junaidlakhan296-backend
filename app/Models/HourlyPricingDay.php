<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HourlyPricingDay extends Model
{
    protected $fillable = [
        'hourly_pricing_id', 'day', 'status'
    ];

    protected $casts = [
        'hourly_pricing_id' => 'integer',
        'day' => 'string',
        'status' => 'string',
    ];

    public function hourlyPricing()
    {
        return $this->belongsTo(HourlyPricing::class);
    }
}
