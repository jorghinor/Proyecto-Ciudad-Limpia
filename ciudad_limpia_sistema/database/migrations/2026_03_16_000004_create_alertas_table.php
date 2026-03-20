<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // contenedor_lleno, contenedor_danado, zona_critica
            $table->text('mensaje');
            $table->unsignedBigInteger('entidad_id')->nullable(); // ID del contenedor, reporte, etc.
            $table->string('entidad_tipo')->nullable(); // contenedor, reporte
            $table->string('estado')->default('activa'); // activa, atendida, ignorada
            $table->json('canal')->nullable(); // email, sms, dashboard
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
