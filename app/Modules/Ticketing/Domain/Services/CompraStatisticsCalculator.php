<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Services;

use App\Modules\Ticketing\Domain\Enums\TipoCompra;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Pago;
use Illuminate\Support\Collection;

final class CompraStatisticsCalculator
{
    /**
     * @param Collection<int, Compra> $compras
     * @return array{
     *   total_dinero: float,
     *   total_efectivo: float,
     *   total_transferencia: float,
     *   total_puntos: int,
     *   total_butacas: int,
     *   butacas_efectivo: int,
     *   butacas_transferencia: int,
     *   butacas_puntos: int
     * }
     */
    public function calculate(Collection $compras): array
    {
        $result = [
            'total_dinero' => 0.0,
            'total_efectivo' => 0.0,
            'total_transferencia' => 0.0,
            'total_puntos' => 0,
            'total_butacas' => 0,
            'butacas_efectivo' => 0,
            'butacas_transferencia' => 0,
            'butacas_puntos' => 0,
        ];

        foreach ($compras as $compra) {
            $butacaCount = $compra->butacas->count();
            $result['total_butacas'] += $butacaCount;

            /** @var Pago|null $pago */
            $pago = $compra->pago;
            if ($pago === null) {
                continue;
            }

            if ($compra->tipo === TipoCompra::TARJETA->value) {
                $result['butacas_transferencia'] += $butacaCount;
                if ($pago->webPayment !== null) {
                    $amount = (float) ($pago->webPayment->cantidad ?? 0);
                    $result['total_transferencia'] += $amount;
                    $result['total_dinero'] += $amount;
                }
            } elseif ($compra->tipo === TipoCompra::PUNTOS->value) {
                $result['butacas_puntos'] += $butacaCount;
                if ($pago->punto !== null) {
                    $result['total_puntos'] += $pago->punto->gastados ?? 0;
                }
            } else {
                $result['butacas_efectivo'] += $butacaCount;
                if ($pago->efectivo !== null) {
                    $amount = (float) ($pago->efectivo->cantidad_e ?? 0);
                    $result['total_efectivo'] += $amount;
                    $result['total_dinero'] += $amount;
                }
            }
        }

        return $result;
    }
}
