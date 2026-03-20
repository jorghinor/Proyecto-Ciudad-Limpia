<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contenedor;

class ContenedorSeeder extends Seeder
{
    public function run(): void
    {
        $contenedores = [
            // Zona Norte
            ['ubicacion' => 'Av. America y Libertador', 'latitud' => -17.3750, 'longitud' => -66.1600, 'capacidad' => 1000, 'nivel_llenado' => 20, 'estado' => 'vacio'],
            ['ubicacion' => 'Plaza Cala Cala', 'latitud' => -17.3700, 'longitud' => -66.1650, 'capacidad' => 1000, 'nivel_llenado' => 85, 'estado' => 'lleno'],
            ['ubicacion' => 'Parque Fidel Anze', 'latitud' => -17.3680, 'longitud' => -66.1550, 'capacidad' => 1000, 'nivel_llenado' => 45, 'estado' => 'medio'],

            // Zona Centro
            ['ubicacion' => 'Plaza Principal 14 de Septiembre', 'latitud' => -17.3938, 'longitud' => -66.1570, 'capacidad' => 1000, 'nivel_llenado' => 90, 'estado' => 'lleno'],
            ['ubicacion' => 'Av. Heroinas y Ayacucho', 'latitud' => -17.3920, 'longitud' => -66.1580, 'capacidad' => 1000, 'nivel_llenado' => 60, 'estado' => 'casi lleno'],
            ['ubicacion' => 'Calle España y Ecuador', 'latitud' => -17.3950, 'longitud' => -66.1600, 'capacidad' => 1000, 'nivel_llenado' => 10, 'estado' => 'vacio'],
            ['ubicacion' => 'Av. San Martin y Aroma', 'latitud' => -17.3980, 'longitud' => -66.1560, 'capacidad' => 1000, 'nivel_llenado' => 95, 'estado' => 'lleno'],

            // Zona Sur
            ['ubicacion' => 'Av. 6 de Agosto y Barrientos', 'latitud' => -17.4100, 'longitud' => -66.1500, 'capacidad' => 1000, 'nivel_llenado' => 80, 'estado' => 'casi lleno'],
            ['ubicacion' => 'Mercado Calatayud', 'latitud' => -17.4050, 'longitud' => -66.1550, 'capacidad' => 1000, 'nivel_llenado' => 100, 'estado' => 'lleno'],
            ['ubicacion' => 'Av. Panamericana', 'latitud' => -17.4200, 'longitud' => -66.1450, 'capacidad' => 1000, 'nivel_llenado' => 30, 'estado' => 'medio'],

            // Zona Este
            ['ubicacion' => 'Av. Villazon y Quintanilla', 'latitud' => -17.3850, 'longitud' => -66.1300, 'capacidad' => 1000, 'nivel_llenado' => 50, 'estado' => 'medio'],
            ['ubicacion' => 'Jardin Botanico', 'latitud' => -17.3800, 'longitud' => -66.1400, 'capacidad' => 1000, 'nivel_llenado' => 0, 'estado' => 'danado'],
        ];

        foreach ($contenedores as $c) {
            Contenedor::create($c);
        }
    }
}
