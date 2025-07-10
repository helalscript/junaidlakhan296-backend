<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;
    protected $table = 'promo_codes';

    protected $fillable = [
        'code',
        'value',
        'uses_limit',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'uses_limit' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => 'string',
    ];

    public function userPromoCodes()
    {
        return $this->hasMany(UserPromoCode::class);
    }
}
