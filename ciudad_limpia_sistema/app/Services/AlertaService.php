<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Contenedor;

class AlertaService
{
    public function crearAlertaContenedorLleno(Contenedor $contenedor)
    {
        $mensaje = "El contenedor en {$contenedor->ubicacion} está {$contenedor->estado} ({$contenedor->nivel_llenado}%)";
        
        return Alerta::create([
            'tipo' => 'contenedor_lleno',
            'mensaje' => $mensaje,
            'entidad_id' => $contenedor->id,
            'entidad_tipo' => 'contenedor',
            'estado' => 'activa',
            'canal' => ['dashboard', 'email'],
        ]);
    }

    public function crearAlertaContenedorDanado(Contenedor $contenedor)
    {
        $mensaje = "El contenedor en {$contenedor->ubicacion} está dañado y requiere mantenimiento";
        
        return Alerta::create([
            'tipo' => 'contenedor_danado',
            'mensaje' => $mensaje,
            'entidad_id' => $contenedor->id,
            'entidad_tipo' => 'contenedor',
            'estado' => 'activa',
            'canal' => ['dashboard', 'email'],
        ]);
    }

    public function crearAlertaZonaCritica($ubicacion, $descripcion)
    {
        $mensaje = "Zona crítica reportada en {$ubicacion}: {$descripcion}";
        
        return Alerta::create([
            'tipo' => 'zona_critica',
            'mensaje' => $mensaje,
            'estado' => 'activa',
            'canal' => ['dashboard', 'email', 'sms'],
        ]);
    }

    public function obtenerAlertasActivas()
    {
        return Alerta::where('estado', 'activa')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function marcarComoAtendida(Alerta $alerta)
    {
        $alerta->update(['estado' => 'atendida']);
        return $alerta;
    }
}
