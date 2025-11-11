<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [1, 'Agendada', 'Clase agendada para una fecha.'],
            [2, 'En curso', 'La clase está en curso.'],
            [3, 'Cancelada', 'La clase ha sido cancelada.'],
            [4, 'Finalizada', 'La clase ha terminado.'],
            [5, 'En Espera', 'Solicitud de estudiante para acceder a un curso en espera.'],
            [6, 'Aprobado', 'Solicitud de estudiante para acceder a un curso ha sido aprobada.'],
            [7, 'Denegado', 'Solicitud de estudiante para acceder a un curso ha sido denegada.'],
            [8, 'Activo', 'El usuario o curso se encuentre activo.'],
            [9, 'Inactivo', 'El usuario o curso se encuentra inactivo.'],
        ];

        foreach ($items as [$id, $estado, $descripcion]) {
            DB::table('estado')->updateOrInsert(
                ['idestado' => $id],
                ['estado' => $estado, 'descripcion' => $descripcion]
            );
        }
    }
}
