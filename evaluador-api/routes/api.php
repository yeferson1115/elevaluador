<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\IngresoController;
use App\Http\Controllers\API\IngresoImageController;
use App\Http\Controllers\API\AvaluoController;
use App\Http\Controllers\API\InspeccionController;
use App\Http\Controllers\API\ValoresRepuestoController;
use App\Http\Controllers\API\FasecoldaController;
use App\Http\Controllers\API\ReprocesarAvaluosController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'me']);

    // Solo para usuarios con rol "admin"
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin-only', fn () => response()->json(['message' => 'Bienvenido administrador']));
    });

    // Solo para usuarios con permiso "view reports"
    Route::middleware('permission:view reports')->group(function () {
        Route::get('/reports', fn () => response()->json(['message' => 'Aquí están tus reportes']));
    });
    
    // Rutas de Roles y Usuarios
    Route::get('/roles/datatables', [RoleController::class, 'index']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);
    Route::get('permissions', [RoleController::class, 'allPermissions']);
    Route::apiResource('users', UserController::class); 
    Route::get('/getroles', [RoleController::class, 'getroles']);
    
    // Rutas principales
    Route::apiResource('ingreso', IngresoController::class);
    Route::apiResource('avaluo', AvaluoController::class);
    Route::patch('avaluo/{avaluo}/cierre', [AvaluoController::class, 'actualizarCierre']);
    Route::apiResource('inspeccion', InspeccionController::class);
    
    // CRUD completo para ValoresRepuesto
    Route::apiResource('valores-repuesto', ValoresRepuestoController::class);
    
    // Rutas adicionales para ValoresRepuesto
    Route::prefix('valores-repuestos')->group(function () {
        Route::get('/tipos', [ValoresRepuestoController::class, 'getTipos']);
        Route::get('/buscar', [ValoresRepuestoController::class, 'buscarPorCilindraje']);
        Route::get('/buscar-rango', [ValoresRepuestoController::class, 'buscarPorRango']);
        Route::get('/clase/{clase}', [ValoresRepuestoController::class, 'porClase']);
    });

    // Rutas de imágenes
    Route::prefix('ingresos-imagenes/{avaluoId}')->group(function () {
        Route::get('/imagenes', [IngresoImageController::class, 'index']);
        Route::post('/imagenes', [IngresoImageController::class, 'store']);
        Route::post('/imagenes/delete', [IngresoImageController::class, 'delete']);
        Route::post('/imagenes/reorder', [IngresoImageController::class, 'reorder']);
        Route::post('/imagenes/rotate', [IngresoImageController::class, 'rotate']);
    });
    
    // Importaciones
    Route::post('ingreso/import', [IngresoController::class, 'import']);
    Route::post('ingreso/importmovilidad', [IngresoController::class, 'importmovilidad']);

    // Rutas Fasecolda
    Route::prefix('fasecolda')->group(function () {
        Route::post('/import', [FasecoldaController::class, 'import']);
        Route::get('/', [FasecoldaController::class, 'index']);
        Route::put('/{codigo}', [FasecoldaController::class, 'update']);
        Route::delete('/{codigo}', [FasecoldaController::class, 'destroy']);
        Route::get('/{codigo}/registros', [FasecoldaController::class, 'getRegistros']);
        Route::put('/registro/{id}', [FasecoldaController::class, 'updateRegistro']);
        Route::delete('/registro/{id}', [FasecoldaController::class, 'destroyRegistro']);
        Route::get('/{codigo}', [FasecoldaController::class, 'getValores']);
        Route::get('/{codigo}/modelo/{modelo}', [FasecoldaController::class, 'buscarPorModelo']);
    });
  
    // Reprocesar avalúos
    Route::get('/avaluos/reprocesar', [ReprocesarAvaluosController::class, 'reprocesar']);
    Route::get('/avaluos/reprocesar/status', [ReprocesarAvaluosController::class, 'status']);
    Route::post('/avaluos/bulk-update-compact', [AvaluoController::class, 'bulkUpdateCompact']);

    // Exportaciones
    Route::get('/ingresos/export-sec-bog', [IngresoController::class, 'exportSecBog']);
    Route::get('/ingresos/export-certificados-zip', [IngresoController::class, 'exportCertificadosZipMejorado']);
    Route::post('/ingresos/export-certificados-zip-background', [IngresoController::class, 'exportCertificadosZipBackground']);
});

// Rutas públicas
Route::middleware('auth:api')->post('/refresh', [AuthController::class, 'refresh']);
Route::get('avaluos/{id}/pdf', [AvaluoController::class, 'generarPdf']);
