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
        'status',
    ];

    // The attributes that should be cast to native types
    protected $casts = [
        'user_id' => 'integer',           // Cast to integer
        'unique_id' => 'string',          // Cast to string
        'title' => 'string',              // Cast to string
        'type_of_spot' => 'string',       // Cast to string
        'max_vehicle_size' => 'string',  // Cast to string
        'total_slots' => 'integer',      // Cast to integer
        'description' => 'string',       // Cast to string (long text as string)
        'latitude' => 'decimal:7',       // Cast to decimal with 7 decimal places
        'longitude' => 'decimal:7',      // Cast to decimal with 7 decimal places
        'address' => 'string',           // Cast to string (nullable)
        'gallery_images' => 'array',     // Cast to array (for JSON column)
        'status' => 'string',            // Cast to string
        'deleted_at' => 'datetime',      // Cast to datetime (for soft deletes)
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getGalleryImagesAttribute($value): array|null
    {
        // If the gallery_images is not empty and is an array
        $images = json_decode($value, true);

        // If the gallery_images is a valid array
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
        return $this->hasMany(HourlyPricing::class, 'parking_space_id');
    }

    public function dailyPricing()
    {
        return $this->hasMany(DailyPricing::class, 'parking_space_id');
    }

    public function monthlyPricing()
    {
        return $this->hasMany(MonthlyPricing::class, 'parking_space_id');
    }

}

