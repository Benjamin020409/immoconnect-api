<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // ─── Mes favoris ─────────────────────────────────────────
    public function index()
    {
        $favorites = Favorite::with([
                'property.coverImage',
                'property.owner:id,name',
            ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(fn($f) => $f->property)
            ->filter();

        return response()->json($favorites->values());
    }

    // ─── Ajouter aux favoris ──────────────────────────────────
    public function store($propertyId)
    {
        $property = Property::findOrFail($propertyId);

        Favorite::firstOrCreate([
            'user_id'     => auth()->id(),
            'property_id' => $propertyId,
        ]);

        return response()->json([
            'message'      => 'Ajouté aux favoris.',
            'is_favorited' => true,
        ]);
    }

    // ─── Retirer des favoris ──────────────────────────────────
    public function destroy($propertyId)
    {
        Favorite::where('user_id', auth()->id())
            ->where('property_id', $propertyId)
            ->delete();

        return response()->json([
            'message'      => 'Retiré des favoris.',
            'is_favorited' => false,
        ]);
    }

    // ─── Toggle favori ────────────────────────────────────────
    public function toggle($propertyId)
    {
        Property::findOrFail($propertyId);

        $existing = Favorite::where('user_id', auth()->id())
            ->where('property_id', $propertyId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['is_favorited' => false, 'message' => 'Retiré des favoris.']);
        }

        Favorite::create([
            'user_id'     => auth()->id(),
            'property_id' => $propertyId,
        ]);

        return response()->json(['is_favorited' => true, 'message' => 'Ajouté aux favoris.']);
    }
}