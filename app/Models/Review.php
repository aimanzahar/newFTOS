<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'foodtruck_id',
        'menu_item_name',
        'rating',
        'comment',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function foodTruck()
    {
        return $this->belongsTo(FoodTruck::class, 'foodtruck_id');
    }
}
