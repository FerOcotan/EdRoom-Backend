<?php

namespace App\Http\Controllers;

use App\Models\clase;
use App\Models\soliestudiante;
use App\Models\estado as EstadoModel;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index() {
        try {
            $req = request();
            $query = clase::with(['curso','estado']);

            // Si pasan ?curso=ID, filtrar por ese curso
            $cursoId = $req->query('curso');
            if ($cursoId) {
                $query->where('idcurso', $cursoId);
            }

            // Si el middleware adjunta beeart_user_id, limitar a cursos del docente
            // pero sólo cuando NO se solicitó explícitamente `?curso=ID`.
            // Esto permite que estudiantes autenticados consulten las clases de
            // un curso específico sin que se aplique el filtro por owner.
            $beeartUserId = $req->attributes->get('beeart_user_id');
            if ($beeartUserId && empty($cursoId)) {
                $query->whereHas('curso', function($q) use ($beeartUserId) {
                    $q->where('idusuario', $beeartUserId);
                });
            }

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
            $c = clase::with(['curso','estado'])->where('idclase', $id)->first();
            if (!$c) return response()->json(['message' => 'No encontrado'], 404);
            return response()->json($c);
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

        $c = clase::create($data);
        return response()->json($c, 201);
    }

    public function update(Request $req, $id) {
        $c = clase::where('idclase', $id)->first();
        if (!$c) return response()->json(['message' => 'No encontrado'], 404);
        $data = $req->only(['tema','fechahorainicio','fechahorafinal','url','idestado']);
        $c->fill($data);
        $c->save();
        return response()->json($c);
    }

    public function destroy($id) {
        $c = clase::where('idclase', $id)->first();
        if (!$c) return response()->json(['message' => 'No encontrado'], 404);
        $c->delete();
        return response()->json(['deleted' => true]);
    }
}
