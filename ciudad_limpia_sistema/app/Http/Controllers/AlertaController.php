<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Services\AlertaService;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    protected $alertaService;

    public function __construct(AlertaService $alertaService)
    {
        $this->alertaService = $alertaService;
    }

    public function index()
    {
        return Alerta::orderBy('created_at', 'desc')->get();
    }

    public function alertasActivas()
    {
        return $this->alertaService->obtenerAlertasActivas();
    }

    public function show(Alerta $alerta)
    {
        return $alerta;
    }

    public function marcarComoAtendida(Alerta $alerta)
    {
        $this->alertaService->marcarComoAtendida($alerta);
        return response()->json($alerta);
    }

    public function destroy(Alerta $alerta)
    {
        $alerta->delete();
        return response()->json(null, 204);
    }
}
