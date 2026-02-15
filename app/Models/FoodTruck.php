<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodTruck extends Model
{
    protected $fillable = [
        'foodtruck_name',
        'business_license_no',
        'foodtruck_desc',
        'user_id',
    ];

    /**
     * Get the user that owns the food truck.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}