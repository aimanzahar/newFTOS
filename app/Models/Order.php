<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'foodtruck_id',
        'customer_id',
        'customer_name',
        'items',
        'total',
        'status',
        'order_type',
        'table_number',
        'payment_method',
        'accepted_by',
        'notes',
    ];

    protected $casts = [
        'items' => 'array',
        'total' => 'decimal:2',
    ];

    public function foodTruck()
    {
        return $this->belongsTo(FoodTruck::class, 'foodtruck_id');
    }

    /**
     * The worker/ftadmin who accepted this order.
     * Named 'worker' to avoid collision with the 'accepted_by' FK column in JSON.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
