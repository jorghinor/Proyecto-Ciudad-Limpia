<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Reporte extends Model
{
    use HasFactory;

    protected $table = 'reportes';

    protected $fillable = [
        'tipo',
        'descripcion',
        'ubicacion',
        'latitud',
        'longitud',
        'foto',
        'estado',
        'ciudadano_nombre',
        'ciudadano_contacto',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    protected $appends = [
        'foto_url',
    ];

    public function getFotoUrlAttribute()
    {
        if (!$this->foto) {
            return null;
        }

        $filename = basename($this->foto);
        $path = 'reportes_fotos/' . $filename;

        if (Storage::disk('public')->exists($path)) {
            return url('/reportes_fotos/' . $filename);
        }

        return $this->foto;
    }
}
