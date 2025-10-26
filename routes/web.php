<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\AuthTokenController;
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
