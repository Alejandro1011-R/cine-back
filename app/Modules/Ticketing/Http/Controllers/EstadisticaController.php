<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Controllers;

use App\Modules\Ticketing\Application\Queries\CompraStatisticsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class EstadisticaController extends Controller
{
    public function __construct(
        private readonly CompraStatisticsQuery $statisticsQuery,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('role:SuperAdmin,Admin');
    }

    public function byFecha(string $inicio, string $fin): JsonResponse
    {
        return response()->json($this->statisticsQuery->byFecha($inicio, $fin, request()->user()));
    }

    public function byTipo(string $tipo): JsonResponse
    {
        return response()->json($this->statisticsQuery->byTipo($tipo, request()->user()));
    }

    public function byPelicula(string $metric): JsonResponse
    {
        return response()->json($this->statisticsQuery->byPelicula($metric, request()->user()));
    }

    public function byCliente(string $metric): JsonResponse
    {
        return response()->json($this->statisticsQuery->byCliente($metric, request()->user()));
    }
}
