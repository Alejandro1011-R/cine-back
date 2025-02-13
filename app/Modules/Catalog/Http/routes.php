<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\ActorController;
use App\Modules\Catalog\Http\Controllers\GeneroController;
use App\Modules\Catalog\Http\Controllers\PeliculaController;
use Illuminate\Support\Facades\Route;

Route::apiResource('actores', ActorController::class);
Route::apiResource('generos', GeneroController::class);
Route::apiResource('peliculas', PeliculaController::class)->except(['destroy']);
