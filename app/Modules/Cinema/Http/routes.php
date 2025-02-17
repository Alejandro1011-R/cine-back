<?php

declare(strict_types=1);

use App\Modules\Cinema\Http\Controllers\ButacaController;
use App\Modules\Cinema\Http\Controllers\CineController;
use App\Modules\Cinema\Http\Controllers\SalaController;
use App\Modules\Cinema\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('salas', SalaController::class);
Route::apiResource('butacas', ButacaController::class);
Route::get('sesiones', [SesionController::class, 'index']);
Route::post('sesiones', [SesionController::class, 'store']);
Route::get('peliculas/{peliculaId}/sesiones', [SesionController::class, 'byPelicula']);
Route::get('sesiones/{idP}/{idS}/{fecha}', [SesionController::class, 'show']);
Route::delete('sesiones/{idP}/{idS}/{fecha}', [SesionController::class, 'destroy']);
Route::post('cines/{id}/staff', [CineController::class, 'assignStaff']);
Route::delete('cines/{id}/staff/{ci}', [CineController::class, 'detachStaff']);
Route::apiResource('cines', CineController::class);
