<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camion extends Model
{
    use HasFactory;

    protected $table = 'camiones';

    protected $fillable = [
        'placa',
        'conductor',
        'estado',
        'capacidad',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'capacidad' => 'integer',
    ];

    public function rutas()
    {
        return $this->hasMany(Ruta::class);
    }

    public function ubicaciones()
    {
        return $this->hasMany(UbicacionCamion::class);
    }

    public function ultimaUbicacion()
    {
        return $this->hasOne(UbicacionCamion::class)->latest('registrado_en');
    }
}
