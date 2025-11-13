<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $idEstadoAgendada = DB::table('estado')->where('estado', 'Agendada')->value('idestado') ?: 1;

        $cursos = DB::table('curso')->select('idcurso','nombre')->get();
        $now = Carbon::now();

        foreach ($cursos as $curso) {
            // Crear 3 clases por curso (si no existen)
            for ($i = 1; $i <= 3; $i++) {
                $tema = "{$curso->nombre} - Clase {$i}";

                DB::table('clase')->updateOrInsert(
                    ['idcurso' => $curso->idcurso, 'tema' => $tema],
                    [
                        'idcurso' => $curso->idcurso,
                        'tema' => $tema,
                        'fechahorainicio' => $now->copy()->addDays($i)->toDateTimeString(),
                        'fechahorafinal' => $now->copy()->addDays($i)->addHour()->toDateTimeString(),
                        'url' => null,
                        'idestado' => $idEstadoAgendada,
                    ]
                );
            }
        }
    }
}
