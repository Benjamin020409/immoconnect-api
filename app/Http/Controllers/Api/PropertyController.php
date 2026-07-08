<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Traits\SubscriptionCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    use SubscriptionCheckTrait;

    // ─── Liste publique avec filtres, tri, pagination ─────────
    public function index(Request $request)
    {
        $query = Property::with(['coverImage', 'owner:id,name,avatar', 'reviews'])
            ->where('status', 'active');

        if ($request->filled('type'))         $query->where('type', $request->type);
        if ($request->filled('listing_type')) $query->where('listing_type', $request->listing_type);
        if ($request->filled('city'))         $query->where('city', 'like', "%{$request->city}%");
        if ($request->filled('min_price'))    $query->where('price', '>=', $request->min_price);
        if ($request->filled('max_price'))    $query->where('price', '<=', $request->max_price);
        if ($request->filled('rooms'))        $query->where('rooms', '>=', $request->rooms);
        if ($request->boolean('furnished'))   $query->where('is_furnished', true);

        if ($request->filled('amenities')) {
            foreach (explode(',', $request->amenities) as $amenity) {
                $query->whereJsonContains('amenities', trim($amenity));
            }
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title',        'like', "%{$term}%")
                  ->orWhere('description','like', "%{$term}%")
                  ->orWhere('city',        'like', "%{$term}%")
                  ->orWhere('address',     'like', "%{$term}%");
            });
        }

        match ($request->sort) {
            'oldest'     => $query->oldest(),
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating'     => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
            default      => $query->latest(),
        };

        return response()->json($query->paginate(min((int)($request->per_page ?? 12), 24)));
    }

    // ─── Annonces en vedette ──────────────────────────────────
    public function featured()
    {
        $properties = Property::with(['coverImage', 'owner:id,name', 'reviews'])
            ->where('status', 'active')
            ->latest()
            ->take(6)
            ->get();

        return response()->json($properties);
    }

    // ─── Détail d'une annonce ─────────────────────────────────
    public function show($id)
    {
        $property = Property::with([
            'images',
            'owner:id,name,avatar,phone',
            'reviews.user:id,name,avatar',
        ])->findOrFail($id);

        return response()->json([
            'property'       => $property,
            'average_rating' => round($property->reviews->avg('rating') ?? 0, 1),
            'total_reviews'  => $property->reviews->count(),
        ]);
    }

    // ─── Créer une annonce (owner) ────────────────────────────
    public function store(Request $request)
    {
        // ✅ Vérification du quota d'abonnement
        $check = $this->checkSubscriptionQuota(auth()->id());

        if (!$check['allowed']) {
            return response()->json([
                'message'       => $check['message'],
                'reason'        => $check['reason'],
                'plan'          => $check['plan'],
                'quota_reached' => true,
            ], 403);
        }

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'type'         => 'required|in:room,apartment,house,studio',
            'listing_type' => 'required|in:rent,sale',
            'price'        => 'required|numeric|min:0',
            'city'         => 'required|string',
            'address'      => 'required|string',
            'rooms'        => 'integer|min:1',
            'bathrooms'    => 'integer|min:1',
            'area'         => 'nullable|numeric',
            'amenities'    => 'nullable|array',
            'amenities.*'  => 'string',
            'is_furnished' => 'nullable|boolean',
            'images'       => 'nullable|array',
            'images.*'     => 'image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        // Nettoyer les amenities vides
        if (isset($validated['amenities'])) {
            $validated['amenities'] = array_values(
                array_filter($validated['amenities'], fn($a) => !empty($a))
            );
        }

        $property = Property::create([
            ...$validated,
            'user_id' => auth()->id(),
            'status'  => 'pending',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('properties', 'public');
                $property->images()->create([
                    'path'     => $path,
                    'is_cover' => $i === 0,
                ]);
            }
        }

        // ✅ Ajouter les infos de quota dans la réponse
        return response()->json([
            'message'          => 'Annonce créée, en attente de validation.',
            'property'         => $property->load('images'),
            'subscription_info'=> [
                'plan'           => $check['plan'],
                'used'           => ($check['used'] ?? 0) + 1,
                'max_properties' => $check['max_properties'],
                'remaining'      => max(0, ($check['remaining'] ?? 0) - 1),
                'warning'        => $check['warning'] ?? false,
                'warning_message'=> $check['warning_message'] ?? null,
            ],
        ], 201);
    }

    // ─── Modifier une annonce ─────────────────────────────────
    public function update(Request $request, $id)
    {
        $property = Property::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'title'        => 'string|max:255',
            'description'  => 'string',
            'price'        => 'numeric|min:0',
            'city'         => 'string',
            'address'      => 'string',
            'rooms'        => 'integer|min:1',
            'bathrooms'    => 'integer|min:1',
            'area'         => 'nullable|numeric',
            'amenities'    => 'nullable|array',
            'is_furnished' => 'boolean',
        ]);

        $property->update($validated);

        return response()->json([
            'message'  => 'Annonce mise à jour.',
            'property' => $property->load('images'),
        ]);
    }

    // ─── Supprimer une annonce ────────────────────────────────
    public function destroy($id)
    {
        $property = Property::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $property->delete();

        return response()->json(['message' => 'Annonce supprimée.']);
    }

    // ─── Mes annonces (owner) ─────────────────────────────────
    public function myProperties()
    {
        $properties = Property::with(['images', 'bookings', 'reviews'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($properties);
    }

    // ─── Infos quota abonnement (owner) ───────────────────────
    public function subscriptionQuota()
    {
        $check = $this->checkSubscriptionQuota(auth()->id());
        return response()->json($check);
    }
}