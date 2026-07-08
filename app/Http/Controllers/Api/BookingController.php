<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Créer une réservation (locataire)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'start_date'  => 'required|date|after:today',
            'end_date'    => 'nullable|date|after:start_date',
            'message'     => 'nullable|string|max:500',
        ]);

        // Vérifier qu'il n'y a pas déjà une réservation pending
        $exists = Booking::where('property_id', $validated['property_id'])
            ->where('tenant_id', auth()->id())
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Vous avez déjà une réservation active pour ce bien.',
            ], 422);
        }

        $booking = Booking::create([
            ...$validated,
            'tenant_id' => auth()->id(),
            'status'    => 'pending',
        ]);

        return response()->json([
            'message' => 'Demande de réservation envoyée.',
            'booking' => $booking->load(['property', 'tenant']),
        ], 201);
    }

    // Mes réservations (locataire)
    public function myBookings()
    {
        $bookings = Booking::with(['property.coverImage', 'property.owner:id,name'])
            ->where('tenant_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    // Annuler une réservation (locataire)
    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('tenant_id', auth()->id())
            ->whereIn('status', ['pending'])
            ->firstOrFail();

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Réservation annulée.']);
    }

    // Réservations reçues (propriétaire)
    public function received()
    {
        $bookings = Booking::with(['property', 'tenant:id,name,email,phone'])
            ->whereHas('property', fn($q) => $q->where('user_id', auth()->id()))
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    // Répondre à une réservation (propriétaire)
    public function respond(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $booking = Booking::whereHas('property', fn($q) => $q->where('user_id', auth()->id()))
            ->findOrFail($id);

        $booking->update(['status' => $request->status]);

        return response()->json([
            'message' => $request->status === 'approved' ? 'Réservation approuvée.' : 'Réservation refusée.',
            'booking' => $booking,
        ]);
    }
}