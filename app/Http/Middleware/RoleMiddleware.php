<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!in_array($request->user()?->role, $roles)) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        return $next($request);
    }
}