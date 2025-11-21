<?php

namespace App\Http\Controllers;

use App\Models\clase;
use App\Models\soliestudiante;
use App\Models\estado as EstadoModel;
use App\Models\User;
use App\Models\curso as CursoModel;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index() {
        try {
            $req = request();
            $query = clase::with(['curso','estado']);

            $cursoId = $req->query('curso');
            $beeartUserId = $req->attributes->get('beeart_user_id');

            // Require authenticated user (middleware normally ensures token)
            if (!$beeartUserId) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Allow admins to see everything
            $me = User::find($beeartUserId);
            $role = $me ? (int)($me->idrol ?? 0) : null;
            if ($role === 1) {
                if ($cursoId) $query->where('idcurso', $cursoId);
                $results = $query->get();
                return response()->json($results);
            }

            // If a specific course is requested, only allow if the user is the owner
            // or the user has an approved solicitud (idestado == 6) for that course.
            if ($cursoId) {
                $courseOwner = CursoModel::where('idcurso', $cursoId)->value('idusuario');
                if ($courseOwner && $courseOwner == $beeartUserId) {
                    $query->where('idcurso', $cursoId);
                    return response()->json($query->get());
                }

                // Check if the user has an approved solicitud for this course
                $approved = soliestudiante::where('idcurso', $cursoId)
                    ->where('idestudiante', $beeartUserId)
                    ->where('idestado', 6)
                    ->exists();
                if ($approved) {
                    $query->where('idcurso', $cursoId);
                    return response()->json($query->get());
                }

                return response()->json(['message' => 'No autorizado'], 403);
            }

            // No specific course requested: return classes for courses where the user
            // is owner OR where the user has an approved solicitud (idestado == 6).
            $ownerCourseIds = CursoModel::where('idusuario', $beeartUserId)->pluck('idcurso')->toArray();
            $approvedCourseIds = soliestudiante::where('idestudiante', $beeartUserId)
                ->where('idestado', 6)
                ->pluck('idcurso')
                ->toArray();

            $courseIds = array_values(array_unique(array_merge($ownerCourseIds, $approvedCourseIds)));
            if (empty($courseIds)) {
                return response()->json([]);
            }

            $query->whereIn('idcurso', $courseIds);
            $results = $query->get();
            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Devuelve la próxima clase del docente autenticado (por token).
     * Si no hay clase futura, devuelve null.
     */
    public function next(Request $req) {
        try {
            // El middleware CheckBeeartToken adjunta beeart_user_id cuando el token es válido
            $userId = $req->attributes->get('beeart_user_id');

            if (!$userId) {
                return response()->json(null, 200);
            }

            $now = now();
            // Determinar cursos relevantes para este usuario:
            // - si es docente (owner) devolver sus cursos
            // - además, incluir cursos donde el usuario está inscrito/aprobado

            // Cursos donde es docente
            $docenteCursoIds = \App\Models\curso::where('idusuario', $userId)->pluck('idcurso')->toArray();

            // Cursos donde es estudiante aprobado (usar misma heurística de estados que en SoliEstudianteController)
            $explicitApproved = 6;
            $found = EstadoModel::whereRaw("LOWER(estado) LIKE ?", ['%aprob%'])
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

            $estudianteCursoIds = [];
            if (!empty($candidates)) {
                $rows = soliestudiante::where('idestudiante', $userId)
                    ->whereIn('idestado', $candidates)
                    ->pluck('idcurso')
                    ->toArray();
                $estudianteCursoIds = $rows ?: [];
            }

            $courseIds = array_values(array_unique(array_merge($docenteCursoIds, $estudianteCursoIds)));

            if (empty($courseIds)) {
                return response()->json(null, 200);
            }

            // Seleccionar la próxima clase que esté en curso o por iniciar.
            // Incluir:
            // - clases sin fecha (fechahorainicio NULL)
            // - clases futuras (fechahorainicio >= now)
            // - clases en curso (fechahorainicio <= now AND (fechahorafinal IS NULL OR fechahorafinal >= now))

            $next = clase::with(['curso','estado'])
                ->whereIn('idcurso', $courseIds)
                ->where(function($q) use ($now) {
                    $q->whereNull('fechahorainicio')
                      ->orWhere('fechahorainicio', '>=', $now)
                      ->orWhere(function($q2) use ($now) {
                          $q2->where('fechahorainicio', '<=', $now)
                             ->where(function($q3) use ($now) {
                                 $q3->whereNull('fechahorafinal')->orWhere('fechahorafinal', '>=', $now);
                             });
                      });
                })
                ->orderByRaw("CASE WHEN fechahorainicio IS NULL THEN 1 ELSE 0 END, fechahorainicio ASC")
                ->first();

            return response()->json($next, 200);
        } catch (\Exception $e) {
            // En caso de error, devolver null para no romper la UI
            return response()->json(null, 200);
        }
    }

    public function show($id) {
        try {
            $req = request();
            $beeartUserId = $req->attributes->get('beeart_user_id');

            $c = clase::with(['curso','estado'])->where('idclase', $id)->first();
            if (!$c) return response()->json(['message' => 'No encontrado'], 404);

            // Require authentication
            if (!$beeartUserId) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Admins can view
            $me = User::find($beeartUserId);
            $role = $me ? (int)($me->idrol ?? 0) : null;
            if ($role === 1) {
                return response()->json($c);
            }

            // Owner of the course can view
            $courseOwner = CursoModel::where('idcurso', $c->idcurso)->value('idusuario');
            if ($courseOwner && $courseOwner == $beeartUserId) {
                return response()->json($c);
            }

            // Student must have an approved solicitud (idestado == 6)
            $approved = soliestudiante::where('idcurso', $c->idcurso)
                ->where('idestudiante', $beeartUserId)
                ->where('idestado', 6)
                ->exists();
            if ($approved) {
                return response()->json($c);
            }

            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function store(Request $req) {
        $data = $req->validate([
            'idcurso' => 'required|integer',
            'tema' => 'required|string|max:100',
            'fechahorainicio' => 'nullable|date',
            'fechahorafinal' => 'nullable|date',
            'url' => 'nullable|string|max:100',
            'idestado' => 'required|integer',
        ]);

        // Verify requester owns the course
        $beeartUserId = $req->attributes->get('beeart_user_id');
        if (!$beeartUserId) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }
        $courseOwner = \App\Models\curso::where('idcurso', $data['idcurso'])->value('idusuario');
        if ($courseOwner != $beeartUserId) {
            return response()->json(['message' => 'No autorizado para crear clases en este curso'], 403);
        }

        // Additional business validation: no permitir fechas en el pasado y asegurar inicio < fin
        try {
            $now = now();
            if (!empty($data['fechahorainicio'])) {
                $start = \Illuminate\Support\Carbon::parse($data['fechahorainicio']);
                if ($start->lessThan($now)) {
                    return response()->json(['errors' => ['fechahorainicio' => ['La fecha y hora de inicio no puede ser anterior al momento actual.']]], 422);
                }
            }
            if (!empty($data['fechahorafinal'])) {
                $end = \Illuminate\Support\Carbon::parse($data['fechahorafinal']);
                if ($end->lessThan($now)) {
                    return response()->json(['errors' => ['fechahorafinal' => ['La fecha y hora de fin no puede ser anterior al momento actual.']]], 422);
                }
            }
            if (!empty($data['fechahorainicio']) && !empty($data['fechahorafinal'])) {
                $start = \Illuminate\Support\Carbon::parse($data['fechahorainicio']);
                $end = \Illuminate\Support\Carbon::parse($data['fechahorafinal']);
                if ($end->lessThanOrEqualTo($start)) {
                    return response()->json(['errors' => ['fechahorafinal' => ['La fecha de fin debe ser posterior a la fecha de inicio.']]], 422);
                }
            }
        } catch (\Exception $e) {
            // Si el parse falla, devolver error 422 con mensaje claro
            return response()->json(['errors' => ['fechas' => ['Fechas inválidas.']]], 422);
        }

        $c = clase::create($data);

        // Notificar por correo a estudiantes aprobados del curso
        try {
            $c->load('curso');

            // Determinar estados aprobados (misma heurística usada en otros controladores)
            $explicitApproved = 6;
            $found = EstadoModel::whereRaw("LOWER(estado) LIKE ?", ['%aprob%'])
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

            if (!empty($candidates)) {
                $students = soliestudiante::with('estudiante')
                    ->where('idcurso', $data['idcurso'])
                    ->whereIn('idestado', $candidates)
                    ->get();

                $emails = [];
                foreach ($students as $s) {
                    if ($s->estudiante && !empty($s->estudiante->email)) {
                        // Construir nombre del estudiante: preferir `name`, fallback a `nombre apellido`, fallback a email
                        $studentName = $s->estudiante->name ?? trim(($s->estudiante->nombre ?? '') . ' ' . ($s->estudiante->apellido ?? ''));
                        if (empty($studentName)) $studentName = $s->estudiante->email;
                        $emails[$s->estudiante->email] = $studentName;
                    }
                }

                if (!empty($emails)) {
                    // Formatear fechas en español (ej: 21 de noviembre de 2025 a las 16:40)
                    try {
                        $startFmt = null;
                        $endFmt = null;
                        if (!empty($c->fechahorainicio)) {
                            $startFmt = \Illuminate\Support\Carbon::parse($c->fechahorainicio)
                                ->locale('es')
                                ->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm');
                        }
                        if (!empty($c->fechahorafinal)) {
                            $endFmt = \Illuminate\Support\Carbon::parse($c->fechahorafinal)
                                ->locale('es')
                                ->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm');
                        }
                    } catch (\Exception $e) {
                        $startFmt = !empty($c->fechahorainicio) ? (string)$c->fechahorainicio : null;
                        $endFmt = !empty($c->fechahorafinal) ? (string)$c->fechahorafinal : null;
                    }

                    // Nombre del docente (dueño del curso)
                    $teacherName = null;
                    try {
                        $ownerId = \App\Models\curso::where('idcurso', $data['idcurso'])->value('idusuario');
                        if ($ownerId) {
                            $owner = User::find($ownerId);
                            if ($owner) {
                                $teacherName = $owner->name ?? trim(($owner->nombre ?? '') . ' ' . ($owner->apellido ?? '')) ?: null;
                            }
                        }
                    } catch (\Exception $e) {
                        $teacherName = null;
                    }

                    foreach ($emails as $email => $studentName) {
                        \Illuminate\Support\Facades\Mail::to($email)
                            ->send(new \App\Mail\ClassScheduledMail(
                                $studentName,
                                $c->curso->nombre ?? '',
                                $startFmt,
                                $endFmt,
                                (env('FRONTEND_URL', env('APP_URL', '')) ?: '') . '/courses/' . ($c->idcurso ?? ''),
                                $teacherName
                            ));
                    }
                }
            }
        } catch (\Exception $e) {
            // No interrumpir la creación de la clase si el envío falla
        }

        return response()->json($c, 201);
    }

    public function update(Request $req, $id) {
        $c = clase::where('idclase', $id)->first();
        if (!$c) return response()->json(['message' => 'No encontrado'], 404);
        $data = $req->only(['tema','fechahorainicio','fechahorafinal','url','idestado']);

        $beeartUserId = $req->attributes->get('beeart_user_id');
        if (!$beeartUserId) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }
        $courseOwner = \App\Models\curso::where('idcurso', $c->idcurso)->value('idusuario');
        if ($courseOwner != $beeartUserId) {
            return response()->json(['message' => 'No autorizado para modificar esta clase'], 403);
        }

        // Validate dates: no permitir poner fechas pasadas y asegurar inicio < fin
        try {
            $now = now();
            if (!empty($data['fechahorainicio'])) {
                $start = \Illuminate\Support\Carbon::parse($data['fechahorainicio']);
                if ($start->lessThan($now)) {
                    return response()->json(['errors' => ['fechahorainicio' => ['La fecha y hora de inicio no puede ser anterior al momento actual.']]], 422);
                }
            }
            if (!empty($data['fechahorafinal'])) {
                $end = \Illuminate\Support\Carbon::parse($data['fechahorafinal']);
                if ($end->lessThan($now)) {
                    return response()->json(['errors' => ['fechahorafinal' => ['La fecha y hora de fin no puede ser anterior al momento actual.']]], 422);
                }
            }
            if (!empty($data['fechahorainicio']) && !empty($data['fechahorafinal'])) {
                $start = \Illuminate\Support\Carbon::parse($data['fechahorainicio']);
                $end = \Illuminate\Support\Carbon::parse($data['fechahorafinal']);
                if ($end->lessThanOrEqualTo($start)) {
                    return response()->json(['errors' => ['fechahorafinal' => ['La fecha de fin debe ser posterior a la fecha de inicio.']]], 422);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => ['fechas' => ['Fechas inválidas.']]], 422);
        }

        $c->fill($data);
        $c->save();
        return response()->json($c);
    }

    public function destroy($id) {
        $req = request();
        $beeartUserId = $req->attributes->get('beeart_user_id');
        if (!$beeartUserId) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $c = clase::where('idclase', $id)->first();
        if (!$c) return response()->json(['message' => 'No encontrado'], 404);

        $courseOwner = \App\Models\curso::where('idcurso', $c->idcurso)->value('idusuario');
        if ($courseOwner != $beeartUserId) {
            return response()->json(['message' => 'No autorizado para eliminar esta clase'], 403);
        }

        $c->delete();
        return response()->json(['deleted' => true]);
    }
}
