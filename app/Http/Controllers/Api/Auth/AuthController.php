<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\InvitationCode; 

class AuthController extends Controller
{
    // ─── Register ───────────────────────────────────────────
   

public function register(Request $request)
{
    $validated = $request->validate([
        'name'            => 'required|string|max:255',
        'email'           => 'required|email|unique:users,email',
        'password'        => ['required', 'confirmed', Password::min(8)],
        'phone'           => 'nullable|string|max:20',
        'role'            => 'in:tenant,owner',
        'invitation_code' => 'required_if:role,owner|nullable|string',
    ]);

    // ✅ Vérification du code si rôle owner
    if (($validated['role'] ?? 'tenant') === 'owner') {
        $code = InvitationCode::where('code', strtoupper($validated['invitation_code']))
            ->first();

        if (!$code || !$code->isValid()) {
            return response()->json([
                'message' => 'Code d\'invitation invalide ou expiré.',
                'errors'  => ['invitation_code' => ['Code invalide ou expiré']]
            ], 422);
        }

        // Vérifier si le code est limité à un email
        if ($code->email && $code->email !== $validated['email']) {
            return response()->json([
                'message' => 'Ce code n\'est pas associé à votre email.',
                'errors'  => ['invitation_code' => ['Code non valide pour cet email']]
            ], 422);
        }
    }

    // Créer l'utilisateur
    $user = User::create([
        'name'     => $validated['name'],
        'email'    => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role'     => $validated['role'] ?? 'tenant',
        'phone'    => $validated['phone'] ?? null,
    ]);

    // ✅ Marquer le code comme utilisé
    if (isset($code)) {
        $code->update([
            'is_used' => true,
            'used_by' => $user->id,
        ]);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Inscription réussie',
        'user'    => $this->formatUser($user),
        'token'   => $token,
    ], 201);
}

    // ─── Login ──────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user'    => $this->formatUser($user),
            'token'   => $token,
        ]);
    }

    // ─── Logout ─────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    // ─── Profil connecté ────────────────────────────────────
    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    // ─── Helper ─────────────────────────────────────────────
    private function formatUser(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'phone'  => $user->phone,
            'avatar' => $user->avatar
                ? asset('storage/' . $user->avatar)
                : null,
        ];
    }
}