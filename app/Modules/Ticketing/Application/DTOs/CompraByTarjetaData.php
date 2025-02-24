<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\DTOs;

final readonly class CompraByTarjetaData
{
    /**
     * @param array<int> $butacaIds
     * @param array<int> $descuentoIds
     */
    public function __construct(
        public int $idP,
        public int $idS,
        public string $fecha,
        public string $ci,
        public ?float $cantidad,
        public string $codigoT,
        public string $fechaDeCompra,
        public array $butacaIds = [],
        public array $descuentoIds = [],
    ) {}
}
