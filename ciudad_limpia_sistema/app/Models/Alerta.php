<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas';

    protected $fillable = [
        'tipo',
        'mensaje',
        'entidad_id',
        'entidad_tipo',
        'estado',
        'canal',
    ];

    protected $casts = [
        'canal' => 'array',
    ];
}
