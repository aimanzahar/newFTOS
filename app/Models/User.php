<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 1;
    public const ROLE_FOOD_TRUCK_ADMIN = 2;
    public const ROLE_FOOD_TRUCK_WORKER = 3;
    public const ROLE_SYSTEM_ADMIN = 6;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_no',
        'role',
        'foodtruck_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'integer',
            'foodtruck_id' => 'integer',
        ];
    }

    public function isCustomer(): bool { return (int)$this->role === self::ROLE_CUSTOMER; }
    public function isFoodTruckAdmin(): bool { return (int)$this->role === self::ROLE_FOOD_TRUCK_ADMIN; }
    public function isFoodTruckWorker(): bool { return (int)$this->role === self::ROLE_FOOD_TRUCK_WORKER; }
    public function isSystemAdmin(): bool { return (int)$this->role === self::ROLE_SYSTEM_ADMIN; }

    public function foodTruck(): BelongsTo
    {
        return $this->belongsTo(FoodTruck::class, 'foodtruck_id');
    }
}