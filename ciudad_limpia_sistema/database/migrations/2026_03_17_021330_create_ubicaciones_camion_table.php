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
          Schema::create('ubicaciones_camion', function (Blueprint $table) {
              $table->id();
              $table->foreignId('camion_id')->constrained('camiones')->onDelete('cascade');
              $table->decimal('latitud', 10, 8);
              $table->decimal('longitud', 11, 8);
              $table->timestamp('registrado_en');
              $table->timestamps();
          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_camion');
    }
};
