<?php

namespace App\Http\Controllers;

use App\Models\soliestudiante;
use App\Models\estado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoliEstudianteController extends Controller
{
    public function index(Request $request) {
        try {
            $query = soliestudiante::with(['estudiante','curso','estado']);

            // If the middleware attached a beeart_user_id, limit solicitudes to cursos owned by that user
            $beeartUserId = $request->attributes->get('beeart_user_id');
            if ($beeartUserId) {
                // Determine role of the caller: admins should see all, docentes see cursos they own, estudiantes see their own solicitudes
                $me = User::find($beeartUserId);
                $role = $me ? (int)($me->idrol ?? 0) : null;
                if ($role === 2) {
                    // Docente: only solicitudes for cursos owned by this docente
                    $query->whereHas('curso', function($q) use ($beeartUserId) {
                        $q->where('idusuario', $beeartUserId);
                    });
                } elseif ($role === 3) {
                    // Estudiante: only their own solicitudes
                    $query->where('idestudiante', $beeartUserId);
                }
                // Admins (role === 1) and other roles see all (no extra filter)
            }

            $results = $query->get();
            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function show($id) {
        try {
            $req = request();
            $beeartUserId = $req->attributes->get('beeart_user_id');

            $s = soliestudiante::with(['estudiante','curso','estado'])->where('idsoliestudiante', $id)->first();
            if (!$s) return response()->json(['message' => 'No encontrado'], 404);

            // Require authentication
            if (!$beeartUserId) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Allow admins
            $me = User::find($beeartUserId);
            $role = $me ? (int)($me->idrol ?? 0) : null;
            if ($role === 1) {
                return response()->json($s);
            }

            // Allow the student who sent the solicitud
            if ($s->idestudiante == $beeartUserId) {
                return response()->json($s);
            }

            // Allow the owner of the associated course
            $courseOwner = \App\Models\curso::where('idcurso', $s->idcurso)->value('idusuario');
            if ($courseOwner && $courseOwner == $beeartUserId) {
                return response()->json($s);
            }

            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function store(Request $req) {
        $data = $req->validate([
            'idestudiante' => 'required|integer',
            'idcurso' => 'required|integer',
            'fecha' => 'nullable|date',
            'idestado' => 'required|integer',
        ]);

        // Evitar duplicados: si ya existe una solicitud para el mismo estudiante+curso
        // en estado En Espera(5), Aprobado(6) o Denegado(7), devolver 409
        try {
            $existing = soliestudiante::where('idestudiante', $data['idestudiante'])
                ->where('idcurso', $data['idcurso'])
                ->whereIn('idestado', [5,6,7])
                ->first();
            if ($existing) {
                return response()->json(['message' => 'Ya existe una solicitud previa para este curso', 'existing' => $existing], 409);
            }
        } catch (\Exception $e) {
            // seguir adelante si la comprobación falla por alguna razón inesperada
        }

        $s = soliestudiante::create($data);
        return response()->json($s, 201);
    }

    public function update(Request $req, $id) {
        $s = soliestudiante::where('idsoliestudiante', $id)->first();
        if (!$s) return response()->json(['message' => 'No encontrado'], 404);

        $beeartUserId = $req->attributes->get('beeart_user_id');
        if (!$beeartUserId) return response()->json(['message' => 'Usuario no autenticado'], 401);

        $me = User::find($beeartUserId);
        $role = $me ? (int)($me->idrol ?? 0) : null;

        $allowed = false;
        if ($role === 1) {
            // Admin
            $allowed = true;
        } elseif ($role === 3) {
            // Estudiante: sólo puede modificar su propia solicitud
            if ($s->idestudiante == $beeartUserId) $allowed = true;
        } elseif ($role === 2) {
            // Docente: sólo si es propietario del curso asociado
            $courseOwner = \App\Models\curso::where('idcurso', $s->idcurso)->value('idusuario');
            if ($courseOwner == $beeartUserId) $allowed = true;
        }

        if (!$allowed) return response()->json(['message' => 'No autorizado'], 403);

        $oldIdEstado = $s->idestado;

        $data = $req->only(['fecha','idestado']);

        // Determinar qué estados se consideran "aprobados"
        $explicitApproved = 6;
        $found = \App\Models\estado::whereRaw("LOWER(estado) LIKE ?", ['%aprob%'])
            ->orWhereRaw("LOWER(estado) LIKE ?", ['%acept%'])
            ->orWhereRaw("LOWER(estado) LIKE ?", ['%inscrit%'])
            ->orWhereRaw("LOWER(estado) LIKE ?", ['%matricu%'])
            ->pluck('idestado')
            ->toArray();

        $candidates = $found;
        if (!in_array($explicitApproved, $candidates)) {
            $candidates[] = $explicitApproved;
        }
        $candidates = array_values(array_unique(array_map('intval', $candidates)));

        // Si el nuevo estado implica aprobar y antes no estaba aprobado, comprobar cupo
        $willBeApproved = isset($data['idestado']) && in_array((int)$data['idestado'], $candidates);
        $wasApproved = in_array((int)$oldIdEstado, $candidates);

        try {
            DB::beginTransaction();

            // Si se va a aprobar y antes no estaba, validar cupo
            if ($willBeApproved && !$wasApproved) {
                $curso = \App\Models\curso::where('idcurso', $s->idcurso)->first();
                $max = $curso ? (int)($curso->maximoest ?? 0) : 0;

                if ($max > 0) {
                    $currentApproved = soliestudiante::where('idcurso', $s->idcurso)
                        ->whereIn('idestado', $candidates)
                        ->count();

                    if ($currentApproved >= $max) {
                        DB::rollBack();
                        return response()->json(['message' => 'Cupo completo para este curso'], 409);
                    }
                }
            }

            // Aplicar cambios
            $s->fill($data);
            $s->save();

            // Actualizar contador `estuinscritos` en la tabla curso si es posible
            try {
                $s->load('estudiante','curso','estado');

                // Si pasó de no aprobado -> aprobado, incrementar contador
                if ($willBeApproved && !$wasApproved) {
                    if (isset($curso) && $curso) {
                        $curso->estuinscritos = max(0, (int)($curso->estuinscritos ?? 0)) + 1;
                        $curso->save();
                    }
                }

                // Si pasó de aprobado -> no aprobado, decrementar contador
                if (!$willBeApproved && $wasApproved) {
                    $curso = $curso ?? \App\Models\curso::where('idcurso', $s->idcurso)->first();
                    if ($curso) {
                        $curso->estuinscritos = max(0, (int)($curso->estuinscritos ?? 0) - 1);
                        $curso->save();
                    }
                }

                // Envío de correo si el estado cambió a aprobado
                if (in_array((int)$s->idestado, $candidates) && (int)$oldIdEstado !== (int)$s->idestado) {
                    if ($s->estudiante && !empty($s->estudiante->email)) {
                        $studentName = $s->estudiante->name ?? trim(($s->estudiante->nombre ?? '') . ' ' . ($s->estudiante->apellido ?? ''));
                        if (empty($studentName)) $studentName = $s->estudiante->email;

                        \Illuminate\Support\Facades\Mail::to($s->estudiante->email)
                            ->send(new \App\Mail\StudentAdmittedMail(
                                $studentName,
                                $s->estudiante->email,
                                $s->curso->nombre ?? ($s->curso->title ?? ''),
                                (string)($s->idcurso ?? $s->curso->idcurso ?? ''),
                                null,
                                (env('FRONTEND_URL', env('APP_URL', '')) ?: '') . '/courses/' . ($s->idcurso ?? $s->curso->idcurso ?? '')
                            ));
                    }
                }
            } catch (\Exception $e) {
                // no interrumpir el flujo por errores en el envío de correo o actualización del curso
            }

            DB::commit();
            return response()->json($s);
        } catch (\Exception $e) {
            try { DB::rollBack(); } catch (\Exception $_) {}
            return response()->json(['message' => 'Error al actualizar solicitud'], 500);
        }
    }

    public function destroy($id) {
        $req = request();
        $beeartUserId = $req->attributes->get('beeart_user_id');
        if (!$beeartUserId) return response()->json(['message' => 'Usuario no autenticado'], 401);

        $s = soliestudiante::where('idsoliestudiante', $id)->first();
        if (!$s) return response()->json(['message' => 'No encontrado'], 404);

        $me = User::find($beeartUserId);
        $role = $me ? (int)($me->idrol ?? 0) : null;

        $allowed = false;
        if ($role === 1) {
            $allowed = true;
        } elseif ($role === 3) {
            if ($s->idestudiante == $beeartUserId) $allowed = true;
        } elseif ($role === 2) {
            $courseOwner = \App\Models\curso::where('idcurso', $s->idcurso)->value('idusuario');
            if ($courseOwner == $beeartUserId) $allowed = true;
        }

        if (!$allowed) return response()->json(['message' => 'No autorizado'], 403);

        $s->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Devuelve las solicitudes asociadas al estudiante identificado por el token.
     * Ruta: GET /api/soliestudiantes/mine
     */
    public function mine(Request $request) {
        try {
            $beeartUserId = $request->attributes->get('beeart_user_id');
            if (!$beeartUserId) return response()->json(['message' => 'Token de sesión requerido'], 401);

            $rows = soliestudiante::with(['estudiante','curso','estado'])
                ->where('idestudiante', $beeartUserId)
                ->get();

            return response()->json($rows);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Devuelve los estudiantes "inscritos" (estado aprobado) para un curso.
     * Retorna un arreglo sencillo de objetos con { id, nombre, email }.
     * La determinación del/los estados aprobados se hace buscando en la tabla
     * `estado` cadenas comunes como 'aprob', 'acept', 'inscrit', 'matricu'.
     */
    public function inscritosByCurso(Request $request, $id) {
        try {
            // Permitir override via query param ?idestado=6 o ?idestado=6,7
            $qIdEstado = $request->query('idestado');
            if ($qIdEstado) {
                $ids = array_filter(array_map('intval', explode(',', $qIdEstado)));
                $candidates = array_values(array_unique($ids));
            } else {
                // Añadir explícitamente el id conocido para "Aprobado" (6)
                $explicitApproved = 6;

                // Buscar posibles estados por heurística (nombre)
                $found = estado::whereRaw("LOWER(estado) LIKE ?", ['%aprob%'])
                    ->orWhereRaw("LOWER(estado) LIKE ?", ['%acept%'])
                    ->orWhereRaw("LOWER(estado) LIKE ?", ['%inscrit%'])
                    ->orWhereRaw("LOWER(estado) LIKE ?", ['%matricu%'])
                    ->pluck('idestado')
                    ->toArray();

                // Garantizar que el id explícito esté presente
                $candidates = $found;
                if (!in_array($explicitApproved, $candidates)) {
                    $candidates[] = $explicitApproved;
                }
                // Normalizar a enteros y quitar duplicados
                $candidates = array_values(array_unique(array_map('intval', $candidates)));
            }

            // Si no hay candidatos validos, devolver vacío
            if (empty($candidates)) {
                return response()->json([]);
            }

            $rows = soliestudiante::with('estudiante')
                ->where('idcurso', $id)
                ->whereIn('idestado', $candidates)
                ->get();

            // Soporte para exportar CSV: si se solicita ?format=csv devolvemos un attachment CSV
            $format = $request->query('format');
            if ($format && strtolower($format) === 'csv') {
                $filename = sprintf("inscritos_curso_%s_%s.csv", $id, date('Ymd_His'));
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                return response()->streamDownload(function() use ($rows) {
                    $out = fopen('php://output', 'w');
                        // Cabeceras CSV (nombre, email) — exportar sólo nombre y correo
                        fputcsv($out, ['nombre', 'email']);
                        foreach ($rows as $s) {
                            $email = $s->estudiante->email ?? '';
                            $n1 = $s->estudiante->nombre ?? $s->estudiante->name ?? '';
                            $n2 = $s->estudiante->apellido ?? '';
                            $name = trim($n1 . ' ' . $n2);
                            fputcsv($out, [$name, $email]);
                        }
                    fclose($out);
                }, $filename, $headers);
            }

            $list = $rows->map(function($s) {
                $nombre = '';
                if ($s->estudiante) {
                    $n1 = $s->estudiante->nombre ?? '';
                    $n2 = $s->estudiante->apellido ?? '';
                    $nombre = trim($n1 . ' ' . $n2);
                }
                return [
                    'id' => $s->idsoliestudiante ?? null,
                    'nombre' => $nombre,
                    'email' => $s->estudiante->email ?? null,
                ];
            });

            return response()->json($list->values());
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Devuelve los cursos a los que un estudiante está aprobado (idestado aprobado).
     * Ruta sugerida: GET /api/soliestudiantes/estudiante/{id}
     * Se puede forzar idestado vía query param ?idestado=6 o ?idestado=6,7
     */
    public function cursosByEstudiante(Request $request, $idestudiante) {
        try {
            // Allow override via query param ?idestado=6 or ?idestado=6,7
            $qIdEstado = $request->query('idestado');
            if ($qIdEstado) {
                $ids = array_filter(array_map('intval', explode(',', $qIdEstado)));
                $candidates = array_values(array_unique($ids));
            } else {
                // Default approved id
                $explicitApproved = 6;

                // Heuristic search of estado names
                $found = estado::whereRaw("LOWER(estado) LIKE ?", ['%aprob%'])
                    ->orWhereRaw("LOWER(estado) LIKE ?", ['%acept%'])
                    ->orWhereRaw("LOWER(estado) LIKE ?", ['%inscrit%'])
                    ->orWhereRaw("LOWER(estado) LIKE ?", ['%matricu%'])
                    ->pluck('idestado')
                    ->toArray();

                $candidates = $found;
                if (!in_array($explicitApproved, $candidates)) {
                    $candidates[] = $explicitApproved;
                }
                $candidates = array_values(array_unique(array_map('intval', $candidates)));
            }

            if (empty($candidates)) {
                return response()->json([]);
            }

            // Obtener solicitudes del estudiante filtradas por estados aprobados
            $rows = soliestudiante::with(['curso','estado'])
                ->where('idestudiante', $idestudiante)
                ->whereIn('idestado', $candidates)
                ->get();

            // Mapear a lista de cursos (mantener algunos campos útiles)
            $list = $rows->map(function($s) {
                $c = $s->curso ?? null;
                if (!$c) return null;
                return [
                    'id' => $c->idcurso ?? $c->id ?? null,
                    'nombre' => $c->nombre ?? $c->title ?? null,
                    'descripcion' => $c->descripcion ?? null,
                    'raw' => $c,
                    'estado' => $s->estado ?? null,
                ];
            })->filter()->values();

            return response()->json($list);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
