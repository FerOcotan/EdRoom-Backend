<?php

namespace App\Http\Controllers;

use App\Models\soliestudiante;
use Illuminate\Http\Request;

class SoliEstudianteController extends Controller
{
    public function index() {
        try {
            return response()->json(soliestudiante::with(['estudiante','curso','estado'])->get());
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
}
