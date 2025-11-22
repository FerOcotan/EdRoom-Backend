<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\soliestudiante;
use Illuminate\Support\Facades\DB;

class ImportStudentsCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:import {file} {course_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa estudiantes desde un CSV, crea cuentas si no existen y crea solicitudes aprobadas para un curso.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');
        $courseId = (int) $this->argument('course_id');

        if (!file_exists($file)) {
            $this->error("El archivo no existe: $file");
            return 1;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo: $file");
            return 1;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            $this->error('CSV vacío o inválido');
            fclose($handle);
            return 1;
        }

        // Normalizar cabeceras
        $cols = array_map(function ($c) { return strtolower(trim($c)); }, $header);

        $emailIndex = array_search('email', $cols);
        $nameIndex = array_search('name', $cols);
        $courseIndex = array_search('course_id', $cols);

        $createdUsers = 0;
        $createdRequests = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $email = $emailIndex !== false ? trim($row[$emailIndex]) : null;
                $name = $nameIndex !== false ? trim($row[$nameIndex]) : ($email ? explode('@', $email)[0] : 'Estudiante');
                $rowCourse = $courseIndex !== false ? (int) $row[$courseIndex] : $courseId;
                if (!$email) {
                    $skipped++;
                    continue;
                }

                $user = User::where('email', $email)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => 'password',
                        'idrol' => 3, // Estudiante
                        'idestado' => 8, // Activo
                    ]);
                    $createdUsers++;
                }

                // Usar el curso de la fila si existe, si no, usar el argumento
                $finalCourseId = $rowCourse ? $rowCourse : $courseId;

                // Crear o actualizar solicitud: marcar como aprobada (idestado = 6)
                $existing = soliestudiante::where('idestudiante', $user->id)
                    ->where('idcurso', $finalCourseId)
                    ->first();

                if ($existing) {
                    // Actualizar estado si es diferente
                    if ($existing->idestado != 6) {
                        $existing->idestado = 6;
                        $existing->fecha = now();
                        $existing->save();
                        $createdRequests++;
                    } else {
                        $skipped++;
                    }
                } else {
                    soliestudiante::create([
                        'idestudiante' => $user->id,
                        'idcurso' => $finalCourseId,
                        'fecha' => now(),
                        'idestado' => 6, // Aprobado
                    ]);
                    $createdRequests++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error('Error al procesar CSV: ' . $e->getMessage());
            return 1;
        }

        fclose($handle);

        $this->info("Usuarios creados: $createdUsers");
        $this->info("Solicitudes creadas/actualizadas como aprobadas: $createdRequests");
        $this->info("Filas omitidas (invalidas/duplicadas): $skipped");

        return 0;
    }
}
