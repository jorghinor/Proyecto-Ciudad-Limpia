<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Contenedor;
use App\Models\Reporte;
use App\Http\Requests\StorePublicReporteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    // Obtener ubicaciones de camiones (Cacheado por 15 segundos para no saturar DB)
    public function camiones()
    {
        return Cache::remember('public_camiones', 15, function () {
            return Camion::where('estado', 'en_ruta')
                ->orWhere('estado', 'disponible')
                ->select('id', 'placa', 'latitud', 'longitud', 'estado')
                ->get();
        });
    }

    // Obtener contenedores (Cacheado por 60 segundos)
    public function contenedores()
    {
        return Cache::remember('public_contenedores', 60, function () {
            return Contenedor::select('id', 'ubicacion', 'latitud', 'longitud', 'estado', 'nivel_llenado')->get();
        });
    }

    // Permitir a ciudadanos crear reportes con validación estricta
    public function crearReporte(StorePublicReporteRequest $request)
    {
        // Obtener datos validados
        $validated = $request->validated();

        // Manejo de la imagen (Si se subió)
        if ($request->hasFile('foto')) {
            // Guardar en 'public/reportes_fotos'
            $path = $request->file('foto')->store('reportes_fotos', 'public');
            // Guardar ruta relativa para evitar problemas de permisos/servidor al servir archivos
            $validated['foto'] = $path;
        }

        $validated['estado'] = 'pendiente';
        // Asignar fecha y hora actual si no lo hace el modelo
        $validated['fecha_reporte'] = now();

        $reporte = Reporte::create($validated);

        return response()->json([
            'message' => 'Reporte enviado con éxito. Nuestro equipo lo revisará.',
            'id' => $reporte->id
        ], 201);
    }

    // Estadísticas para el Hero (Cacheado por 5 minutos)
    public function estadisticas()
    {
        return Cache::remember('public_stats', 300, function () {
            return [
                'contenedores_activos' => Contenedor::count(),
                'camiones_ruta' => Camion::where('estado', 'en_ruta')->count(),
                'reportes_resueltos' => Reporte::where('estado', 'resuelto')->count(),
            ];
        });
    }

    // Servir evidencia fotográfica de reportes
    public function verReporteFoto($filename)
    {
        $safeName = basename($filename);
        $path = 'reportes_fotos/' . $safeName;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }
}
