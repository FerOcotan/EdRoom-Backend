<?php

namespace App\Http\Controllers;

use App\Models\soliestudiante;
use App\Models\estado;
use Illuminate\Http\Request;

class SoliEstudianteController extends Controller
{
    public function index(Request $request) {
        try {
            $query = soliestudiante::with(['estudiante','curso','estado']);

            // If the middleware attached a beeart_user_id, limit solicitudes to cursos owned by that user
            $beeartUserId = $request->attributes->get('beeart_user_id');
            if ($beeartUserId) {
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

    public function show($id) {
        try {
            $s = soliestudiante::with(['estudiante','curso','estado'])->where('idsoliestudiante', $id)->first();
            if (!$s) return response()->json(['message' => 'No encontrado'], 404);
            return response()->json($s);
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

        $s = soliestudiante::create($data);
        return response()->json($s, 201);
    }

    public function update(Request $req, $id) {
        $s = soliestudiante::where('idsoliestudiante', $id)->first();
        if (!$s) return response()->json(['message' => 'No encontrado'], 404);
        $data = $req->only(['fecha','idestado']);
        $s->fill($data);
        $s->save();
        return response()->json($s);
    }

    public function destroy($id) {
        $s = soliestudiante::where('idsoliestudiante', $id)->first();
        if (!$s) return response()->json(['message' => 'No encontrado'], 404);
        $s->delete();
        return response()->json(['deleted' => true]);
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
