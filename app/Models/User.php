<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function favorites()
{
    return $this->hasMany(Favorite::class);
}


// app/Models/User.php
public function subscriptions()
{
    return $this->hasMany(\App\Models\Subscription::class, 'owner_id');
}

public function activeSubscription()
{
    return $this->hasOne(\App\Models\Subscription::class, 'owner_id')
        ->where('status', 'active')
        ->latest();
}

    // Helpers de rôle
    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isOwner(): bool  { return $this->role === 'owner'; }
    public function isTenant(): bool { return $this->role === 'tenant'; }
}