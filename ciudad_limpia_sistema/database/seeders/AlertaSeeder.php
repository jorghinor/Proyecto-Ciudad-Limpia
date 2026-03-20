<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Alerta;
use App\Models\Contenedor;

class AlertaSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener algunos contenedores problemáticos
        $contenedorLleno = Contenedor::where('estado', 'lleno')->first();
        $contenedorDanado = Contenedor::where('estado', 'danado')->first();

        if ($contenedorLleno) {
            Alerta::create([
                'tipo' => 'contenedor_lleno',
                'mensaje' => "El contenedor #{$contenedorLleno->id} en {$contenedorLleno->ubicacion} ha alcanzado su capacidad máxima.",
                'entidad_id' => $contenedorLleno->id,
                'entidad_tipo' => 'contenedor',
                'estado' => 'activa',
                'created_at' => now()->subMinutes(10),
            ]);
        }

        if ($contenedorDanado) {
            Alerta::create([
                'tipo' => 'contenedor_danado',
                'mensaje' => "Reporte automático de sensor dañado en contenedor #{$contenedorDanado->id} ({$contenedorDanado->ubicacion}).",
                'entidad_id' => $contenedorDanado->id,
                'entidad_tipo' => 'contenedor',
                'estado' => 'activa',
                'created_at' => now()->subHours(5),
            ]);
        }

        // Alerta de zona crítica sin entidad específica
        Alerta::create([
            'tipo' => 'zona_critica',
            'mensaje' => 'Se detecta acumulación inusual de residuos en el sector del Mercado Central.',
            'entidad_id' => null,
            'entidad_tipo' => null,
            'estado' => 'atendida',
            'created_at' => now()->subDays(1),
        ]);
    }
}
