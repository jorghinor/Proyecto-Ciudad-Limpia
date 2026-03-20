<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Reporte;

class ReporteSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tabla antes de poblar (opcional)
        // DB::table('reportes')->truncate();

        $reportes = [
            [
                'tipo' => 'basura_en_calle',
                'descripcion' => 'Hay un montón de bolsas de basura rotas en la esquina del parque, los perros las están rompiendo.',
                'ubicacion' => 'Av. Heroínas y Ayacucho',
                'latitud' => -17.3935,
                'longitud' => -66.1570,
                'estado' => 'pendiente',
                'foto' => 'https://images.unsplash.com/photo-1605600659908-0ef719419d41?auto=format&fit=crop&w=500&q=60',
                'ciudadano_nombre' => 'Juan Pérez',
                'ciudadano_contacto' => '70712345',
                'created_at' => now()->subHours(2),
            ],
            [
                'tipo' => 'contenedor_danado',
                'descripcion' => 'El contenedor verde tiene la tapa rota y no cierra bien, huele muy mal.',
                'ubicacion' => 'Calle España #450',
                'latitud' => -17.3960,
                'longitud' => -66.1600,
                'estado' => 'en_proceso',
                'foto' => 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=500&q=60',
                'ciudadano_nombre' => 'María Gomez',
                'ciudadano_contacto' => '60698765',
                'created_at' => now()->subDays(1),
            ],
            [
                'tipo' => 'punto_critico',
                'descripcion' => 'Acumulación masiva de escombros y basura. Parece un basural clandestino.',
                'ubicacion' => 'Zona Sur, Mercado Campesino',
                'latitud' => -17.4200,
                'longitud' => -66.1400,
                'estado' => 'pendiente',
                'foto' => null, // Sin foto para probar la interfaz
                'ciudadano_nombre' => null, // Anónimo
                'ciudadano_contacto' => null,
                'created_at' => now()->subMinutes(30),
            ],
            [
                'tipo' => 'basura_en_calle',
                'descripcion' => 'Bolsas dejadas fuera del horario de recolección.',
                'ubicacion' => 'Av. América',
                'latitud' => -17.3800,
                'longitud' => -66.1650,
                'estado' => 'resuelto',
                'foto' => 'https://images.unsplash.com/photo-1597484661643-2f5fef640dd1?auto=format&fit=crop&w=500&q=60',
                'ciudadano_nombre' => 'Carlos D.',
                'ciudadano_contacto' => null,
                'created_at' => now()->subDays(3),
            ],
        ];

        foreach ($reportes as $datos) {
            Reporte::create($datos);
        }
    }
}
