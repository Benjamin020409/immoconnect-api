<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Property;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    // ─── Liste tous les abonnements ───────────────────────────
    public function index(Request $request)
    {
        $query = Subscription::with([
                'owner:id,name,email,phone',
                'plan:id,name,max_properties,price',
                'createdBy:id,name',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('owner', fn($q) =>
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
            );
        }

        $subscriptions = $query->paginate($request->per_page ?? 10);

        // Enrichir avec les stats d'annonces
        $subscriptions->getCollection()->transform(function ($sub) {
            $usedCount = Property::where('user_id', $sub->owner_id)
                ->whereIn('status', ['active', 'pending', 'rented'])
                ->count();

            $sub->used_properties  = $usedCount;
            $sub->is_expired       = $sub->isExpired();
            $sub->remaining        = $sub->max_properties === -1
                ? -1
                : max(0, $sub->max_properties - $usedCount);
            $sub->usage_percent    = $sub->max_properties === -1
                ? 0
                : ($sub->max_properties > 0 ? round($usedCount / $sub->max_properties * 100) : 0);

            return $sub;
        });

        return response()->json($subscriptions);
    }

    // ─── Liste des plans disponibles ──────────────────────────
    public function plans()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return response()->json($plans);
    }

    // ─── Créer un abonnement pour un propriétaire ─────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id'       => 'required|exists:users,id',
            'plan_id'        => 'required|exists:subscription_plans,id',
            'max_properties' => 'nullable|integer|min:-1',
            'starts_at'      => 'nullable|date',
            'expires_at'     => 'nullable|date|after:starts_at',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Vérifier que l'utilisateur est bien un propriétaire
        $owner = User::findOrFail($validated['owner_id']);
        if ($owner->role !== 'owner') {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas un propriétaire.',
            ], 422);
        }

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Calculer expires_at depuis duration_days si pas fourni
        $expiresAt = $validated['expires_at'] ?? null;
        if (!$expiresAt && $plan->duration_days > 0) {
            $startsAt  = $validated['starts_at'] ?? now();
            $expiresAt = \Carbon\Carbon::parse($startsAt)->addDays($plan->duration_days);
        }

        // Désactiver l'ancien abonnement actif
        Subscription::where('owner_id', $validated['owner_id'])
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $subscription = Subscription::create([
            'owner_id'       => $validated['owner_id'],
            'plan_id'        => $validated['plan_id'],
            'max_properties' => $validated['max_properties'] ?? $plan->max_properties,
            'starts_at'      => $validated['starts_at'] ?? now(),
            'expires_at'     => $expiresAt,
            'status'         => 'active',
            'created_by'     => auth()->id(),
            'notes'          => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message'      => 'Abonnement créé avec succès.',
            'subscription' => $subscription->load(['owner:id,name,email', 'plan']),
        ], 201);
    }

    // ─── Détail d'un abonnement ───────────────────────────────
    public function show($id)
    {
        $subscription = Subscription::with([
                'owner:id,name,email,phone',
                'plan',
                'createdBy:id,name',
            ])
            ->findOrFail($id);

        $usedCount = Property::where('user_id', $subscription->owner_id)
            ->whereIn('status', ['active', 'pending', 'rented'])
            ->count();

        return response()->json([
            'subscription'  => $subscription,
            'used_properties' => $usedCount,
            'remaining'     => $subscription->max_properties === -1
                ? -1
                : max(0, $subscription->max_properties - $usedCount),
            'usage_percent' => $subscription->max_properties === -1
                ? 0
                : ($subscription->max_properties > 0
                    ? round($usedCount / $subscription->max_properties * 100)
                    : 0),
        ]);
    }

    // ─── Modifier un abonnement ───────────────────────────────
    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'max_properties' => 'nullable|integer|min:-1',
            'expires_at'     => 'nullable|date',
            'status'         => 'nullable|in:active,expired,cancelled',
            'notes'          => 'nullable|string|max:500',
        ]);

        $subscription->update($validated);

        return response()->json([
            'message'      => 'Abonnement mis à jour.',
            'subscription' => $subscription->load(['owner:id,name,email', 'plan']),
        ]);
    }

    // ─── Annuler un abonnement ────────────────────────────────
    public function destroy($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Abonnement annulé.']);
    }

    // ─── Abonnement d'un propriétaire spécifique ──────────────
    public function ownerSubscription($ownerId)
    {
        $subscription = Subscription::with(['plan'])
            ->where('owner_id', $ownerId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'subscription' => null,
                'message'      => 'Aucun abonnement actif.',
            ]);
        }

        $usedCount = Property::where('user_id', $ownerId)
            ->whereIn('status', ['active', 'pending', 'rented'])
            ->count();

        return response()->json([
            'subscription'   => $subscription,
            'used_properties'=> $usedCount,
            'remaining'      => $subscription->max_properties === -1
                ? -1
                : max(0, $subscription->max_properties - $usedCount),
            'usage_percent'  => $subscription->max_properties === -1
                ? 0
                : ($subscription->max_properties > 0
                    ? round($usedCount / $subscription->max_properties * 100)
                    : 0),
            'is_expired'     => $subscription->isExpired(),
        ]);
    }

    // ─── Propriétaires sans abonnement ────────────────────────
    public function ownersWithoutSubscription()
    {
        $owners = User::where('role', 'owner')
            ->whereDoesntHave('subscriptions', fn($q) => $q->where('status', 'active'))
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($owners);
    }

    // ─── Stats abonnements pour le dashboard ──────────────────
    public function stats()
    {
        $active    = Subscription::where('status', 'active')->count();
        $expired   = Subscription::where('status', 'expired')->count();
        $cancelled = Subscription::where('status', 'cancelled')->count();

        // Propriétaires ayant atteint leur quota
        $atLimit = Subscription::where('status', 'active')
            ->where('max_properties', '>', 0)
            ->get()
            ->filter(function ($sub) {
                $used = Property::where('user_id', $sub->owner_id)
                    ->whereIn('status', ['active', 'pending', 'rented'])
                    ->count();
                return $used >= $sub->max_properties;
            })
            ->count();

        // Abonnements expirant dans les 7 prochains jours
        $expiringSoon = Subscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>=', now())
            ->count();

        return response()->json([
            'active'         => $active,
            'expired'        => $expired,
            'cancelled'      => $cancelled,
            'at_limit'       => $atLimit,
            'expiring_soon'  => $expiringSoon,
        ]);
    }
}