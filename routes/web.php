<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClassJoinController;
use Illuminate\Http\Request;
use App\Models\SessionToken;
// Middlewares
use App\Http\Middleware\CheckBeeartToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::get('/', function () {
    return view('welcome');
});


// Endpoint para crear token (login con email/password)
Route::post('/api/token', [AuthTokenController::class, 'create'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Endpoint para revocar token
Route::post('/api/token/revoke', [AuthTokenController::class, 'revoke']);

// Endpoint para registrar usuario y obtener token inicial
Route::post('/api/register', [AuthTokenController::class, 'register'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);



// Endpoint para actualizar el perfil del usuario autenticado
Route::patch('/api/me', [UserController::class, 'update'])
    ->middleware(\App\Http\Middleware\CheckBeeartToken::class)
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Endpoint para obtener el perfil del usuario autenticado (delegado a UserController::me)
Route::get('/api/me', [UserController::class, 'me'])
    ->middleware(\App\Http\Middleware\CheckBeeartToken::class);
/** ---------- NUEVO: SOLICITUD DE INGRESO A CLASE (MAILTRAP) ---------- */
Route::post('/api/classes/{id}/join-request', [ClassJoinController::class, 'store'])
    ->withoutMiddleware(VerifyCsrfToken::class);
