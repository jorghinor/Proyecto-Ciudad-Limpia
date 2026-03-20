<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            // Hacemos que el camión sea opcional (nullable) para rutas pendientes de asignación
            $table->foreignId('camion_id')->nullable()->constrained('camiones')->onDelete('cascade');
            $table->string('nombre');
            $table->string('zona');
            $table->string('estado')->default('pendiente'); // pendiente, en_progreso, completada
            $table->string('prioridad')->default('normal'); // baja, normal, alta, urgente
            $table->json('contenedores_ids')->nullable(); // Array de IDs de contenedores
            $table->double('distancia_total')->nullable(); // en kilómetros
            $table->integer('tiempo_estimado')->nullable(); // en minutos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
