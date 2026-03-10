<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerPunchCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'foodtruck_id',
        'punched_in_at',
        'punched_out_at',
    ];

    protected $casts = [
        'punched_in_at' => 'datetime',
        'punched_out_at' => 'datetime',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function foodTruck(): BelongsTo
    {
        return $this->belongsTo(FoodTruck::class, 'foodtruck_id');
    }
}
