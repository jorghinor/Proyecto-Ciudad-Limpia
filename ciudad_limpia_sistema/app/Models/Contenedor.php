<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contenedor extends Model
{
    use HasFactory;

    protected $table = 'contenedores';

    protected $fillable = [
        'ubicacion',
        'latitud',
        'longitud',
        'capacidad', // <-- Agregado
        'nivel_llenado',
        'estado',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'nivel_llenado' => 'integer',
        'capacidad' => 'integer',
    ];
}
