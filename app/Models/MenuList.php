<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuList extends Model
{
    use HasFactory;

    // This tells Laravel which table to use
    protected $table = 'menu_lists';

    // These are the fields you want to be "fillable" (saveable)
    protected $fillable = [
        'food_truck_id',
        'menu_name',
        'menu_category',
        'menu_baseprice',
        'menu_quantity',
        'menu_desc',
        'menu_image',
        'is_active',
    ];

    /**
     * Relationship: A menu item belongs to a Food Truck.
     */
    public function foodTruck()
    {
        return $this->belongsTo(FoodTruck::class);
    }
}