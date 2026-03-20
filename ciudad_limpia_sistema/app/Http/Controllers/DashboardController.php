<?php

namespace App\Http\Controllers;

use App\Models\Contenedor;
use App\Models\Camion;
use App\Models\Ruta;
use App\Models\Reporte;
use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metricas()
    {
        $contenedoresLlenos = Contenedor::where('estado', 'lleno')->count();
        $contenedoresCasiLlenos = Contenedor::where('estado', 'casi lleno')->count();
        $contenedoresDanados = Contenedor::where('estado', 'danado')->count();
        $totalContenedores = Contenedor::count();

        $rutasActivas = Ruta::where('estado', 'en_progreso')->count();
        $rutasPendientes = Ruta::where('estado', 'pendiente')->count();
        $totalRutas = Ruta::count();

        $reportesPendientes = Reporte::where('estado', 'pendiente')->count();
        $totalReportes = Reporte::count();

        $alertasActivas = Alerta::where('estado', 'activa')->count();

        $camionesDisponibles = Camion::where('estado', 'disponible')->count();
        $camionesEnRuta = Camion::where('estado', 'en_ruta')->count();
        $totalCamiones = Camion::count();

        return response()->json([
            'contenedores' => [
                'llenos' => $contenedoresLlenos,
                'casi_llenos' => $contenedoresCasiLlenos,
                'danados' => $contenedoresDanados,
                'total' => $totalContenedores,
            ],
            'rutas' => [
                'activas' => $rutasActivas,
                'pendientes' => $rutasPendientes,
                'total' => $totalRutas,
            ],
            'reportes' => [
                'pendientes' => $reportesPendientes,
                'total' => $totalReportes,
            ],
            'alertas' => [
                'activas' => $alertasActivas,
            ],
            'camiones' => [
                'disponibles' => $camionesDisponibles,
                'en_ruta' => $camionesEnRuta,
                'total' => $totalCamiones,
            ],
        ]);
    }

    public function contenedoresPorZona()
    {
        $contenedores = Contenedor::selectRaw('SUBSTRING_INDEX(ubicacion, " ", 1) as zona, COUNT(*) as total, SUM(CASE WHEN estado = "lleno" THEN 1 ELSE 0 END) as llenos')
            ->groupBy('zona')
            ->get();

        return response()->json($contenedores);
    }

    public function eficienciaRutas()
    {
        // En PostgreSQL, DATE(created_at) es created_at::date
        $rutas = Ruta::selectRaw('created_at::date as fecha, COUNT(*) as total_rutas, AVG(COALESCE(distancia_total, 0)) as distancia_promedio')
            ->groupByRaw('created_at::date')
            ->orderBy('fecha', 'desc')
            ->limit(7)
            ->get();

        return response()->json($rutas);
    }

    public function actividadReciente()
    {
        $alertas = Alerta::where('estado', 'activa')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $reportes = Reporte::where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'alertas' => $alertas,
            'reportes' => $reportes,
        ]);
    }
}
