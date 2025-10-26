<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SessionToken;

class CheckBeeartToken
{
    /**
     * Handle an incoming request.
     * Require a valid token for protected API routes.
     */
    public function handle(Request $request, Closure $next)
    {
        // Public endpoints that don't require token
        // Register and token (login) must be public so users can create account / login without token
        $public = [
            '/api/ping',
            '/api/token',
            '/api/register',
        ];

        $path = $request->getPathInfo();
        if (in_array($path, $public, true)) {
            return $next($request);
        }

        // Only protect API routes
        if (!str_starts_with($path, '/api')) {
            return $next($request);
        }

        $authHeader = $request->header('Authorization') ?: $request->header('X-Beeart-Token');
        $token = null;
        if ($authHeader) {
            if (str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } else {
                $token = $authHeader;
            }
        }

        if (empty($token)) {
            return response()->json(['message' => 'Token de sesión requerido'], 401);
        }

        $record = SessionToken::where('token', $token)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->first();

        if (!$record) {
            return response()->json(['message' => 'Token inválido o expirado'], 401);
        }

        // Optionally set the current user on the request
        if ($record->user_id) {
            // attach user id to request so controllers can use it
            $request->attributes->set('beeart_user_id', $record->user_id);
        }

        return $next($request);
    }
}
