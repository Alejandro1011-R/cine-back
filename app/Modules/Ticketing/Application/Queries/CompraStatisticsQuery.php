<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Queries;

use App\Modules\Catalog\Http\Resources\PeliculaResource;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Http\Resources\ClienteResource;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Domain\Services\CompraStatisticsCalculator;
use App\Modules\Ticketing\Http\Resources\CompraResource;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;

final class CompraStatisticsQuery
{
    public function __construct(
        private readonly CompraStatisticsCalculator $calculator,
    ) {}

    public function byFecha(string $inicio, string $fin, Usuario $user): array
    {
        $compras = $this->comprasQuery($user)
            ->whereBetween('fecha_de_compra', [$inicio, $fin])
            ->get();

        $stats = $this->calculator->calculate($compras);
        $stats['registro_compra'] = CompraResource::collection($compras)->resolve();

        return $stats;
    }

    public function byTipo(string $tipo, Usuario $user): array
    {
        return CompraResource::collection($this->comprasQuery($user)->where('tipo', $tipo)->get())->resolve();
    }

    public function byPelicula(string $metric, Usuario $user): array
    {
        $results = [];
        $peliculaIds = $this->comprasQuery($user)->select('id_p')->distinct()->pluck('id_p');

        foreach (Pelicula::whereIn('id_p', $peliculaIds)->get() as $pelicula) {
            $compras = $this->comprasQuery($user)
                ->where('id_p', $pelicula->id_p)
                ->get();

            $stats = $this->calculator->calculate($compras);
            $stats['pelicula'] = (new PeliculaResource($pelicula))->resolve();
            $results[] = $stats;
        }

        return $this->sortResults($results, $metric);
    }

    public function byCliente(string $metric, Usuario $user): array
    {
        $results = [];
        $clienteIds = $this->comprasQuery($user)->select('ci')->distinct()->pluck('ci');

        foreach (Cliente::whereIn('ci', $clienteIds)->get() as $cliente) {
            $compras = $this->comprasQuery($user)
                ->where('ci', $cliente->ci)
                ->get();

            $stats = $this->calculator->calculate($compras);
            $stats['cliente'] = (new ClienteResource($cliente))->resolve();
            $results[] = $stats;
        }

        return $this->sortResults($results, $metric);
    }

    private function comprasQuery(Usuario $user)
    {
        $query = Compra::with(['pago.efectivo', 'pago.punto', 'pago.webPayment', 'cliente']);

        if ($user->rol === UserRole::ADMIN->value) {
            $query->whereExists(function ($subQuery) use ($user): void {
                $subQuery->selectRaw('1')
                    ->from('sesiones')
                    ->join('salas', 'sesiones.id_s', '=', 'salas.id_s')
                    ->join('cine_usuario', 'salas.id_c', '=', 'cine_usuario.id_c')
                    ->whereColumn('sesiones.id_p', 'compras.id_p')
                    ->whereColumn('sesiones.id_s', 'compras.id_s')
                    ->whereColumn('sesiones.fecha', 'compras.fecha')
                    ->where('cine_usuario.ci', $user->ci);
            });
        }

        return $query;
    }

    private function sortResults(array $results, string $metric): array
    {
        $sortKey = $this->mapMetricToKey($metric);

        usort($results, fn (array $a, array $b): int => ($b[$sortKey] ?? 0) <=> ($a[$sortKey] ?? 0));

        return $results;
    }

    private function mapMetricToKey(string $metric): string
    {
        return match ($metric) {
            'dinero' => 'total_dinero',
            'efectivo' => 'total_efectivo',
            'transferencia' => 'total_transferencia',
            'puntos' => 'total_puntos',
            'butacas' => 'total_butacas',
            'butacas-efectivo' => 'butacas_efectivo',
            'butacas-transferencia' => 'butacas_transferencia',
            'butacas-puntos' => 'butacas_puntos',
            default => 'total_butacas',
        };
    }
}
