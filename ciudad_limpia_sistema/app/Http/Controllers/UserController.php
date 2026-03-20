<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'rol' => ['required', 'string', Rule::in(['admin', 'operador', 'ciudadano'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'rol' => $validated['rol'],
        ]);

        return response()->json($user, 201);
    }

    /**
     * Mostrar un usuario específico
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'rol' => ['sometimes', 'string', Rule::in(['admin', 'operador', 'ciudadano'])],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(User $user)
    {
        // Evitar que un usuario se elimine a sí mismo
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propio usuario'], 403);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Cambiar el rol de un usuario
     */
    public function cambiarRol(Request $request, User $user)
    {
        $validated = $request->validate([
            'rol' => ['required', 'string', Rule::in(['admin', 'operador', 'ciudadano'])],
        ]);

        $user->update(['rol' => $validated['rol']]);

        return response()->json($user);
    }

    /**
     * Cambiar contraseña de un usuario
     */
    public function cambiarPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    /**
     * Listar usuarios por rol
     */
    public function usuariosPorRol($rol)
    {
        $usuarios = User::where('rol', $rol)->get();

        return response()->json($usuarios);
    }

    /**
     * Buscar usuarios
     */
    public function buscar(Request $request)
    {
        $query = $request->get('q');

        $usuarios = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get();

        return response()->json($usuarios);
    }
}
