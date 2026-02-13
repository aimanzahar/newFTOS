<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Added role to mass assignable attributes
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'integer', // Ensures the role is always treated as a number
        ];
    }

    /**
     * Role Helper Methods
     * These make your code much more readable in Controllers and Blade files.
     */

    public function isCustomer(): bool
    {
        return $this->role === 1;
    }

    public function isFoodTruckAdmin(): bool
    {
        return $this->role === 2;
    }

    public function isFoodTruckWorker(): bool
    {
        return $this->role === 3;
    }

    public function isSystemAdmin(): bool
    {
        return $this->role === 6;
    }
}