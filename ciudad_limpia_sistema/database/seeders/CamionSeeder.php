<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Camion;

class CamionSeeder extends Seeder
{
    public function run(): void
    {
        $camiones = [
            ['placa' => '2345-ABC', 'capacidad' => 5000, 'conductor' => 'Juan Mamani', 'estado' => 'en_ruta', 'latitud' => -17.3935, 'longitud' => -66.1570],
            ['placa' => '1290-DEF', 'capacidad' => 8000, 'conductor' => 'Pedro Quispe', 'estado' => 'disponible', 'latitud' => -17.3850, 'longitud' => -66.1600],
            ['placa' => '4567-GHI', 'capacidad' => 5000, 'conductor' => 'Carlos Lopez', 'estado' => 'en_ruta', 'latitud' => -17.4000, 'longitud' => -66.1500],
            ['placa' => '8901-JKL', 'capacidad' => 10000, 'conductor' => 'Luis Fernandez', 'estado' => 'mantenimiento', 'latitud' => null, 'longitud' => null],
            ['placa' => '3456-MNO', 'capacidad' => 6000, 'conductor' => 'Roberto Diaz', 'estado' => 'disponible', 'latitud' => -17.4100, 'longitud' => -66.1450],
        ];

        foreach ($camiones as $c) {
            Camion::create($c);
        }
    }
}
