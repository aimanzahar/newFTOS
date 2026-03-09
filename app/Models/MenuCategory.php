<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable = ['foodtruck_id', 'name', 'color', 'sort_order'];

    public function foodTruck()
    {
        return $this->belongsTo(FoodTruck::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'category', 'name')
            ->where('foodtruck_id', $this->foodtruck_id);
    }
}
