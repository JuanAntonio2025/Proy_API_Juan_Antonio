<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetitionController;

// Rutas Públicas (Login y Registro)
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Rutas Protegidas (Requieren Token válido)
// CAMBIO IMPORTANTE: Cambia 'middleware('api')' por 'middleware('auth:api')'
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

// Ruta de Refresh (Fuera del auth:api estricto)
// Laravel intentará leer el token del header, y si es válido (aunque expirado), lo refrescará.
Route::middleware('api')->post('refresh', [AuthController::class, 'refresh']);

Route::get('peticiones', [PetitionController::class,'index']);
Route::get('peticiones/{id}', [PetitionController::class,'show']);

Route::middleware('auth:api')->group(function () {
    Route::get('mispeticiones', [PetitionController::class, 'listmine']);
    Route::delete('peticiones/{id}', [PetitionController::class,'destroy']);
    Route::put('peticiones/firmar/{id}', [PetitionController::class,'firmar']);
    Route::put('peticiones/{id}', [PetitionController::class,'update']);
    Route::put('peticiones/estado/{id}', [PetitionController::class,'cambiarEstado']);
    Route::post('peticiones', [PetitionController::class,'store']);
});

