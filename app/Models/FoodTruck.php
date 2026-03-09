<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodTruck extends Model
{
    use HasFactory;

    protected $table = 'food_trucks';

    /**
     * The attributes that are mass assignable.
     * * Added 'status' to allow the AdminController to update 
     * the approval status.
     */
    protected $fillable = [
        'foodtruck_name',
        'business_license_no',
        'foodtruck_desc',
        'user_id',
        'status',
        'is_operational',
    ];

    protected $casts = [
        'is_operational' => 'boolean',
    ];

    /**
     * Helper to check if the truck is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Get the owner of the food truck.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for owner (for convenient access).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the staff members working for this truck.
     */
    public function staff()
    {
        return $this->hasMany(User::class, 'foodtruck_id');
    }

    /**
     * Get the workers (staff with role 3) for this truck.
     */
    public function workers()
    {
        return $this->hasMany(User::class, 'foodtruck_id')->where('role', 3);
    }

    /**
     * Get the menu items for this truck.
     */
    public function menus()
    {
        return $this->hasMany(\App\Models\Menu::class, 'foodtruck_id');
    }
}