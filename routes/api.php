<?php

use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminPetitionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CategoryController;
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
    Route::get('misfirmas', [PetitionController::class, 'peticionesFirmadas']);
    Route::delete('peticiones/{id}', [PetitionController::class,'destroy']);
    Route::put('peticiones/firmar/{id}', [PetitionController::class,'firmar']);
    Route::post('peticiones/{id}', [PetitionController::class,'update']);
    Route::put('peticiones/{id}', [PetitionController::class,'update']);
    Route::put('peticiones/estado/{id}', [PetitionController::class,'cambiarEstado']);
    Route::post('peticiones', [PetitionController::class,'store']);
});

Route::get('categorias', [CategoryController::class, 'index']);

//Rutas admin
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('peticiones', [AdminPetitionController::class, 'index']);
    Route::get('peticiones/meta', [AdminPetitionController::class, 'meta']);
    Route::get('peticiones/{id}', [AdminPetitionController::class, 'show']);
    Route::post('peticiones', [AdminPetitionController::class, 'store']);
    Route::post('peticiones/{id}', [AdminPetitionController::class, 'update']);
    Route::put('peticiones/{id}', [AdminPetitionController::class, 'update']);
    Route::delete('peticiones/{id}', [AdminPetitionController::class, 'destroy']);
    Route::delete('peticiones/file/{fileId}', [AdminPetitionController::class, 'destroyFile']);

    Route::get('usuarios', [AdminUserController::class, 'index']);
    Route::get('usuarios/{id}', [AdminUserController::class, 'show']);
    Route::post('usuarios', [AdminUserController::class, 'store']);
    Route::post('usuarios/{id}', [AdminUserController::class, 'update']);
    Route::put('usuarios/{id}', [AdminUserController::class, 'update']);
    Route::delete('usuarios/{id}', [AdminUserController::class, 'destroy']);

    Route::get('categorias', [AdminCategoryController::class, 'index']);
    Route::post('categorias', [AdminCategoryController::class, 'store']);
    Route::put('categorias/{id}', [AdminCategoryController::class, 'update']);
    Route::delete('categorias/{id}', [AdminCategoryController::class, 'destroy']);
});

