<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\ClienteController;
use App\Modules\Identity\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::apiResource('usuarios', UsuarioController::class)
    ->parameters(['usuarios' => 'ci']);

Route::put('usuarios/{ci}/promote-socio', [UsuarioController::class, 'promoteSocio']);
Route::put('usuarios/{ci}/change-role/{rol}', [UsuarioController::class, 'changeRole']);

Route::apiResource('clientes', ClienteController::class)
    ->parameters(['clientes' => 'ci']);
