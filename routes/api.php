<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\SoliEstudianteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\DailyController;

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

Route::middleware([\App\Http\Middleware\CheckBeeartToken::class])->group(function() {
    // Cursos
    Route::get('cursos', [CursoController::class, 'index']);
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
    Route::post('roles', [RolController::class, 'store']);
    Route::patch('roles/{id}', [RolController::class, 'update']);
    Route::delete('roles/{id}', [RolController::class, 'destroy']);

    // Estados
    Route::get('estados', [EstadoController::class, 'index']);
    Route::get('estados/{id}', [EstadoController::class, 'show']);
    Route::post('estados', [EstadoController::class, 'store']);
    Route::patch('estados/{id}', [EstadoController::class, 'update']);
    Route::delete('estados/{id}', [EstadoController::class, 'destroy']);

    // Daily (crear salas)
    Route::post('daily/rooms', [DailyController::class, 'store']);
    // Daily: crear meeting token para unirse como owner/participant
    Route::post('daily/tokens', [DailyController::class, 'createToken']);
});
