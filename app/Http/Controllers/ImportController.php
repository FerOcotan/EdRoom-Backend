<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\soliestudiante;
use App\Models\curso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    /**
     * Import students from uploaded CSV, create users with provided default password,
     * and create soliestudiante entries marked as approved (idestado = 6).
     */
    public function importStudents(Request $req, $courseId)
    {
        // Require an uploaded file
        if (!$req->hasFile('file')) {
            return response()->json(['message' => 'Falta el archivo CSV (field: file)'], 400);
        }

        $file = $req->file('file');
        if (!$file->isValid()) {
            return response()->json(['message' => 'Archivo inválido'], 400);
        }

        // Determine whether to create user accounts for missing emails.
        // Accepts '1','0','true','false' (case-insensitive). Default: true.
        $createAccounts = $req->has('create_accounts') ? filter_var($req->input('create_accounts'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : true;
        if ($createAccounts === null) $createAccounts = true;

        // Default password provided by docente is required only when creating accounts
        $defaultPassword = $req->input('default_password');
        if ($createAccounts) {
            if (!$defaultPassword || strlen($defaultPassword) < 4) {
                return response()->json(['message' => 'Se requiere una contraseña por defecto (mínimo 4 caracteres) cuando se crean cuentas'], 400);
            }
        }

        // Authorization: ensure requester is admin or owner of the course
        $beeartUserId = $req->attributes->get('beeart_user_id');
        if (!$beeartUserId) return response()->json(['message' => 'Usuario no autenticado'], 401);

        $me = User::find($beeartUserId);
        if (!$me) return response()->json(['message' => 'Usuario no encontrado'], 401);

        $course = curso::where('idcurso', (int)$courseId)->first();
        if (!$course) return response()->json(['message' => 'Curso no encontrado'], 404);

        $role = (int)($me->idrol ?? 0);
        if ($role !== 1 && $course->idusuario != $beeartUserId) {
            return response()->json(['message' => 'No autorizado para importar en este curso'], 403);
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) return response()->json(['message' => 'No se pudo abrir el archivo'], 500);

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return response()->json(['message' => 'CSV vacío o inválido'], 400);
        }

        // Leer todas las filas restantes para diagnóstico y procesamiento
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        // Log para depuración: cabecera y primeras filas
        try {
            Log::debug('[ImportController] CSV header: ' . json_encode($header));
            Log::debug('[ImportController] Detected rows: ' . count($rows));
            $preview = array_slice($rows, 0, 5);
            Log::debug('[ImportController] Preview rows: ' . json_encode($preview));
        } catch (\Exception $e) {
            // ignore logging failures
        }

        $cols = array_map(function($c){ return strtolower(trim($c)); }, $header);
        $emailIndex = array_search('email', $cols);
        $nameIndex = array_search('name', $cols);
        $courseIndex = array_search('course_id', $cols);

        $createdUsers = 0; $createdRequests = 0; $skipped = 0;
        // Reporte detallado por fila (útil para ver qué pasa con usuarios existentes)
        $report = [
            'created_users' => [],
            'existing_users' => [],
            'created_requests' => [],
            'updated_requests' => [],
            'skipped_rows' => [],
        ];
        $missingAccounts = [];

        DB::beginTransaction();
        try {
            // Procesar las filas previamente leídas
            foreach ($rows as $row) {
                $email = $emailIndex !== false ? trim($row[$emailIndex] ?? '') : null;
                if (!$email) { $skipped++; continue; }
                $name = $nameIndex !== false ? trim($row[$nameIndex] ?? '') : explode('@', $email)[0];
                $rowCourse = $courseIndex !== false ? trim($row[$courseIndex] ?? '') : '';

                $user = User::where('email', $email)->first();
                if (!$user) {
                    if ($createAccounts) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => $defaultPassword,
                            'idrol' => 3,
                            'idestado' => 8,
                        ]);
                        $createdUsers++;
                        $report['created_users'][] = $email;
                    } else {
                        // No crear cuenta: si el usuario no existe, no podemos crear una solicitud vinculada
                        $skipped++;
                        $report['skipped_rows'][] = ['email' => $email, 'reason' => 'user not found and create_accounts=false'];
                        // Marcar en missingAccounts para reportarlo claramente en la respuesta
                        $missingAccounts[] = $email;
                        continue;
                    }
                } else {
                    $report['existing_users'][] = $email;
                }

                // Usar siempre el curso de la ruta. Si el CSV traía `course_id` distinto,
                // lo ignoramos y registramos un mensaje informativo.
                $finalCourseId = (int)$courseId;
                if ($courseIndex !== false && $rowCourse !== '') {
                    if (is_numeric($rowCourse) && (int)$rowCourse !== $finalCourseId) {
                        try {
                            Log::info('[ImportController] Ignorando course_id del CSV (' . $rowCourse . ") y usando courseId de ruta: " . $finalCourseId);
                        } catch (\Exception $e) { /* ignore logging failures */ }
                    }
                }

                $existing = soliestudiante::where('idestudiante', $user->id)
                    ->where('idcurso', $finalCourseId)
                    ->first();
                if ($existing) {
                    if ($existing->idestado != 6) {
                        $existing->idestado = 6; $existing->fecha = now(); $existing->save(); $createdRequests++;
                        $report['updated_requests'][] = ['email' => $email, 'idcurso' => $finalCourseId];
                    } else {
                        $skipped++;
                        $report['skipped_rows'][] = ['email' => $email, 'reason' => 'request already approved', 'idcurso' => $finalCourseId];
                    }
                } else {
                    soliestudiante::create([
                        'idestudiante' => $user->id,
                        'idcurso' => $finalCourseId,
                        'fecha' => now(),
                        'idestado' => 6,
                    ]);
                    $createdRequests++;
                    $report['created_requests'][] = ['email' => $email, 'idcurso' => $finalCourseId];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack(); fclose($handle);
            return response()->json(['message' => 'Error al procesar CSV', 'error' => $e->getMessage()], 500);
        }

        fclose($handle);
        // Limitar el tamaño del reporte para evitar respuestas gigantescas (si es necesario)
        $maxItems = 500;
        foreach ($report as $k => $arr) {
            if (is_array($arr) && count($arr) > $maxItems) $report[$k] = array_slice($arr, 0, $maxItems);
        }

        // Si la importación se hizo con create_accounts=false, no devolvemos
        // la lista de "existing_users" para evitar reportes largos de usuarios
        // ya existentes cuando solo se quería inscribirlos.
        if (isset($createAccounts) && $createAccounts === false) {
            if (array_key_exists('existing_users', $report)) {
                unset($report['existing_users']);
            }
        }

        // Si había cuentas faltantes cuando create_accounts=false, incluirlas
        // explícitamente en la respuesta y añadir un mensaje claro.
        $note = null;
        if (isset($createAccounts) && $createAccounts === false && count($missingAccounts) > 0) {
            $note = 'Importación completada. No se encontraron cuentas para algunos emails.';
            // adjuntar lista de emails (acotar tamaño si es muy grande)
            $maxMissing = 1000;
            if (count($missingAccounts) > $maxMissing) {
                $missingSlice = array_slice($missingAccounts, 0, $maxMissing);
            } else {
                $missingSlice = $missingAccounts;
            }
        }

        $response = [
            'created_users' => $createdUsers,
            'created_requests' => $createdRequests,
            'skipped' => $skipped,
            'report' => $report,
        ];
        if ($note) {
            $response['message'] = $note;
            $response['missing_accounts'] = $missingSlice ?? [];
        }

        return response()->json($response);
    }
}
