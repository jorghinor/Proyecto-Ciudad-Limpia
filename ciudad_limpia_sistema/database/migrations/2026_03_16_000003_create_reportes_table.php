<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // basura_en_calle, contenedor_danado, punto_critico
            $table->text('descripcion');
            $table->string('ubicacion');
            $table->double('latitud', 10, 7)->nullable();
            $table->double('longitud', 10, 7)->nullable();
            $table->string('foto')->nullable(); // URL de la foto
            $table->string('estado')->default('pendiente'); // pendiente, en_proceso, resuelto
            $table->string('ciudadano_nombre')->nullable();
            $table->string('ciudadano_contacto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
