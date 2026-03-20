<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruta;
use App\Models\Camion;
use App\Models\Contenedor;

class RutaSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener IDs para relacionar
        $camion1 = Camion::where('placa', '2345-ABC')->first();
        $camion2 = Camion::where('placa', '4567-GHI')->first();

        $contenedoresNorte = Contenedor::where('latitud', '>', -17.38)->pluck('id')->toArray();
        $contenedoresCentro = Contenedor::whereBetween('latitud', [-17.40, -17.38])->pluck('id')->toArray();

        if ($camion1 && count($contenedoresCentro) > 0) {
            Ruta::create([
                'nombre' => 'Ruta Centro Mañana',
                'zona' => 'Distrito 10',
                'estado' => 'en_progreso',
                'prioridad' => 'alta',
                'camion_id' => $camion1->id,
                'contenedores_ids' => $contenedoresCentro, // Array casteado automáticamente por el modelo
            ]);
        }

        if ($camion2 && count($contenedoresNorte) > 0) {
            Ruta::create([
                'nombre' => 'Ruta Norte Tarde',
                'zona' => 'Distrito 12',
                'estado' => 'pendiente',
                'prioridad' => 'normal',
                'camion_id' => $camion2->id,
                'contenedores_ids' => $contenedoresNorte,
            ]);
        }

        // Ruta sin asignar
        Ruta::create([
            'nombre' => 'Ruta Sur Nocturna',
            'zona' => 'Distrito 5',
            'estado' => 'pendiente',
            'prioridad' => 'urgente',
            'camion_id' => null,
            'contenedores_ids' => [],
        ]);
    }
}
