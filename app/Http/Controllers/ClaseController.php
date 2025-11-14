<?php

namespace App\Http\Controllers;

use App\Models\clase;
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
            $beeartUserId = $req->attributes->get('beeart_user_id');
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

            $next = clase::with(['curso','estado'])
                ->whereHas('curso', function($q) use ($userId) {
                    $q->where('idusuario', $userId);
                })
                ->where(function($q) use ($now) {
                    $q->whereNull('fechahorainicio')->orWhere('fechahorainicio', '>=', $now);
                })
                ->orderBy('fechahorainicio', 'asc')
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
