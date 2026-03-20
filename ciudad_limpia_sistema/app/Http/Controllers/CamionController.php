<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CamionController extends Controller
{
    public function index()
    {
        return Camion::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'placa' => 'required|string|unique:camiones',
            'conductor' => 'required|string|max:255',
            'capacidad' => 'required|integer|min:1',
            'estado' => ['sometimes', 'string', Rule::in(['disponible', 'en_ruta', 'mantenimiento'])],
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
        ]);

        $camion = Camion::create($validated);
        return response()->json($camion, 201);
    }

    public function show(Camion $camion)
    {
        return $camion;
    }

    public function update(Request $request, Camion $camion)
    {
        $validated = $request->validate([
            'placa' => ['sometimes', 'string', Rule::unique('camiones')->ignore($camion->id)],
            'conductor' => 'sometimes|string|max:255',
            'capacidad' => 'sometimes|integer|min:1',
            'estado' => ['sometimes', 'string', Rule::in(['disponible', 'en_ruta', 'mantenimiento'])],
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
        ]);

        $camion->update($validated);
        return response()->json($camion);
    }

    public function destroy(Camion $camion)
    {
        $camion->delete();
        return response()->json(null, 204);
    }

     public function updateUbicacion(Request $request, Camion $camion)
     {
        $validated = $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        $ubicacion = $camion->ubicaciones()->create([
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
            'registrado_en' => now(),
        ]);

        $camion->update([
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
        ]);

        return response()->json($ubicacion, 201);
     }

    public function historialUbicaciones(Camion $camion)
    {
        $historial = $camion->ubicaciones()
            ->orderBy('registrado_en', 'desc')
            ->limit(100)
            ->get();

        return response()->json($historial);
    }
}
