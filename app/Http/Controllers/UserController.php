<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Actualizar el perfil del usuario autenticado (name)
     */
    public function update(Request $request)
    {
        $userId = $request->attributes->get('beeart_user_id');
        if (!$userId) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:6',
            'current_password' => 'required_with:password|string',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->name = $data['name'];
        if (array_key_exists('password', $data) && $data['password']) {
            // verify current password
            if (!isset($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return response()->json(['message' => 'Contraseña actual incorrecta'], 422);
            }
            // User model casts password => 'hashed' so assignment will hash
            $user->password = $data['password'];
        }
        $user->save();

        return response()->json(['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    }

    /**
     * Devolver el perfil del usuario autenticado.
     * Mover aquí la lógica que antes vivía en routes/web.php para mantener controladores limpios.
     */
    public function me(Request $request)
    {
        $userId = $request->attributes->get('beeart_user_id');
        if (!$userId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Aseguramos que idrol se exponga y convierta a entero si existe
        $idrol = $user->idrol !== null ? (int) $user->idrol : null;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'idrol' => $idrol,
        ]);
    }

    /**
     * Listar usuarios (solo admin/idrol=1)
     */
    public function index(Request $request)
    {
        $userId = $request->attributes->get('beeart_user_id');
        if (!$userId) return response()->json(['message' => 'No autenticado'], 401);

        $me = User::find($userId);
        if (!$me) return response()->json(['message' => 'Usuario no encontrado'], 404);

        if ((int)($me->idrol ?? 0) !== 1) {
            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        $users = User::all()->map(function($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'idrol' => $u->idrol ?? null,
            ];
        });

        return response()->json($users);
    }

    /**
     * Mostrar un usuario por id (admin)
     */
    public function show(Request $request, $id)
    {
        $userId = $request->attributes->get('beeart_user_id');
        if (!$userId) return response()->json(['message' => 'No autenticado'], 401);

        $me = User::find($userId);
        if (!$me) return response()->json(['message' => 'Usuario no encontrado'], 404);
        if ((int)($me->idrol ?? 0) !== 1) return response()->json(['message' => 'Acceso denegado'], 403);

        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'idrol' => $u->idrol ?? null,
        ]);
    }

    /**
     * Actualizar usuario por id (admin)
     */
    public function updateUser(Request $request, $id)
    {
        $userId = $request->attributes->get('beeart_user_id');
        if (!$userId) return response()->json(['message' => 'No autenticado'], 401);

        $me = User::find($userId);
        if (!$me) return response()->json(['message' => 'Usuario no encontrado'], 404);
        if ((int)($me->idrol ?? 0) !== 1) return response()->json(['message' => 'Acceso denegado'], 403);

        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        $data = $request->only(['name', 'email', 'idrol']);
        if (isset($data['name'])) $u->name = $data['name'];
        if (isset($data['email'])) $u->email = $data['email'];
        if (isset($data['idrol'])) $u->idrol = $data['idrol'];
        $u->save();

        return response()->json(['message' => 'Actualizado', 'user' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'idrol' => $u->idrol]]);
    }

    /**
     * Eliminar usuario por id (admin)
     */
    public function destroy(Request $request, $id)
    {
        $userId = $request->attributes->get('beeart_user_id');
        if (!$userId) return response()->json(['message' => 'No autenticado'], 401);

        $me = User::find($userId);
        if (!$me) return response()->json(['message' => 'Usuario no encontrado'], 404);
        if ((int)($me->idrol ?? 0) !== 1) return response()->json(['message' => 'Acceso denegado'], 403);

        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        $u->delete();
        return response()->json(['deleted' => true]);
    }
}
