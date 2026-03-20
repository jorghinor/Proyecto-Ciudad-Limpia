<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use App\Models\Camion;
use App\Models\Contenedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RutaController extends Controller
{
    public function index()
    {
        return Ruta::with('camion')->orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'camion_id' => 'nullable|exists:camiones,id',
            'nombre' => 'required|string|max:255',
            'zona' => 'required|string|max:255',
            'estado' => ['sometimes', 'string', Rule::in(['pendiente', 'en_progreso', 'completada'])],
            'prioridad' => ['sometimes', 'string', Rule::in(['baja', 'normal', 'alta', 'urgente'])],
            'contenedores_ids' => 'sometimes|array',
            'contenedores_ids.*' => 'exists:contenedores,id',
        ]);

        $ruta = Ruta::create($validated);
        return response()->json($ruta->load('camion'), 201);
    }

    public function show(Ruta $ruta)
    {
        return $ruta->load('camion');
    }

    public function update(Request $request, Ruta $ruta)
    {
        $validated = $request->validate([
            'camion_id' => 'nullable|exists:camiones,id',
            'nombre' => 'sometimes|string|max:255',
            'zona' => 'sometimes|string|max:255',
            'estado' => ['sometimes', 'string', Rule::in(['pendiente', 'en_progreso', 'completada'])],
            'prioridad' => ['sometimes', 'string', Rule::in(['baja', 'normal', 'alta', 'urgente'])],
            'contenedores_ids' => 'sometimes|array',
            'contenedores_ids.*' => 'exists:contenedores,id',
        ]);

        $ruta->update($validated);
        return response()->json($ruta->load('camion'));
    }

    public function destroy(Ruta $ruta)
    {
        $ruta->delete();
        return response()->json(null, 204);
    }

    public function optimizarRuta(Request $request)
    {
        $validated = $request->validate([
            'camion_id' => 'required|exists:camiones,id',
            'zona' => 'nullable|string', // Opcional, si no se envía busca cerca del camión
        ]);

        $camion = Camion::find($validated['camion_id']);
        
        // Coordenadas de inicio (Camión o Plaza Principal Cochabamba por defecto)
        $latInicio = $camion->latitud ?? -17.3938;
        $lngInicio = $camion->longitud ?? -66.1570;

        // 1. Obtener contenedores candidatos
        // Prioridad: Llenos/Casi Llenos. Si hay zona, filtrar por texto.
        // Si no, buscar los más cercanos (radio 5km).
        $query = Contenedor::whereIn('estado', ['lleno', 'casi lleno', 'medio']);

        if (!empty($validated['zona'])) {
            $query->where('ubicacion', 'like', '%' . $validated['zona'] . '%');
        } else {
            // Filtrado simple por "caja" de coordenadas (aprox +/- 0.05 grados ~ 5km)
            $query->whereBetween('latitud', [$latInicio - 0.05, $latInicio + 0.05])
                  ->whereBetween('longitud', [$lngInicio - 0.05, $lngInicio + 0.05]);
        }

        $contenedores = $query->get();

        // Si no hay urgentes, buscar cualquiera en la zona para rellenar ruta
        if ($contenedores->count() < 3) {
            $contenedores = Contenedor::where('ubicacion', 'like', '%' . ($validated['zona'] ?? '') . '%')
                ->limit(15) // Límite razonable
                ->get();
        }

        if ($contenedores->isEmpty()) {
            return response()->json(['message' => 'No se encontraron contenedores para optimizar en esta área.'], 422);
        }

        // 2. Ejecutar Algoritmo de Optimización (Nearest Neighbor + Capacidad)
        $rutaOptimizada = $this->calcularRutaOptima($latInicio, $lngInicio, $contenedores, $camion->capacidad);

        if (empty($rutaOptimizada['contenedores_ids'])) {
            return response()->json(['message' => 'No se pudo generar una ruta viable.'], 422);
        }

        // 3. Guardar Ruta
        $nombreRuta = 'Ruta Optimizada ' . ($validated['zona'] ?? 'GPS') . ' - ' . now()->format('d/m H:i');

        $ruta = Ruta::create([
            'camion_id' => $camion->id,
            'nombre' => $nombreRuta,
            'zona' => $validated['zona'] ?? 'Zona GPS',
            'prioridad' => 'alta',
            'estado' => 'pendiente',
            'contenedores_ids' => $rutaOptimizada['contenedores_ids'],
            'distancia_total' => $rutaOptimizada['distancia_total'],
            'tiempo_estimado' => $rutaOptimizada['tiempo_estimado'],
        ]);

        return response()->json($ruta->load('camion'), 201);
    }

    /**
     * Algoritmo Heurístico: Vecino Más Cercano con restricción de capacidad
     */
    private function calcularRutaOptima($latInicio, $lngInicio, $contenedores, $capacidadCamion)
    {
        $ordenados = [];
        $pendientes = $contenedores->toArray();
        
        $latActual = $latInicio;
        $lngActual = $lngInicio;
        
        $distanciaTotal = 0;
        $cargaActual = 0; // Litros o Kg estimados
        $capacidadMaxima = $capacidadCamion > 0 ? $capacidadCamion : 5000; // Default si no tiene dato

        while (!empty($pendientes)) {
            $masCercano = null;
            $distanciaMinima = PHP_FLOAT_MAX;
            $indiceCercano = -1;
            
            foreach ($pendientes as $index => $c) {
                // Cálculo de distancia Haversine
                $distancia = $this->calcularDistancia($latActual, $lngActual, $c['latitud'], $c['longitud']);
                
                // Priorizar contenedores más llenos ponderando la distancia
                // (Si está muy lleno, "acortamos" virtualmente su distancia para ir antes)
                $pesoPrioridad = 1;
                if ($c['estado'] === 'lleno') $pesoPrioridad = 0.6; // 40% más "cerca"
                if ($c['estado'] === 'casi lleno') $pesoPrioridad = 0.8;

                $distanciaPonderada = $distancia * $pesoPrioridad;

                if ($distanciaPonderada < $distanciaMinima) {
                    $distanciaMinima = $distancia; // Guardamos la real para el total
                    $masCercano = $c;
                    $indiceCercano = $index;
                }
            }
            
            if ($masCercano) {
                // Verificar capacidad del camión
                $cargaContenedor = ($masCercano['capacidad'] ?? 1000) * (($masCercano['nivel_llenado'] ?? 50) / 100);

                if (($cargaActual + $cargaContenedor) <= $capacidadMaxima) {
                    $ordenados[] = $masCercano['id'];
                    $distanciaTotal += $distanciaMinima;
                    $cargaActual += $cargaContenedor;

                    // Moverse al nuevo punto
                    $latActual = $masCercano['latitud'];
                    $lngActual = $masCercano['longitud'];
                } else {
                    // Camión lleno, detener ruta aquí (o ignorar este contenedor y buscar uno más pequeño)
                    // Por simplicidad del MVP, detenemos la optimización aquí.
                    break;
                }

                unset($pendientes[$indiceCercano]);
                $pendientes = array_values($pendientes);
            }
        }

        // Velocidad promedio camión urbano: 25 km/h
        $tiempoEstimado = ($distanciaTotal / 25) * 60; // Minutos
        // Agregar tiempo de recolección (3 min por contenedor)
        $tiempoEstimado += (count($ordenados) * 3);

        return [
            'contenedores_ids' => $ordenados,
            'distancia_total' => round($distanciaTotal, 2),
            'tiempo_estimado' => round($tiempoEstimado),
        ];
    }

    private function calcularDistancia($lat1, $lng1, $lat2, $lng2)
    {
        $radioTierra = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $radioTierra * $c;
    }

    public function rutasPorPrioridad()
    {
        return Ruta::with('camion')
            ->whereIn('prioridad', ['alta', 'urgente'])
            ->where('estado', '!=', 'completada')
            ->orderByRaw("FIELD(prioridad, 'urgente', 'alta', 'normal', 'baja')")
            ->get();
    }
}
