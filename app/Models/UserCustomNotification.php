<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCustomNotification extends Model
{
    // Define the table name if it's not the default
    protected $table = 'user_custom_notifications';

    // Fillable columns for mass assignment
    protected $fillable = [
        'user_id',
        'custom_notification_id',
        'status'
    ];

    // Column casts for type safety
    protected $casts = [
        'user_id' => 'integer',
        'custom_notification_id' => 'integer',
        'status' => 'string',
    ];



    // A UserCustomNotification belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class); // Foreign key 'user_id'
    }

    // A UserCustomNotification belongs to a CustomNotification
    public function customNotification()
    {
        return $this->belongsTo(CustomNotification::class); // Foreign key 'custom_notification_id'
    }
}
