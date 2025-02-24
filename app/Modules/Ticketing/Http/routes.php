<?php

declare(strict_types=1);

use App\Modules\Ticketing\Http\Controllers\CompraController;
use App\Modules\Ticketing\Http\Controllers\DescuentoController;
use App\Modules\Ticketing\Http\Controllers\EstadisticaController;
use App\Modules\Ticketing\Http\Controllers\PagoController;
use Illuminate\Support\Facades\Route;

Route::get('compras', [CompraController::class, 'index']);
Route::get('compras/{id}', [CompraController::class, 'show']);
Route::post('compras/tarjeta', [CompraController::class, 'compraByTarjeta']);
Route::post('compras/taquilla', [CompraController::class, 'compraByTaquilla']);
Route::post('compras/puntos', [CompraController::class, 'compraByPuntos']);
Route::delete('compras/{idP}/{idS}/{fecha}/{ci}/{idPg}', [CompraController::class, 'destroy']);

Route::get('estadisticas/by-fecha/{inicio}/{fin}', [EstadisticaController::class, 'byFecha']);
Route::get('estadisticas/by-tipo/{tipo}', [EstadisticaController::class, 'byTipo']);
Route::get('estadisticas/by-pelicula/{metric}', [EstadisticaController::class, 'byPelicula']);
Route::get('estadisticas/by-cliente/{metric}', [EstadisticaController::class, 'byCliente']);

Route::get('pagos', [PagoController::class, 'index']);
Route::get('pagos/{id}', [PagoController::class, 'show']);
Route::delete('pagos/{id}', [PagoController::class, 'destroy']);

Route::get('efectivos', [PagoController::class, 'efectivosIndex']);
Route::get('efectivos/{id}', [PagoController::class, 'efectivosShow']);
Route::post('efectivos', [PagoController::class, 'technicalWrite']);
Route::delete('efectivos/{id}', [PagoController::class, 'destroy']);

Route::get('puntos-pagos', [PagoController::class, 'puntosIndex']);
Route::get('puntos-pagos/{id}', [PagoController::class, 'puntosShow']);
Route::post('puntos-pagos', [PagoController::class, 'technicalWrite']);
Route::delete('puntos-pagos/{id}', [PagoController::class, 'destroy']);

Route::get('web-payments', [PagoController::class, 'webPaymentsIndex']);
Route::get('web-payments/{id}', [PagoController::class, 'webPaymentsShow']);
Route::post('web-payments', [PagoController::class, 'technicalWrite']);
Route::delete('web-payments/{id}', [PagoController::class, 'destroy']);

Route::get('descuentos', [DescuentoController::class, 'index']);
Route::get('descuentos/{id}', [DescuentoController::class, 'show']);
Route::post('descuentos', [DescuentoController::class, 'store']);
Route::put('descuentos/{id}', [DescuentoController::class, 'update']);
Route::delete('descuentos/{id}', [DescuentoController::class, 'destroy']);
