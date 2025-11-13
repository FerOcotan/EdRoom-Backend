<?php

namespace App\Http\Controllers;

use App\Models\estado;
use Illuminate\Http\Request;

class EstadoController extends Controller
{
    public function index() {
        try {
            return response()->json(estado::all());
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function show($id) {
        try {
            $e = estado::where('idestado', $id)->first();
            if (!$e) return response()->json(['message' => 'No encontrado'], 404);
            return response()->json($e);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function store(Request $req) {
        $data = $req->validate([
            'estado' => 'required|string',
            'descripcion' => 'nullable|string|max:100',
        ]);
        $e = estado::create($data);
        return response()->json($e, 201);
    }

    public function update(Request $req, $id) {
        $e = estado::where('idestado', $id)->first();
        if (!$e) return response()->json(['message' => 'No encontrado'], 404);
        $e->fill($req->only(['estado','descripcion']));
        $e->save();
        return response()->json($e);
    }

    public function destroy($id) {
        $e = estado::where('idestado', $id)->first();
        if (!$e) return response()->json(['message' => 'No encontrado'], 404);
        $e->delete();
        return response()->json(['deleted' => true]);
    }
}
