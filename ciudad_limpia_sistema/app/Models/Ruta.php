<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';

    protected $fillable = [
        'camion_id',
        'nombre',
        'zona',
        'estado',
        'prioridad',
        'contenedores_ids',
        'distancia_total',
        'tiempo_estimado',
    ];

    protected $casts = [
        'contenedores_ids' => 'array',
        'distancia_total' => 'float',
        'tiempo_estimado' => 'integer',
    ];

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }
}
