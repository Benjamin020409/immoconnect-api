<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Mettre à jour le profil
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user'    => $request->user(),
        ]);
    }



    public function show($id)
{
    $user = User::select('id', 'name', 'avatar', 'phone', 'role')
        ->findOrFail($id);

    return response()->json($user);
}
}