<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SoliEstudianteSeeder extends Seeder
{
    public function run(): void
    {
        $idEstadoEnEspera = DB::table('estado')->where('estado', 'En Espera')->value('idestado') ?: 5;
        $idEstadoAprobado = DB::table('estado')->where('estado', 'Aprobado')->value('idestado') ?: 6;

        $cursos = DB::table('curso')->pluck('idcurso')->toArray();
        $estudiantes = DB::table('users')->where('idrol', DB::table('rol')->where('nombre','Estudiante')->value('idrol') ?: 3)->pluck('id')->toArray();

        if (empty($cursos) || empty($estudiantes)) return;

        $now = Carbon::now()->toDateTimeString();

        foreach ($estudiantes as $estId) {
            // Cada estudiante solicita 1-3 cursos aleatorios
            $num = rand(1, min(3, count($cursos)));
            $selected = (array) array_rand(array_flip($cursos), $num);

            foreach ($selected as $cursoId) {
                $estado = (rand(0,100) < 60) ? $idEstadoEnEspera : $idEstadoAprobado; // 60% En Espera

                DB::table('soliestudiante')->updateOrInsert(
                    ['idestudiante' => $estId, 'idcurso' => $cursoId],
                    [
                        'idestudiante' => $estId,
                        'idcurso' => $cursoId,
                        'fecha' => $now,
                        'idestado' => $estado,
                    ]
                );
            }
        }
    }
}
