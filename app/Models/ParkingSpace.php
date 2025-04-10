<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParkingSpace extends Model
{
    use SoftDeletes;

    // Table associated with the model
    protected $table = 'parking_spaces';

    // The attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'unique_id',
        'title',
        'type_of_spot',
        'max_vehicle_size',
        'total_slots',
        'description',
        'latitude',
        'longitude',
        'address',
        'gallery_images',
        'slug',
        'status',
    ];

    // The attributes that should be cast to native types
    protected $casts = [
        'user_id' => 'integer',
        'unique_id' => 'string',
        'title' => 'string',
        'type_of_spot' => 'string',
        'max_vehicle_size' => 'string',
        'total_slots' => 'integer',
        'description' => 'string',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'address' => 'string',
        'gallery_images' => 'array',
        'slug' => 'string',
        'status' => 'string',
        'deleted_at' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getGalleryImagesAttribute($value): array|null
    {
        // Attempt to decode the gallery_images field as JSON
        $images = json_decode($value, true);

        // If the gallery_images is a valid array and not null, process it
        if (is_array($images)) {
            // Check if the request is an API request
            if (request()->is('api/*')) {
                // Return the full URL for API requests
                return array_map(fn($image) => url($image), $images);
            }

            // Return only the paths for web requests
            return $images;
        }

        // Return null if no valid gallery_images are set
        return null;
    }

    public function hourlyPricing()
    {
        return $this->hasMany(HourlyPricing::class);
    }

    public function dailyPricing()
    {
        return $this->hasMany(DailyPricing::class);
    }

    public function monthlyPricing()
    {
        return $this->hasMany(MonthlyPricing::class);
    }
    public function driverInstructions()
    {
        return $this->hasMany(DriverInstruction::class);
    }

    public function spotDetails()
    {
        return $this->hasMany(SpotDetail::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function averageRating()
{
    return $this->reviews()->where('status', 'approved')->avg('rating') ?? 0;
}
}

