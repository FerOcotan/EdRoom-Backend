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
            [1, 'Administrador', 'Es el usuario con los privilegios más altos dentro del sistema.'],
            [2, 'Docente', 'Imparte cursos,'],
            [3, 'Estudiante', 'Solicita acceso a cursos y atiende videollamadas'],
        ];

        foreach ($items as [$id, $nombre, $descripcion]) {
            DB::table('rol')->updateOrInsert(
                ['idrol' => $id],
                ['nombre' => $nombre, 'descripcion' => $descripcion]
            );
        }
    }
}
