<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarnos de que roles y estados ya están seeded
        $idRolDocente = DB::table('rol')->where('nombre', 'Docente')->value('idrol') ?: 2;
        $idRolEstudiante = DB::table('rol')->where('nombre', 'Estudiante')->value('idrol') ?: 3;
        $idEstadoActivo = DB::table('estado')->where('estado', 'Activo')->value('idestado') ?: 8;

        $now = Carbon::now()->toDateTimeString();

        // Maestros (2)
        $teachers = [
            ['name' => 'Maestro Uno', 'email' => 'teacher1@example.com', 'password' => 'TeacherPass1!'],
            ['name' => 'Maestro Dos', 'email' => 'teacher2@example.com', 'password' => 'TeacherPass2!'],
        ];

        foreach ($teachers as $t) {
            DB::table('users')->updateOrInsert(
                ['email' => $t['email']],
                [
                    'name' => $t['name'],
                    'email' => $t['email'],
                    'email_verified_at' => $now,
                    'password' => Hash::make($t['password']),
                    'remember_token' => substr(bin2hex(random_bytes(5)), 0, 10),
                    'idrol' => $idRolDocente,
                    'idestado' => $idEstadoActivo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Estudiantes (10)
        for ($i = 1; $i <= 10; $i++) {
            $email = "student{$i}@example.com";
            $name = "Estudiante {$i}";
            $password = "StudentPass{$i}!";

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => $now,
                    'password' => Hash::make($password),
                    'remember_token' => substr(bin2hex(random_bytes(5)), 0, 10),
                    'idrol' => $idRolEstudiante,
                    'idestado' => $idEstadoActivo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
