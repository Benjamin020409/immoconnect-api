<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // ─── Liste tous les utilisateurs ─────────────────────────
    public function index(Request $request)
    {
        $query = User::latest();

        if ($request->filled('role'))   $query->where('role', $request->role);
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name',  'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return response()->json($query->paginate($request->per_page ?? 10));
    }

    // ─── Supprimer un utilisateur ─────────────────────────────
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Empêcher la suppression d'un admin
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Impossible de supprimer un administrateur.',
            ], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}