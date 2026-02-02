<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Rutas para el JWT
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function (){
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('userProfile', [AuthController::class, 'me']);
});

// Rutas para las peticiones
Route::controller(\App\Http\Controllers\PetitionController::class)->group(function () {
    Route::get('peticiones', 'index');
    Route::get('mispeticiones', 'listmine');
    Route::get('peticiones/{id}', 'show');
    Route::delete('peticiones/{id}', 'delete');
    Route::put('peticiones/firmar/{id}', 'firmar');
    Route::put('peticiones/{id}', 'update');
    Route::put('peticiones/estado/{id}', 'cambiarEstado');
    Route::post('peticiones', 'store');
});

