<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use App\Models\SessionToken;

Route::get('/', function () {
    return view('welcome');
});

// Ruta de prueba accesible desde el frontend (usa /api/ping)
Route::get('/api/ping', function () {
    return response()->json(['pong' => true]);
});

// Endpoint para crear token (login con email/password)
Route::post('/api/token', [AuthTokenController::class, 'create'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Endpoint para revocar token
Route::post('/api/token/revoke', [AuthTokenController::class, 'revoke']);

// Endpoint para registrar usuario y obtener token inicial
Route::post('/api/register', [AuthTokenController::class, 'register'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Note: removed dev-only register/test endpoint. Use POST /api/register from the SPA or API client.

// Ruta para devolver la lista de usuarios en JSON — protegida mediante middleware de token
Route::get('/api/users', function () {
    return User::all();
})->middleware(\App\Http\Middleware\CheckBeeartToken::class);

// Endpoint para actualizar el perfil del usuario autenticado
Route::patch('/api/me', [UserController::class, 'update'])
    ->middleware(\App\Http\Middleware\CheckBeeartToken::class)
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Endpoint para obtener el perfil del usuario autenticado
Route::get('/api/me', function (Request $request) {
    $userId = $request->attributes->get('beeart_user_id');
    if (!$userId) {
        return response()->json(['message' => 'No autenticado'], 401);
    }
    $user = User::find($userId);
    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado'], 404);
    }
    return response()->json(['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
})->middleware(\App\Http\Middleware\CheckBeeartToken::class);
