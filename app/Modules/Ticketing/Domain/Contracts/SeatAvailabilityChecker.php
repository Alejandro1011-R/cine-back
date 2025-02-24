<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Contracts;

interface SeatAvailabilityChecker
{
    /**
     * @param list<int> $butacaIds
     */
    public function hasReservedSeats(int $peliculaId, int $salaId, string $fecha, array $butacaIds): bool;
}
