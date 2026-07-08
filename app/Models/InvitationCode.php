<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationCode extends Model
{
    protected $fillable = [
        'code', 'email', 'is_used',
        'used_by', 'created_by', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used'    => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    // ✅ Relation avec l'utilisateur qui a utilisé le code
    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    // ✅ Relation avec l'admin qui a créé le code
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if ($this->is_used) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }
}