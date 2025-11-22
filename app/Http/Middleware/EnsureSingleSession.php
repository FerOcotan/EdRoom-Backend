<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SessionToken;

/**
 * EnsureSingleSession middleware
 * - Para sesiones basadas en cookies: comprueba que la fila en `sessions`
 *   para el session id existe y pertenece al mismo user_id autenticado.
 * - Para autenticación por token: comprueba que el token enviado exista
 *   en `session_tokens` y que sea el único activo para ese user.
 */
class EnsureSingleSession
{
    public function handle(Request $request, Closure $next)
    {
        // 1) Si hay un token Bearer o X-Beeart-Token: validar que exista en session_tokens
        $authHeader = $request->header('Authorization') ?: $request->header('X-Beeart-Token');
        if ($authHeader) {
            $token = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : $authHeader;
            if ($token) {
                $record = SessionToken::where('token_hash', $token)
                    ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
                    ->first();

                if (! $record) {
                    Log::debug('[EnsureSingleSession] token inválido o expirado o reemplazado por otro dispositivo');
                    return response()->json(['message' => 'Token inválido o expirado'], 401);
                }

                // Opcional: si hay más de un token para este user, rechazamos
                $count = SessionToken::where('user_id', $record->user_id)->count();
                if ($count > 1) {
                    // Eliminamos tokens antiguos dejando solo el actual (defensive)
                    SessionToken::where('user_id', $record->user_id)
                        ->where('token_hash', '!=', $token)
                        ->delete();
                }

                // Attach user id for controllers
                $request->attributes->set('beeart_user_id', $record->user_id);
                return $next($request);
            }
        }

        // 2) Si no hay token, comprobar sesión basada en cookie
        if (Auth::check()) {
            $userId = Auth::id();
            try {
                $sessionId = session()->getId();
            } catch (\Throwable $e) {
                $sessionId = null;
            }

                if ($sessionId) {
                $sessionRow = DB::table('sessions')->where('id', $sessionId)->first();
                if (! $sessionRow) {
                    // sesión borrada -> logout
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Sesión activa en otro dispositivo'], 401);
                    }
                    return redirect('/login')->withErrors(['message' => 'Sesión activa en otro dispositivo']);
                }

                // Si existe la fila, comprobar que user_id coincide
                if (isset($sessionRow->user_id) && $sessionRow->user_id != $userId) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Sesión activa en otro dispositivo'], 401);
                    }
                    return redirect('/login')->withErrors(['message' => 'Sesión activa en otro dispositivo']);
                }
            }
        }

        return $next($request);
    }
}
