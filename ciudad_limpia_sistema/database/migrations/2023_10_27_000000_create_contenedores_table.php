<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contenedores', function (Blueprint $table) {
            $table->id();
            $table->string('ubicacion');
            $table->double('latitud', 10, 7); // Precisión para latitud
            $table->double('longitud', 10, 7); // Precisión para longitud
            $table->integer('capacidad')->default(1000); // Capacidad en Litros (Agregado)
            $table->integer('nivel_llenado')->default(0); // 0-100%
            $table->string('estado')->default('vacio'); // 'vacio', 'medio', 'lleno', 'danado'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contenedores');
    }
};
