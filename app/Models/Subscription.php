<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'owner_id',
        'plan_id',
        'max_properties',
        'starts_at',
        'expires_at',
        'status',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Vérifier si l'abonnement est expiré
    public function isExpired(): bool
    {
        if (!$this->expires_at) return false;
        return $this->expires_at->isPast();
    }

    // Vérifier si illimité
    public function isUnlimited(): bool
    {
        return $this->max_properties === -1;
    }
}