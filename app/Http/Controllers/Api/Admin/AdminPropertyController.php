<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPropertyController extends Controller
{
    // ─── Liste toutes les annonces avec filtres ───────────────
    public function index(Request $request)
    {
        $query = Property::with(['owner:id,name,avatar', 'coverImage'])
            ->latest();

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title',   'like', "%{$term}%")
                  ->orWhere('city',  'like', "%{$term}%")
                  ->orWhereHas('owner', fn($q2) => $q2->where('name', 'like', "%{$term}%"));
            });
        }

        return response()->json($query->paginate($request->per_page ?? 10));
    }

    // ─── Approuver une annonce ────────────────────────────────
    public function approve($id)
    {
        $property = Property::findOrFail($id);
        $property->update(['status' => 'active']);

        // TODO: notifier le propriétaire par email
        // Mail::to($property->owner->email)->send(new PropertyApproved($property));

        return response()->json([
            'message'  => 'Annonce approuvée et publiée.',
            'property' => $property,
        ]);
    }

    // ─── Rejeter une annonce ──────────────────────────────────
    public function reject($id)
    {
        $property = Property::findOrFail($id);
        $property->update(['status' => 'pending']);

        return response()->json([
            'message'  => 'Annonce rejetée.',
            'property' => $property,
        ]);
    }

    // ─── Supprimer une annonce ────────────────────────────────
    public function destroy($id)
    {
        $property = Property::findOrFail($id);

        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $property->delete();

        return response()->json(['message' => 'Annonce supprimée.']);
    }

    // ─── Stats globales ───────────────────────────────────────
    public function stats()
    {
        return response()->json([
            'total_properties' => Property::count(),
            'pending'          => Property::where('status', 'pending')->count(),
            'active'           => Property::where('status', 'active')->count(),
            'rented'           => Property::where('status', 'rented')->count(),
            'total_users'      => User::count(),
            'owners'           => User::where('role', 'owner')->count(),
            'tenants'          => User::where('role', 'tenant')->count(),
        ]);
    }
}