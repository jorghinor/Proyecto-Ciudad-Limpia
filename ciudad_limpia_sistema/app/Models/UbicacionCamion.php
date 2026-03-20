<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UbicacionCamion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_camion';

    protected $fillable = [
        'camion_id',
        'latitud',
        'longitud',
        'registrado_en',
    ];

    protected $casts = [
        'registrado_en' => 'datetime',
    ];

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }
}