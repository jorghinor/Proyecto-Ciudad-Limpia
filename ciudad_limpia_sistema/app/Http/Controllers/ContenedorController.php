<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Contenedor;
use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContenedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Contenedor::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ubicacion' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'nivel_llenado' => 'sometimes|integer|min:0|max:100',
            'estado' => ['sometimes', 'string', Rule::in(['vacio', 'medio', 'casi lleno', 'lleno', 'danado'])],
        ]);

        $contenedor = Contenedor::create($validated);

        return response()->json($contenedor, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Contenedor $contenedor)
    {
        return $contenedor;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contenedor $contenedor)
    {
        $validated = $request->validate([
            'ubicacion' => 'sometimes|string|max:255',
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
            'nivel_llenado' => 'sometimes|integer|min:0|max:100',
            'estado' => ['sometimes', 'string', Rule::in(['vacio', 'medio', 'casi lleno', 'lleno', 'danado'])],
        ]);

        $contenedor->update($validated);

        return response()->json($contenedor);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contenedor $contenedor)
    {
        $contenedor->delete();

        return response()->json(null, 204);
    }

    /**
     * Update the level of the specified resource in storage.
     */
    public function updateNivelLlenado(Request $request, Contenedor $contenedor)
    {
        $validated = $request->validate([
            'nivel_llenado' => 'required|integer|min:0|max:100',
        ]);

        $nivel_llenado = $validated['nivel_llenado'];

        if ($nivel_llenado <= 30) {
            $estado = 'vacio';
        } elseif ($nivel_llenado <= 70) {
            $estado = 'medio';
        } elseif ($nivel_llenado <= 90) {
            $estado = 'casi lleno';
        } else {
            $estado = 'lleno';
        }

        $contenedor->update([
            'nivel_llenado' => $nivel_llenado,
            'estado' => $estado,
        ]);

        // Crear alerta si el contenedor está lleno
        if ($estado === 'lleno') {
            // Verificar si ya existe una alerta activa para este contenedor
            $existeAlerta = Alerta::where('entidad_id', $contenedor->id)
                ->where('entidad_tipo', 'contenedor')
                ->where('tipo', 'contenedor_lleno')
                ->where('estado', 'activa')
                ->exists();

            if (!$existeAlerta) {
                $alertaService = new AlertaService();
                $alertaService->crearAlertaContenedorLleno($contenedor);
            }
        }

        return response()->json($contenedor);
    }

    /**
     * Mark container as damaged and create alert.
     */
    public function marcarComoDanado(Request $request, Contenedor $contenedor)
    {
        $contenedor->update(['estado' => 'danado']);

        // Verificar si ya existe una alerta activa para este contenedor
        $existeAlerta = Alerta::where('entidad_id', $contenedor->id)
            ->where('entidad_tipo', 'contenedor')
            ->where('tipo', 'contenedor_danado')
            ->where('estado', 'activa')
            ->exists();

        if (!$existeAlerta) {
            $alertaService = new AlertaService();
            $alertaService->crearAlertaContenedorDanado($contenedor);
        }

        return response()->json($contenedor);
    }
}
