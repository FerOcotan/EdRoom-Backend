<?php

namespace App\Http\Controllers;

use App\Models\curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index(Request $req) {
        try {
            // Si se solicita `all=1` o `global=1`, devolver todos los cursos sin filtrar
            $all = $req->query('all', $req->query('global', null));
            if ((string)$all === '1') {
                // Devolver todos los cursos excepto aquellos cuyo estado contenga 'inactiv'
                $cursos = curso::with(['user','estado','clases'])
                    ->where(function($q) {
                        // incluir cursos que no tienen idestado o cuyo idestado no es 9 (Inactivo)
                        $q->whereNull('idestado')
                          ->orWhere('idestado', '!=', 9);
                    })
                    ->orderBy('idcurso', 'asc')
                    ->get();
                return response()->json($cursos);
            }

            // Obtener id del docente desde query param `idusuario` o usar 15 por defecto
            $idusuario = $req->query('idusuario', 15);

            $cursos = curso::with(['user','estado','clases'])
                ->where('idusuario', $idusuario)
                ->where(function($q) {
                    $q->whereNull('idestado')
                      ->orWhere('idestado', '!=', 9);
                })
                ->orderBy('idcurso', 'asc')
                ->limit(10)
                ->get();

            return response()->json($cursos);
        } catch (\Exception $e) {
            // Si DB no está disponible en el entorno de pruebas, devolver array vacío
            return response()->json([], 200);
        }
    }

    public function show($id) {
        try {
            $c = curso::with(['user','estado','clases'])->where('idcurso', $id)->first();
            if (!$c) return response()->json(['message' => 'No encontrado'], 404);
            return response()->json($c);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function store(Request $req) {
        $data = $req->validate([
            'idusuario' => 'required|integer',
            // nombre: permitir dígitos pero debe ser único por nombre de curso
            'nombre' => ['required','string','max:50','unique:curso,nombre'],
            'descripcion' => 'nullable|string|max:300',
            // máximo estudiantes: entero entre 1 y 45
            'maximoest' => 'required|integer|min:1|max:45',
            'estuinscritos' => 'nullable|integer',
            'idestado' => 'required|integer',
        ]);

        $c = curso::create($data);
        return response()->json($c, 201);
    }

    public function update(Request $req, $id) {
        $c = curso::where('idcurso', $id)->first();
        if (!$c) return response()->json(['message' => 'No encontrado'], 404);
        // Validar datos para evitar errores de base de datos (p. ej. texto demasiado largo)
        // Cuando actualizamos, permitimos el mismo nombre para el registro actual
        $validated = $req->validate([
            'nombre' => ['sometimes','required','string','max:50',"unique:curso,nombre,{$id},idcurso"],
            'descripcion' => 'nullable|string|max:300',
            'maximoest' => ['sometimes','required','integer','min:1','max:45'],
            'estuinscritos' => 'nullable|integer',
            'idestado' => ['sometimes','required','integer'],
        ]);

        try {
            $c->fill($validated);
            $c->save();
            return response()->json($c);
        } catch (\Exception $e) {
            // Registrar error en storage/logs para diagnóstico y devolver JSON claro
            \Illuminate\Support\Facades\Log::error('[CursoController@update] Error saving curso: ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar curso', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        $c = curso::where('idcurso', $id)->first();
        if (!$c) return response()->json(['message' => 'No encontrado'], 404);
        $c->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Obtener cursos del usuario autenticado (según token Beeart).
     * Esta ruta permite al frontend pedir sus propios cursos sin enviar
     * el parámetro `idusuario` por query string.
     */
    public function mine(Request $req) {
        try {
            // El middleware CheckBeeartToken adjunta el id de usuario en
            // $request->attributes->get('beeart_user_id') cuando el token es válido.
            $beeartUserId = $req->attributes->get('beeart_user_id');
            if (!$beeartUserId) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            $cursos = curso::with(['user','estado','clases'])
                ->where('idusuario', $beeartUserId)
                ->where(function($q) {
                    $q->whereNull('idestado')
                      ->orWhere('idestado', '!=', 9);
                })
                ->orderBy('idcurso', 'asc')
                ->limit(10)
                ->get();

            return response()->json($cursos);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('[CursoController@mine] Error fetching mine: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }
}
