<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menus';

    protected $fillable = [
        'foodtruck_id',
        'name',
        'category',
        'base_price',
        'quantity',
        'description',
        'image',
        'original_image',
    ];

    public function foodTruck()
    {
        return $this->belongsTo(FoodTruck::class);
    }
}
