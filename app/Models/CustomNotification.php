<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomNotification extends Model
{
    // Define the table name if it's not the default
    protected $table = 'custom_notifications';

    // Fillable columns for mass assignment
    protected $fillable = [
        'title',
        'description',
        'type',
        'status'
    ];

    // Column casts for type safety
    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'status' => 'string', 
        'type' => 'string',   
    ];

}

