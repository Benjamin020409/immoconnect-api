<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvitationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationCodeController extends Controller
{
    public function index()
    {
        $codes = InvitationCode::with('usedBy:id,name,email')
            ->latest()
            ->get();

        return response()->json($codes);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'quantity'   => 'integer|min:1|max:20',
            'email'      => 'nullable|email',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $quantity = $request->quantity ?? 1;
        $codes    = [];

        for ($i = 0; $i < $quantity; $i++) {
            $codes[] = InvitationCode::create([
                'code'       => strtoupper(Str::random(8)),
                'email'      => $request->email,
                'expires_at' => $request->expires_at,
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => "$quantity code(s) généré(s)",
            'codes'   => $codes,
        ], 201);
    }

    public function destroy($id)
    {
        $code = InvitationCode::findOrFail($id);

        if ($code->is_used) {
            return response()->json([
                'message' => 'Impossible de supprimer un code déjà utilisé.',
            ], 422);
        }

        $code->delete();

        return response()->json(['message' => 'Code supprimé.']);
    }
}