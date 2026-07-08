<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
    protected $fillable = ['property_id', 'path', 'is_cover'];

    protected $appends = ['url']; // ✅ Important — ajoute url dans le JSON

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // ✅ Retourne l'URL complète de l'image
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}