<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReporteController extends Controller
{
    public function index()
    {
        return Reporte::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string', Rule::in(['basura_en_calle', 'contenedor_danado', 'punto_critico'])],
            'descripcion' => 'required|string',
            'ubicacion' => 'required|string|max:255',
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
            'foto' => 'sometimes|string|max:255',
            'ciudadano_nombre' => 'sometimes|string|max:255',
            'ciudadano_contacto' => 'sometimes|string|max:255',
        ]);

        $reporte = Reporte::create($validated);
        return response()->json($reporte, 201);
    }

    public function show(Reporte $reporte)
    {
        return $reporte;
    }

    public function update(Request $request, Reporte $reporte)
    {
        $validated = $request->validate([
            'tipo' => ['sometimes', 'string', Rule::in(['basura_en_calle', 'contenedor_danado', 'punto_critico'])],
            'descripcion' => 'sometimes|string',
            'ubicacion' => 'sometimes|string|max:255',
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
            'foto' => 'sometimes|string|max:255',
            'estado' => ['sometimes', 'string', Rule::in(['pendiente', 'en_proceso', 'resuelto'])],
            'ciudadano_nombre' => 'sometimes|string|max:255',
            'ciudadano_contacto' => 'sometimes|string|max:255',
        ]);

        $reporte->update($validated);
        return response()->json($reporte);
    }

    public function destroy(Reporte $reporte)
    {
        $reporte->delete();
        return response()->json(null, 204);
    }

    public function reportesPendientes()
    {
        $reportes = Reporte::where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($reportes);
    }

    public function reportesPorTipo($tipo)
    {
        $reportes = Reporte::where('tipo', $tipo)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($reportes);
    }

    public function marcarComoResuelto(Reporte $reporte)
    {
        $reporte->update(['estado' => 'resuelto']);
        return response()->json($reporte);
    }
}
