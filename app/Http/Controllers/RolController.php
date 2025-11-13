<?php

namespace App\Http\Controllers;

use App\Models\rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index() {
        try {
            return response()->json(rol::all());
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function show($id) {
        try {
            $r = rol::where('idrol', $id)->first();
            if (!$r) return response()->json(['message' => 'No encontrado'], 404);
            return response()->json($r);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function store(Request $req) {
        $data = $req->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string|max:400',
        ]);
        $r = rol::create($data);
        return response()->json($r, 201);
    }

    public function update(Request $req, $id) {
        $r = rol::where('idrol', $id)->first();
        if (!$r) return response()->json(['message' => 'No encontrado'], 404);
        $r->fill($req->only(['nombre','descripcion']));
        $r->save();
        return response()->json($r);
    }

    public function destroy($id) {
        $r = rol::where('idrol', $id)->first();
        if (!$r) return response()->json(['message' => 'No encontrado'], 404);
        $r->delete();
        return response()->json(['deleted' => true]);
    }
}
