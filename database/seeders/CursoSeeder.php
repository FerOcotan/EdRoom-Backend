<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CursoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $idEstadoActivo = DB::table('estado')->where('estado', 'Activo')->value('idestado') ?: 8;

        // Obtener docentes existentes
        $docentes = DB::table('users')->where('idrol', DB::table('rol')->where('nombre', 'Docente')->value('idrol') ?: 2)->pluck('id')->toArray();

        if (empty($docentes)) {
            // No hay docentes: crear un curso genérico sin docente
            DB::table('curso')->updateOrInsert(
                ['nombre' => 'Curso genérico'],
                [
                    'idusuario' => null,
                    'nombre' => 'Curso genérico',
                    'descripcion' => 'Curso creado automáticamente porque no hay docentes en la base.',
                    'maximoest' => 20,
                    'estuinscritos' => 0,
                    'idestado' => $idEstadoActivo,
                ]
            );
            return;
        }

        $now = Carbon::now()->toDateTimeString();

        foreach ($docentes as $docId) {
            // Crear 1 o 2 cursos por docente
            for ($i = 1; $i <= 2; $i++) {
                $name = "Curso del docente {$docId} - {$i}";
                DB::table('curso')->updateOrInsert(
                    ['idusuario' => $docId, 'nombre' => $name],
                    [
                        'idusuario' => $docId,
                        'nombre' => $name,
                        'descripcion' => "Curso gestionado por el docente con id {$docId}.",
                        'maximoest' => rand(10, 30),
                        'estuinscritos' => 0,
                        'idestado' => $idEstadoActivo,
                    ]
                );
            }
        }
    }
}
