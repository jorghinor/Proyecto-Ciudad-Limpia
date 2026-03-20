<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContenedorController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ================= RUTAS PÚBLICAS (PORTAL CIUDADANO) =================
Route::get('/public/camiones', [PublicController::class, 'camiones']); // Cacheado
Route::get('/public/contenedores', [PublicController::class, 'contenedores']); // Cacheado
Route::get('/public/estadisticas', [PublicController::class, 'estadisticas']); // Cacheado

// Reportes con protección Anti-Spam (3 reportes por minuto por IP)
Route::middleware('throttle:public_reports')->post('/public/reportes', [PublicController::class, 'crearReporte']);

// ================= RUTAS DE AUTENTICACIÓN =================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ================= RUTAS PROTEGIDAS (PANEL DE ADMIN) =================
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Admin: Gestión de usuarios
    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::get('/usuarios/{user}', [UserController::class, 'show']);
        Route::put('/usuarios/{user}', [UserController::class, 'update']);
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy']);
        Route::post('/usuarios/{user}/rol', [UserController::class, 'cambiarRol']);
        Route::post('/usuarios/{user}/password', [UserController::class, 'cambiarPassword']);
        Route::get('/usuarios/rol/{rol}', [UserController::class, 'usuariosPorRol']);
        Route::get('/usuarios/buscar/q', [UserController::class, 'buscar']);
    });
    
    // Admin y Operador: Gestión completa
    Route::middleware('role:admin,operador')->group(function () {
        // Contenedores
        Route::resource('contenedores', ContenedorController::class)->parameters([
            'contenedores' => 'contenedor'
        ]);
        Route::post('/contenedores/{contenedor}/nivel', [ContenedorController::class, 'updateNivelLlenado']);
        Route::post('/contenedores/{contenedor}/danado', [ContenedorController::class, 'marcarComoDanado']);
        
        // Camiones
        Route::resource('camiones', CamionController::class)->parameters([
            'camiones' => 'camion'
        ]);
        Route::post('/camiones/{camion}/ubicacion', [CamionController::class, 'updateUbicacion']);
        Route::get('/camiones/{camion}/ubicaciones', [CamionController::class, 'historialUbicaciones']);
        
        // Rutas
        Route::post('/rutas/optimizar', [RutaController::class, 'optimizarRuta']);
        Route::get('/rutas/prioridad/alta', [RutaController::class, 'rutasPorPrioridad']);
        Route::resource('rutas', RutaController::class);
        
        // Alertas
        Route::get('/alertas', [AlertaController::class, 'index']);
        Route::get('/alertas/activas', [AlertaController::class, 'alertasActivas']);
        Route::get('/alertas/{alerta}', [AlertaController::class, 'show']);
        Route::post('/alertas/{alerta}/atender', [AlertaController::class, 'marcarComoAtendida']);
        Route::delete('/alertas/{alerta}', [AlertaController::class, 'destroy']);
        
        // Dashboard
        Route::get('/dashboard/metricas', [DashboardController::class, 'metricas']);
        Route::get('/dashboard/contenedores-por-zona', [DashboardController::class, 'contenedoresPorZona']);
        Route::get('/dashboard/eficiencia-rutas', [DashboardController::class, 'eficienciaRutas']);
        Route::get('/dashboard/actividad-reciente', [DashboardController::class, 'actividadReciente']);
        
        // Reportes (gestión interna)
        Route::resource('reportes', ReporteController::class)->except(['store']);
        Route::get('/reportes/pendientes/lista', [ReporteController::class, 'reportesPendientes']);
        Route::get('/reportes/tipo/{tipo}', [ReporteController::class, 'reportesPorTipo']);
        Route::post('/reportes/{reporte}/resolver', [ReporteController::class, 'marcarComoResuelto']);
    });
    
    // Todos los roles autenticados: Crear reportes internos
    Route::post('/reportes', [ReporteController::class, 'store']);
});
