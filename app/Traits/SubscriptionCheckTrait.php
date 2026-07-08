<?php

namespace App\Traits;

use App\Models\Subscription;
use App\Models\Property;

trait SubscriptionCheckTrait
{
    // ─── Vérifier si le propriétaire peut publier ─────────────
    protected function checkSubscriptionQuota($ownerId): array
    {
        // Récupérer l'abonnement actif
        $subscription = Subscription::where('owner_id', $ownerId)
            ->where('status', 'active')
            ->latest()
            ->first();

        // Pas d'abonnement — plan gratuit par défaut (2 annonces)
        if (!$subscription) {
            $maxProperties = 2;
            $planName      = 'Gratuit';
            $isDefault     = true;
        } else {
            // Vérifier si l'abonnement est expiré
            if ($subscription->isExpired()) {
                $subscription->update(['status' => 'expired']);
                return [
                    'allowed'  => false,
                    'reason'   => 'expired',
                    'message'  => 'Votre abonnement a expiré. Contactez l\'administrateur.',
                    'plan'     => $subscription->plan->name ?? 'Inconnu',
                ];
            }

            $maxProperties = $subscription->max_properties;
            $planName      = $subscription->plan->name ?? 'Inconnu';
            $isDefault     = false;
        }

        // Plan illimité (-1)
        if ($maxProperties === -1) {
            return [
                'allowed'        => true,
                'reason'         => 'unlimited',
                'plan'           => $planName,
                'max_properties' => -1,
                'used'           => 0,
                'remaining'      => -1,
                'usage_percent'  => 0,
            ];
        }

        // Compter les annonces actives
        $usedCount = Property::where('user_id', $ownerId)
            ->whereIn('status', ['active', 'pending', 'rented'])
            ->count();

        $remaining     = max(0, $maxProperties - $usedCount);
        $usagePercent  = $maxProperties > 0
            ? round($usedCount / $maxProperties * 100)
            : 100;

        // Quota atteint
        if ($usedCount >= $maxProperties) {
            return [
                'allowed'        => false,
                'reason'         => 'quota_reached',
                'message'        => "Vous avez atteint votre limite de {$maxProperties} annonce(s) pour le plan {$planName}. Contactez l'administrateur pour upgrader.",
                'plan'           => $planName,
                'max_properties' => $maxProperties,
                'used'           => $usedCount,
                'remaining'      => 0,
                'usage_percent'  => 100,
                'is_default'     => $isDefault,
            ];
        }

        return [
            'allowed'        => true,
            'reason'         => 'ok',
            'plan'           => $planName,
            'max_properties' => $maxProperties,
            'used'           => $usedCount,
            'remaining'      => $remaining,
            'usage_percent'  => $usagePercent,
            'is_default'     => $isDefault,
            // Alerte si proche de la limite (>=80%)
            'warning'        => $usagePercent >= 80,
            'warning_message'=> $usagePercent >= 80
                ? "Attention : vous avez utilisé {$usedCount}/{$maxProperties} annonces de votre plan {$planName}."
                : null,
        ];
    }
}