<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Contracts;

interface SeatHoldManager
{
    /**
     * @param array<int> $butacaIds
     */
    public function acquire(int $peliculaId, int $salaId, string $fecha, string $ci, array $butacaIds, int $ttlMinutes = 10): ?string;

    public function release(string $holdToken): void;
}
