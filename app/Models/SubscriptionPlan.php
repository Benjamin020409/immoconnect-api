<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'max_properties',
        'duration_days',
        'price',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_properties' => 'integer',
            'duration_days'  => 'integer',
            'price'          => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    // Vérifier si le plan est illimité
    public function isUnlimited(): bool
    {
        return $this->max_properties === -1;
    }
}