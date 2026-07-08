<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'description', 'type',
        'listing_type', 'price', 'city', 'address',
        'latitude', 'longitude', 'rooms', 'bathrooms',
        'area', 'amenities', 'status', 'is_furnished',
    ];

    protected function casts(): array
    {
        return [
            'amenities'   => 'array',
            'is_furnished'=> 'boolean',
            'price'       => 'decimal:2',
            'latitude'    => 'decimal:7',
            'longitude'   => 'decimal:7',
        ];
    }

    // Relations
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function coverImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_cover', true);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function favorites()
{
    return $this->hasMany(Favorite::class);
}

// Vérifier si l'utilisateur connecté a mis en favori
public function getIsFavoritedAttribute(): bool
{
    if (!auth()->check()) return false;
    return $this->favorites()->where('user_id', auth()->id())->exists();
}

    // Moyenne des notes
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    
}