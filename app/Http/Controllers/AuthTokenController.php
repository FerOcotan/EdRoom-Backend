<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SessionToken;

class AuthTokenController extends Controller
{
    // Endpoint para crear un token por email/password
    public function create(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = bin2hex(random_bytes(32));
        $expires = now()->addDays(7);

        $record = SessionToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expires,
        ]);

        return response()->json([
            'token' => $token,
            'expires_at' => $expires->toDateTimeString(),
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    // Optional: endpoint to revoke current token
    public function revoke(Request $request)
    {
        $authHeader = $request->header('Authorization') ?: $request->header('X-Beeart-Token');
        $token = null;
        if ($authHeader) {
            $token = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : $authHeader;
        }

        if ($token) {
            SessionToken::where('token', $token)->delete();
        }

        return response()->json(['message' => 'Sesión finalizada']);
    }

    // Endpoint para registrar un nuevo usuario y devolver token inicial
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Rely on the User model cast 'password' => 'hashed' to hash automatically
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        // Crear token inicial igual que en login
        $token = bin2hex(random_bytes(32));
        $expires = now()->addDays(7);

        SessionToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expires,
        ]);

        return response()->json([
            'token' => $token,
            'expires_at' => $expires->toDateTimeString(),
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ], 201);
    }
}
