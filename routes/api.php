<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\SoliEstudianteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\DailyController;
use App\Http\Controllers\ClassJoinController; // 👈 IMPORTANTE

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí definimos las rutas para los recursos creados en las migrations.
| Todas las rutas están protegidas por el middleware de token personalizado
| `CheckBeeartToken` para que solo peticiones autenticadas por el token
| puedan acceder.
|
*/

// Public endpoints (no token required)
Route::get('users/check-email', [AuthTokenController::class, 'checkEmail']);

Route::middleware([\App\Http\Middleware\CheckBeeartToken::class])->group(function() {
    // Cursos
    Route::get('cursos', [CursoController::class, 'index']);
    // Obtener cursos del usuario autenticado (usa token Beeart)
    Route::get('cursos/mine', [CursoController::class, 'mine']);
    Route::get('cursos/{id}', [CursoController::class, 'show']);
    Route::post('cursos', [CursoController::class, 'store']);
    Route::patch('cursos/{id}', [CursoController::class, 'update']);
    Route::delete('cursos/{id}', [CursoController::class, 'destroy']);

    // Clases
    Route::get('clases/next', [ClaseController::class, 'next']);
    Route::get('clases', [ClaseController::class, 'index']);
    Route::get('clases/{id}', [ClaseController::class, 'show']);
    Route::post('clases', [ClaseController::class, 'store']);
    Route::patch('clases/{id}', [ClaseController::class, 'update']);
    Route::delete('clases/{id}', [ClaseController::class, 'destroy']);

    // Solicitudes de estudiante
    Route::get('soliestudiantes', [SoliEstudianteController::class, 'index']);
    // Obtener solicitudes del estudiante autenticado (id del token)
    Route::get('soliestudiantes/mine', [SoliEstudianteController::class, 'mine']);
    Route::get('soliestudiantes/{id}', [SoliEstudianteController::class, 'show']);
    Route::post('soliestudiantes', [SoliEstudianteController::class, 'store']);
    Route::patch('soliestudiantes/{id}', [SoliEstudianteController::class, 'update']);
    Route::delete('soliestudiantes/{id}', [SoliEstudianteController::class, 'destroy']);

    // Inscritos (estudiantes aprobados) por curso
    Route::get('cursos/{id}/inscritos', [SoliEstudianteController::class, 'inscritosByCurso']);
    // Cursos aprobados por estudiante (solicitudes aprobadas)
    Route::get('soliestudiantes/estudiante/{id}', [SoliEstudianteController::class, 'cursosByEstudiante']);

    // Roles
    Route::get('roles', [RolController::class, 'index']);
    Route::get('roles/{id}', [RolController::class, 'show']);
    Route::post('roles', [RolController::class, 'store'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::patch('roles/{id}', [RolController::class, 'update'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::delete('roles/{id}', [RolController::class, 'destroy'])->middleware(\App\Http\Middleware\RequireAdmin::class);

    // Estados
    Route::get('estados', [EstadoController::class, 'index']);
    Route::get('estados/{id}', [EstadoController::class, 'show']);
    Route::post('estados', [EstadoController::class, 'store'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::patch('estados/{id}', [EstadoController::class, 'update'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::delete('estados/{id}', [EstadoController::class, 'destroy'])->middleware(\App\Http\Middleware\RequireAdmin::class);

    // Daily (crear salas)
    Route::post('daily/rooms', [DailyController::class, 'store']);
    // Daily: crear meeting token para unirse como owner/participant
    Route::post('daily/tokens', [DailyController::class, 'createToken']);
    
        // Usuarios (admin)
        Route::get('users', [\App\Http\Controllers\UserController::class, 'index']);
        Route::get('users/{id}', [\App\Http\Controllers\UserController::class, 'show']);
        Route::patch('users/{id}', [\App\Http\Controllers\UserController::class, 'updateUser']);
        Route::delete('users/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
        //Correo
    Route::post('/classes/{classId}/join-request', [ClassJoinController::class, 'store']);

});
