<?php

namespace App\Http\Controllers;

use App\Models\clase;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index() {
        try {
            return response()->json(clase::with(['curso','estado'])->get());
        } catch (\Exception $e) {
            return response()->json([], 200);
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
