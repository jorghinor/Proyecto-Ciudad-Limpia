<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios
        User::firstOrCreate(
            ['email' => 'admin@ciudadlimpia.com'],
            ['name' => 'Administrador', 'password' => Hash::make('password123'), 'rol' => 'admin']
        );

        User::firstOrCreate(
            ['email' => 'operador@ciudadlimpia.com'],
            ['name' => 'Operador', 'password' => Hash::make('password123'), 'rol' => 'operador']
        );

        User::firstOrCreate(
            ['email' => 'ciudadano@ciudadlimpia.com'],
            ['name' => 'Ciudadano', 'password' => Hash::make('password123'), 'rol' => 'ciudadano']
        );

        // 2. Datos Maestros
        $this->call([
            CamionSeeder::class,
            ContenedorSeeder::class,
            RutaSeeder::class,
            ReporteSeeder::class,
            AlertaSeeder::class, // <-- Nuevo
        ]);
    }
}
