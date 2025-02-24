<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\DTOs;

final readonly class CompraByPuntosData
{
    /**
     * @param array<int> $butacaIds
     */
    public function __construct(
        public int $idP,
        public int $idS,
        public string $fecha,
        public string $ci,
        public ?int $cantidad,
        public string $fechaDeCompra,
        public array $butacaIds = [],
    ) {}
}
