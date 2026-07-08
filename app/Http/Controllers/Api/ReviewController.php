<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // ─── Laisser un avis ─────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'booking_id'  => 'required|exists:bookings,id',
            'rating'      => 'required|integer|min:1|max:5',
            'comment'     => 'nullable|string|max:1000',
        ]);

        // Vérifier que la réservation appartient au locataire connecté
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('tenant_id', auth()->id())
            ->where('property_id', $validated['property_id'])
            ->where('status', 'approved')
            ->firstOrFail();

        // Vérifier qu'il n'a pas déjà laissé un avis pour cette réservation
        $exists = Review::where('property_id', $validated['property_id'])
            ->where('user_id', auth()->id())
            ->where('booking_id', $validated['booking_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Vous avez déjà laissé un avis pour cette réservation.',
            ], 422);
        }

        $review = Review::create([
            'property_id' => $validated['property_id'],
            'booking_id'  => $validated['booking_id'],
            'user_id'     => auth()->id(),
            'rating'      => $validated['rating'],
            'comment'     => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Avis publié avec succès.',
            'review'  => $review->load('user:id,name,avatar'),
        ], 201);
    }

    // ─── Avis d'une propriété ─────────────────────────────────
    public function index($propertyId)
    {
        $reviews = Review::with('user:id,name,avatar')
            ->where('property_id', $propertyId)
            ->latest()
            ->get();

        $average = round($reviews->avg('rating') ?? 0, 1);

        return response()->json([
            'reviews' => $reviews,
            'average' => $average,
            'total'   => $reviews->count(),
        ]);
    }

    // ─── Vérifier si l'utilisateur peut laisser un avis ──────
    public function canReview($propertyId)
    {
        $userId = auth()->id();

        // A-t-il une réservation approuvée ?
        $booking = Booking::where('tenant_id', $userId)
            ->where('property_id', $propertyId)
            ->where('status', 'approved')
            ->first();

        if (!$booking) {
            return response()->json([
                'can_review' => false,
                'reason'     => 'Aucune réservation approuvée pour ce bien.',
            ]);
        }

        // A-t-il déjà laissé un avis ?
        $hasReview = Review::where('user_id', $userId)
            ->where('booking_id', $booking->id)
            ->exists();

        return response()->json([
            'can_review' => !$hasReview,
            'booking_id' => $booking->id,
            'reason'     => $hasReview ? 'Vous avez déjà laissé un avis.' : null,
        ]);
    }
}