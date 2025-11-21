<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class RequireAdmin
{
    /**
     * Handle an incoming request and ensure the user is admin (idrol === 1).
     */
    public function handle(Request $request, Closure $next)
    {
        $beeartUserId = $request->attributes->get('beeart_user_id');
        if (!$beeartUserId) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $me = User::find($beeartUserId);
        $role = $me ? (int)($me->idrol ?? 0) : null;

        if ($role !== 1) {
            return response()->json(['message' => 'No autorizado (se requiere admin)'], 403);
        }

        return $next($request);
    }
}
