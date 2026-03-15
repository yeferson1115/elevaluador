<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AvaluoController;
use App\Http\Controllers\API\ReprocesarAvaluosController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('reprocesar/sec-bogota', [AvaluoController::class, 'reprocesarSecBogota']);
    
    // Ruta para reprocesar un avalúo específico
Route::get('reprocesar/{id}', [AvaluoController::class, 'reprocesarIndividual']);

Route::get('/indexv2', [AvaluoController::class, 'indexv2'])->name('indexv2');
Route::get('/clear-cache', function () {
  echo Artisan::call('config:clear');
  echo Artisan::call('config:cache');
  echo Artisan::call('cache:clear');
  echo Artisan::call('route:clear');
  echo Artisan::call('view:clear');
});


