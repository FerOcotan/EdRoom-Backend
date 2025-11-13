<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [1, 'Administrador', 'Es el usuario con los privilegios más altos dentro del sistema. Puede gestionar usuarios, cursos, estados y realizar tareas administrativas globales.'],
            [2, 'Docente', 'Imparte cursos, gestiona el contenido y las clases, y supervisa el progreso de los estudiantes. Puede aprobar o denegar solicitudes relacionadas con sus cursos.'],
            [3, 'Estudiante', 'Solicita acceso a cursos, asiste a clases y videollamadas, y participa en las actividades y evaluaciones del curso.'],
        ];

        foreach ($items as [$id, $nombre, $descripcion]) {
            DB::table('rol')->updateOrInsert(
                ['idrol' => $id],
                ['nombre' => $nombre, 'descripcion' => $descripcion]
            );
        }
    }
}
