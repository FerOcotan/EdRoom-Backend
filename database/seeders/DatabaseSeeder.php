<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\EstadoSeeder;
use Database\Seeders\RolSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed estados y roles (idempotentes) primero
        $this->call([
            EstadoSeeder::class,
            RolSeeder::class,
        ]);

        // Crear usuario de prueba asegurando idrol e idestado válidos
        $idRolAdmin = \Illuminate\Support\Facades\DB::table('rol')->where('nombre', 'Administrador')->value('idrol') ?: 1;
        $idEstadoActivo = \Illuminate\Support\Facades\DB::table('estado')->where('estado', 'Activo')->value('idestado') ?: 8;

        // Crear o actualizar usuario de prueba de forma idempotente
        \App\Models\User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'remember_token' => substr(bin2hex(random_bytes(5)), 0, 10),
                'idrol' => $idRolAdmin,
                'idestado' => $idEstadoActivo,
            ]
        );

        // Luego el resto de usuarios (docentes y estudiantes)
        $this->call([
            UsersSeeder::class,
        ]);

        // Crear cursos, clases y solicitudes de estudiantes
        $this->call([
            CursoSeeder::class,
            ClaseSeeder::class,
            SoliEstudianteSeeder::class,
        ]);
    }
}
