<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;
use App\Models\SessionToken;
use App\Models\rol;
use App\Models\estado;

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
            'token_hash' => $token,
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
        // Custom validation so we can add targeted alerts/messages
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            // basic rule; we'll add extra strength checks below
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.email' => 'El correo debe ser una dirección válida (ej. usuario@dominio.com).',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ]);

        // Additional custom checks for alerts
        $validator->after(function ($v) use ($request) {
            // Name should not be only numbers
            if (isset($request->name) && preg_match('/^\d+$/', $request->name)) {
                $v->errors()->add('name', 'El nombre no puede contener sólo números.');
            }

            // Email must contain @ (additional friendly check)
            if (isset($request->email) && !str_contains($request->email, '@')) {
                $v->errors()->add('email', 'El correo debe contener el carácter @.');
            }

            // Password strength checks
            if (isset($request->password)) {
                $pwd = $request->password;
                // Enforce minimum recommended length 8
                if (strlen($pwd) < 8) {
                    $v->errors()->add('password', 'Contraseña muy débil: mínimo 8 caracteres.');
                }
                // Avoid passwords that are only numbers
                if (ctype_digit($pwd)) {
                    $v->errors()->add('password', 'Contraseña muy débil: no use sólo números.');
                }
                // Check against a short list of common weak passwords
                $common = ['12345678', 'password', 'qwerty', '123456789', '123456'];
                if (in_array($pwd, $common, true)) {
                    $v->errors()->add('password', 'Contraseña demasiado común. Elija una contraseña más segura.');
                }
                // Optionally, enforce letters + numbers
                if (!preg_match('/[A-Za-z]/', $pwd) || !preg_match('/[0-9]/', $pwd)) {
                    $v->errors()->add('password', 'Contraseña débil: combine letras y números para mayor seguridad.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Determinar rol y estado por defecto
        $rolRecord = rol::where('nombre', 'Estudiante')->first();
        if (!$rolRecord) {
            // Si no existe el rol 'Estudiante', crearlo mínimamente
            $rolRecord = rol::create(['nombre' => 'Estudiante', 'descripcion' => '']);
        }
        $idrol = $rolRecord ? $rolRecord->idrol : 1;

        $estadoRecord = estado::where('estado', 'Activo')->first();
        if (!$estadoRecord) {
            // Si no existe el estado 'Activo', crear uno mínimo
            $estadoRecord = estado::create(['estado' => 'Activo']);
        }
        $idestado = $estadoRecord->idestado;

        // Rely on the User model cast 'password' => 'hashed' to hash automatically
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'idrol' => $idrol,
            'idestado' => $idestado,
        ]);

        // Crear token inicial igual que en login
        $token = bin2hex(random_bytes(32));
        $expires = now()->addDays(7);

        SessionToken::create([
            'user_id' => $user->id,
            'token_hash' => $token,
            'expires_at' => $expires,
        ]);

        return response()->json([
            'token' => $token,
            'expires_at' => $expires->toDateTimeString(),
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ], 201);
    }
}
