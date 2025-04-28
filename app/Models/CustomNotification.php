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
        'role',
        'status'
    ];

    // Column casts for type safety
    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'type' => 'string',   
        'role' => 'string',
        'status' => 'string', 
    ];


}

