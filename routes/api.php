<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function (){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('userProfile', [AuthController::class, 'me'])->middleware('auth:api');
});

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

